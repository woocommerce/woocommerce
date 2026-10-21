<?php
/**
 * Class WC_Product_Grouped_Data_Store_CPT file.
 *
 * @package WooCommerce\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * WC Grouped Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class WC_Product_Grouped_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface {

	/**
	 * Replace grouped sale IDs using public child state, independently of the ordinary sale cache.
	 * Personalized product filters do not determine this public query membership.
	 *
	 * @since 11.3.0
	 * @param int[] $product_ids Cached ordinary sale IDs.
	 * @return int[]
	 */
	public function get_product_ids_on_sale( array $product_ids ): array {
		// A build racing a mutation can only populate its old generation, never the new one.
		$version   = WC_Cache_Helper::get_transient_version( 'product_query' );
		$cache_key = 'wc_grouped_onsale_' . md5( $version );
		$cached    = get_transient( $cache_key );
		if ( false === $cached || $cached['valid_until'] <= time() ) {
			$cached = $this->get_grouped_sale_projection();
			if ( null === $cached ) {
				return $product_ids;
			}
			$ttl = $cached['valid_until'] - time();
			if ( $ttl > 0 ) {
				set_transient( $cache_key, $cached, $ttl );
			}
		}
		return wp_parse_id_list( array_merge( array_diff( $product_ids, $cached['groups'] ), $cached['on_sale'] ) );
	}

	/**
	 * Read the complete grouped replacement set and its next time-dependent change.
	 *
	 * @return array{groups: int[], on_sale: int[], valid_until: int}|null Null on a failed source query.
	 */
	private function get_grouped_sale_projection(): ?array {
		global $wpdb;

		$valid_until = time() + DAY_IN_SECONDS;
		$rows        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT posts.ID, posts.post_status, children.meta_value
				FROM {$wpdb->posts} posts
				INNER JOIN {$wpdb->term_relationships} relationships ON posts.ID = relationships.object_id
				INNER JOIN {$wpdb->term_taxonomy} taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND taxonomy.taxonomy = 'product_type'
				INNER JOIN {$wpdb->terms} terms ON taxonomy.term_id = terms.term_id AND terms.slug = %s
				LEFT JOIN {$wpdb->postmeta} children ON posts.ID = children.post_id AND children.meta_key = '_children'
				WHERE posts.post_type = 'product'
				ORDER BY children.meta_id",
				ProductType::GROUPED
			)
		);
		if ( $wpdb->last_error ) {
			return null;
		}

		$groups    = array();
		$child_ids = array();
		foreach ( $rows as $row ) {
			$id = (int) $row->ID;
			if ( isset( $groups[ $id ] ) ) {
				continue;
			}
			$children      = maybe_unserialize( $row->meta_value );
			$groups[ $id ] = ProductStatus::PUBLISH === $row->post_status && is_array( $children ) ? wp_parse_id_list( array_filter( $children, 'is_scalar' ) ) : array();
			foreach ( $groups[ $id ] as $child_id ) {
				$child_ids[ $child_id ] = true;
			}
		}

		$eligible_ids = $this->get_on_sale_child_ids( array_keys( $child_ids ), $valid_until );
		if ( null === $eligible_ids ) {
			return null;
		}
		$eligible_ids = array_fill_keys( $eligible_ids, true );
		$on_sale      = array();
		foreach ( $groups as $id => $children ) {
			foreach ( $children as $child_id ) {
				if ( isset( $eligible_ids[ $child_id ] ) ) {
					$on_sale[] = $id;
					break;
				}
			}
		}
		return array(
			'groups'      => array_keys( $groups ),
			'on_sale'     => $on_sale,
			'valid_until' => $valid_until,
		);
	}

	/**
	 * Find publicly purchasable core leaf children with an active sale, without user-dependent object filters.
	 * Catalog visibility and stock do not affect the grouped product's sale classification.
	 *
	 * @param int[] $child_ids Referenced child IDs.
	 * @param int   $valid_until Earliest upcoming sale boundary, updated by reference.
	 * @return int[]|null Null on a failed source query.
	 */
	private function get_on_sale_child_ids( array $child_ids, int &$valid_until ): ?array {
		global $wpdb;

		$eligible_ids = array();
		$now          = time();
		foreach ( array_chunk( $child_ids, 1000 ) as $batch ) {
			$ids = implode( ',', array_map( 'absint', $batch ) );
			// Only core simple products and variations have a public, purchasable leaf sale predicate.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$public_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT posts.ID FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->posts} parent ON posts.post_parent = parent.ID
					WHERE posts.ID IN ($ids) AND posts.post_status = %s
					AND posts.post_type IN ('product', 'product_variation')
					AND (parent.ID IS NULL OR parent.post_type NOT IN ('product', 'product_variation') OR parent.post_status = %s)
					AND (posts.post_type = 'product_variation' OR NOT EXISTS (
						SELECT 1 FROM {$wpdb->term_relationships} relationships
						INNER JOIN {$wpdb->term_taxonomy} taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND taxonomy.taxonomy = 'product_type'
						INNER JOIN {$wpdb->terms} terms ON taxonomy.term_id = terms.term_id
						WHERE relationships.object_id = posts.ID AND terms.slug != %s
					))",
					ProductStatus::PUBLISH,
					ProductStatus::PUBLISH,
					ProductType::SIMPLE
				)
			);
			if ( $wpdb->last_error ) {
				return null;
			}
			if ( ! $public_ids ) {
				continue;
			}
			$ids = implode( ',', array_map( 'absint', $public_ids ) );
			// get_results() returns an empty array on SQL failure as well as on a successful empty read.
			$result = $wpdb->query(
				"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
				WHERE post_id IN ($ids) AND meta_key IN ('_price', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to')
				ORDER BY meta_id"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( false === $result ) {
				return null;
			}
			$prices = array();
			foreach ( $wpdb->last_result as $row ) {
				$prices[ $row->post_id ][ $row->meta_key ] = $prices[ $row->post_id ][ $row->meta_key ] ?? $row->meta_value;
			}
			foreach ( $prices as $id => $meta ) {
				$price   = $meta['_price'] ?? '';
				$regular = $meta['_regular_price'] ?? '';
				$sale    = $meta['_sale_price'] ?? '';
				$from    = $meta['_sale_price_dates_from'] ?? '';
				$to      = $meta['_sale_price_dates_to'] ?? '';
				if ( is_numeric( $from ) && (int) $from > $now ) {
					$valid_until = min( $valid_until, (int) $from );
				}
				if ( is_numeric( $to ) && (int) $to >= $now ) {
					$valid_until = min( $valid_until, (int) $to + 1 );
				}
				if ( is_numeric( $price ) && is_numeric( $regular ) && is_numeric( $sale ) && $regular > $sale
					&& ( empty( $from ) || ( is_numeric( $from ) && (int) $from <= $now ) )
					&& ( empty( $to ) || ( is_numeric( $to ) && (int) $to >= $now ) ) ) {
					$eligible_ids[] = (int) $id;
				}
			}
		}
		return $eligible_ids;
	}

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 *
	 * @param WC_Product $product Product object.
	 * @param bool       $force Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
		$meta_key_to_props = array(
			'_children' => 'children',
		);

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $product, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value   = $product->{"get_$prop"}( 'edit' );
			$updated = update_post_meta( $product->get_id(), $meta_key, $value );
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		parent::update_post_meta( $product, $force );
	}

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since  3.0.0
	 * @param  WC_Product $product Product object.
	 */
	protected function handle_updated_props( &$product ) {
		if ( in_array( 'children', $this->updated_props, true ) ) {
			$this->update_prices_from_children( $product );
		}
		parent::handle_updated_props( $product );
	}

	/**
	 * Sync grouped product prices with children.
	 *
	 * @since 3.0.0
	 * @param WC_Product|int $product Product object or product ID.
	 */
	public function sync_price( &$product ) {
		$this->update_prices_from_children( $product );
	}

	/**
	 * Loop over child products and update the grouped product prices.
	 *
	 * @param WC_Product $product Product object.
	 */
	protected function update_prices_from_children( &$product ) {
		$product_id   = $product->get_id();
		$child_prices = array();

		$child_ids = $product->get_children( 'edit' );
		if ( ! empty( $child_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $child_ids );
			foreach ( $child_ids as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child ) {
					$child_prices[] = $child->get_price( 'edit' );
				}
			}
			$child_prices = array_filter( $child_prices );
		}

		// Performance note: prefilter with metadata_exists, to avoid unnecessary meta cache invalidations and SQLs.
		$price_metas = array_filter(
			array( '_price', '_sale_price', '_regular_price' ),
			static fn( $meta_key ) => metadata_exists( 'post', $product_id, $meta_key )
		);
		array_walk( $price_metas, static fn( $meta_key ) => delete_post_meta( $product_id, $meta_key ) );

		if ( ! empty( $child_prices ) ) {
			add_post_meta( $product_id, '_price', min( $child_prices ) );
			add_post_meta( $product_id, '_price', max( $child_prices ) );
		}

		$this->update_lookup_table( $product_id, 'wc_product_meta_lookup' );

		/**
		 * Fire an action for this direct update so it can be detected by other code.
		 *
		 * @since 3.6
		 * @param int $product_id Product ID that was updated directly.
		 */
		do_action( 'woocommerce_updated_product_price', $product_id );
	}
}
