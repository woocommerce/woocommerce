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
	 * Cache namespace for the grouped sale projection.
	 *
	 * @var string
	 */
	private const SALE_CACHE_GROUP = 'grouped_products_onsale';

	/**
	 * Replace grouped IDs with groups containing a publicly purchasable, discounted core child.
	 * Child catalog visibility and stock do not affect the group's sale classification.
	 *
	 * @since 11.4.0
	 * @param int[] $product_ids Cached ordinary sale IDs.
	 * @return int[] Sale IDs including currently qualifying groups.
	 */
	public function reconcile_product_ids_on_sale( array $product_ids ): array {
		$version = WC_Cache_Helper::get_transient_version( self::SALE_CACHE_GROUP );
		$cache   = get_transient( 'wc_' . self::SALE_CACHE_GROUP );
		$now     = time();
		if ( false === $cache || $cache['version'] !== $version || $now < $cache['evaluated_at'] || $now >= $cache['valid_until'] ) {
			$cache            = $this->get_sale_projection();
			$cache['version'] = $version;
			$ttl              = $cache['valid_until'] - time();
			if ( $ttl > 0 ) {
				// Keep the captured version so a refill crossing a mutation cannot become a valid new-generation entry.
				set_transient( 'wc_' . self::SALE_CACHE_GROUP, $cache, $ttl );
			}
		}
		return array_values( array_unique( array_merge( array_diff( $product_ids, $cache['group_ids'] ), $cache['on_sale'] ) ) );
	}

	/**
	 * Invalidate grouped sale discovery without loading any memberships.
	 *
	 * @since 11.4.0
	 */
	public static function invalidate_sale_cache(): void {
		delete_transient( self::SALE_CACHE_GROUP . '-transient-version' );
	}

	/**
	 * Build a grouped sale projection valid until the next relevant date boundary.
	 *
	 * @return array{group_ids: int[], on_sale: int[], evaluated_at: int, valid_until: int}
	 */
	private function get_sale_projection(): array {
		global $wpdb;

		$groups = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT posts.ID, posts.post_status,
				(SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = posts.ID AND meta_key = '_children' ORDER BY meta_id LIMIT 1) AS children
				FROM {$wpdb->terms} terms
				INNER JOIN {$wpdb->term_taxonomy} taxonomy ON terms.term_id = taxonomy.term_id AND taxonomy.taxonomy = 'product_type'
				INNER JOIN {$wpdb->term_relationships} relationships ON taxonomy.term_taxonomy_id = relationships.term_taxonomy_id
				INNER JOIN {$wpdb->posts} posts ON relationships.object_id = posts.ID
				WHERE terms.name = %s AND posts.post_type = 'product'",
				ProductType::GROUPED
			),
			OBJECT_K
		);

		$cacheable   = '' === $wpdb->last_error;
		$memberships = array();
		$child_ids   = array();
		foreach ( $groups ?? array() as $id => $group ) {
			$children = maybe_unserialize( $group->children ?? '' );
			if ( ProductStatus::PUBLISH !== $group->post_status || ! is_array( $children ) ) {
				continue;
			}
			$memberships[ $id ] = array_fill_keys( wp_parse_id_list( array_filter( $children, 'is_scalar' ) ), true );
			$child_ids         += $memberships[ $id ];
		}

		$windows = array();
		foreach ( array_chunk( array_keys( $child_ids ), 1000 ) as $batch ) {
			$windows  += $this->get_child_sale_windows( $batch );
			$cacheable = $cacheable && '' === $wpdb->last_error;
		}
		$now      = time();
		$expires  = $now + DAY_IN_SECONDS;
		$eligible = array();
		$sale_ids = array();
		foreach ( $windows as $id => $window ) {
			if ( ( null === $window['from'] || $window['from'] <= $now ) && ( null === $window['to'] || $window['to'] >= $now ) ) {
				$eligible[ $id ] = true;
			}
			if ( null !== $window['from'] && $window['from'] > $now ) {
				$expires = min( $expires, $window['from'] );
			}
			if ( null !== $window['to'] && $window['to'] >= $now && $window['to'] < PHP_INT_MAX ) {
				$expires = min( $expires, $window['to'] + 1 );
			}
		}
		foreach ( $memberships as $id => $children ) {
			if ( array_intersect_key( $children, $eligible ) ) {
				$sale_ids[] = (int) $id;
			}
		}
		return array(
			'group_ids'    => array_map( 'intval', array_keys( $groups ?? array() ) ),
			'on_sale'      => $sale_ids,
			'evaluated_at' => $now,
			'valid_until'  => $cacheable ? $expires : $now,
		);
	}

	/**
	 * Find time-neutral discount windows without user-dependent object filters.
	 *
	 * @param int[] $child_ids Nonempty batch of referenced child IDs.
	 * @return array<int, array{from: ?int, to: ?int}> Discount windows keyed by child ID.
	 */
	private function get_child_sale_windows( array $child_ids ): array {
		global $wpdb;

		$ids = implode( ',', array_map( 'absint', $child_ids ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- IDs are sanitized above.
		$public_children = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT posts.ID, posts.post_type FROM {$wpdb->posts} posts
				LEFT JOIN {$wpdb->posts} parent ON posts.post_parent = parent.ID
				WHERE posts.ID IN ($ids) AND posts.post_status = %s
				AND posts.post_type IN ('product', 'product_variation')
				AND (parent.ID IS NULL OR parent.post_type NOT IN ('product', 'product_variation') OR parent.post_status = %s)
				AND (posts.post_type = 'product_variation' OR NOT EXISTS (
					SELECT 1 FROM {$wpdb->term_relationships} relationships
					INNER JOIN {$wpdb->term_taxonomy} taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND taxonomy.taxonomy = 'product_type'
					INNER JOIN {$wpdb->terms} terms ON taxonomy.term_id = terms.term_id
					WHERE relationships.object_id = posts.ID AND terms.name != %s
				))",
				ProductStatus::PUBLISH,
				ProductStatus::PUBLISH,
				ProductType::SIMPLE
			),
			OBJECT_K
		);
		if ( ! $public_children ) {
			return array();
		}
		$ids  = implode( ',', array_map( 'absint', array_keys( $public_children ) ) );
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
			WHERE post_id IN ($ids) AND meta_key IN ('_price', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to')
			ORDER BY meta_id"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$prices = array();
		foreach ( $rows ?? array() as $row ) {
			$prices[ $row->post_id ][ $row->meta_key ] = $prices[ $row->post_id ][ $row->meta_key ] ?? $row->meta_value;
		}
		$windows = array();
		foreach ( $prices as $id => $meta ) {
			$price   = $meta['_price'] ?? '';
			$regular = $meta['_regular_price'] ?? '';
			$sale    = $meta['_sale_price'] ?? '';
			$from    = $meta['_sale_price_dates_from'] ?? '';
			$to      = $meta['_sale_price_dates_to'] ?? '';
			// Variations derive their active price from regular/sale prices, not stored _price.
			if ( ( 'product_variation' === $public_children[ $id ]->post_type || is_numeric( $price ) )
				&& is_numeric( $regular ) && is_numeric( $sale ) && $regular > $sale
				&& ( empty( $from ) || is_numeric( $from ) )
				&& ( empty( $to ) || is_numeric( $to ) ) ) {
				$windows[ (int) $id ] = array(
					'from' => empty( $from ) ? null : (int) $from,
					'to'   => empty( $to ) ? null : (int) $to,
				);
			}
		}
		return $windows;
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
