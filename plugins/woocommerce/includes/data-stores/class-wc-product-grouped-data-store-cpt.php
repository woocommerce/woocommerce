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
	 * Reconcile a grouped lookup row after a full-row write.
	 *
	 * @since 11.3.0
	 * @param int $product_id Product ID.
	 * @return bool Whether the row is settled or belongs to another product type.
	 */
	public function sync_sale_row( int $product_id ): bool {
		global $wpdb;

		if ( ! $this->owns_sale_rows() ) {
			return true;
		}
		$state = $this->get_sale_state( $product_id );
		if ( null !== $state && ! $state['grouped'] ) {
			return true;
		}
		for ( $attempt = 0; $attempt < 3 && null !== $state; ++$attempt ) {
			if ( null === $state['onsale'] ) {
				return true;
			}
			if ( ! $state['grouped'] ) {
				break;
			}
			$written = $wpdb->update( $wpdb->wc_product_meta_lookup, array( 'onsale' => $state['onsale'] ), array( 'product_id' => $product_id ), array( '%d' ), array( '%d' ) );
			wp_cache_delete( 'lookup_table', 'object_' . $product_id );
			delete_transient( 'wc_products_onsale' );
			if ( false === $written ) {
				break;
			}
			// A concurrent save can finish before this stale writer. Validate after writing, using fresh source reads.
			$current = $this->get_sale_state( $product_id );
			if ( null !== $current && ( null === $current['onsale'] || ( $current['grouped'] && $current['onsale'] === $state['onsale'] && $current['stored'] === $state['onsale'] ) ) ) {
				return true;
			}
			$state = $current;
		}
		$this->queue_sale_refresh( $product_id );
		return false;
	}

	/**
	 * Refresh every group referencing a changed child, including shared children and variation parents.
	 *
	 * @since 11.3.0
	 * @param int  $product_id Changed product ID.
	 * @param bool $include_children Whether a parent's eligibility changed for its children.
	 */
	public function sync_sale_rows( int $product_id, bool $include_children = false ): void {
		global $wpdb;

		if ( ! $this->owns_sale_rows() ) {
			return;
		}
		$this->sync_sale_row( $product_id );
		$child_ids = array();
		if ( $include_children ) {
			$child_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type IN ('product', 'product_variation')", $product_id ) );
			if ( $wpdb->last_error ) {
				$this->queue_sale_refresh( $product_id, true );
				return;
			}
		}
		$child_ids[] = $product_id;
		$affected    = array_fill_keys( array_map( 'intval', $child_ids ), true );
		$group_ids   = array();
		foreach ( array_chunk( $child_ids, 100 ) as $batch ) {
			$conditions = array();
			foreach ( $batch as $child_id ) {
				$conditions[] = $wpdb->prepare( '(meta_value LIKE %s OR meta_value LIKE %s)', '%' . $wpdb->esc_like( 'i:' . (int) $child_id . ';' ) . '%', '%' . $wpdb->esc_like( ':"' . (int) $child_id . '";' ) . '%' );
			}
			$where = implode( ' OR ', $conditions );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Each condition is prepared above.
			$result = $wpdb->query( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_children' AND ($where)" );
			if ( false === $result ) {
				$this->queue_sale_refresh( $product_id, $include_children );
				return;
			}
			foreach ( $wpdb->last_result as $row ) {
				$children = maybe_unserialize( $row->meta_value );
				if ( is_array( $children ) && array_intersect_key( array_fill_keys( wp_parse_id_list( array_filter( $children, 'is_scalar' ) ), true ), $affected ) ) {
					$group_ids[ (int) $row->post_id ] = true;
				}
			}
		}
		foreach ( array_keys( $group_ids ) as $group_id ) {
			$this->sync_sale_row( $group_id );
		}
	}

	/**
	 * Backfill grouped sale rows in a bounded, restartable batch.
	 *
	 * @since 11.3.0
	 * @param int $after_id Last processed ID.
	 * @return int|null Next cursor, zero when complete, null on a failed query.
	 */
	public function rebuild_sale_rows( int $after_id = 0 ): ?int {
		global $wpdb;

		if ( ! $this->owns_sale_rows() ) {
			return 0;
		}
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT posts.ID FROM {$wpdb->posts} posts
				INNER JOIN {$wpdb->term_relationships} relationships ON posts.ID = relationships.object_id
				INNER JOIN {$wpdb->term_taxonomy} taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND taxonomy.taxonomy = 'product_type'
				INNER JOIN {$wpdb->terms} terms ON taxonomy.term_id = terms.term_id AND terms.slug = %s
				WHERE posts.post_type = 'product' AND posts.ID > %d ORDER BY posts.ID LIMIT 200",
				ProductType::GROUPED,
				$after_id
			)
		);
		if ( $wpdb->last_error ) {
			return null;
		}
		foreach ( $ids as $id ) {
			if ( ! $this->sync_sale_row( (int) $id ) ) {
				return null;
			}
		}
		delete_transient( 'wc_products_onsale' );
		return $ids ? (int) end( $ids ) : 0;
	}

	/**
	 * Read persisted source and lookup state without request-local product or metadata caches.
	 *
	 * @param int $product_id Product ID.
	 * @return array{grouped: bool, onsale: int|null, stored: int|null}|null Null on a failed source query.
	 */
	private function get_sale_state( int $product_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT posts.post_status, lookup_table.onsale AS stored,
				EXISTS (SELECT 1 FROM {$wpdb->term_relationships} relationships
					INNER JOIN {$wpdb->term_taxonomy} taxonomy ON relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND taxonomy.taxonomy = 'product_type'
					INNER JOIN {$wpdb->terms} terms ON terms.term_id = taxonomy.term_id AND terms.slug = %s
					WHERE relationships.object_id = posts.ID) AS grouped,
				(SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = posts.ID AND meta_key = '_children' ORDER BY meta_id LIMIT 1) AS children
				FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->wc_product_meta_lookup} lookup_table ON lookup_table.product_id = posts.ID
				WHERE posts.ID = %d AND posts.post_type IN ('product', 'product_variation')",
				ProductType::GROUPED,
				$product_id
			)
		);
		if ( $wpdb->last_error ) {
			return null;
		}
		if ( ! $row ) {
			return array(
				'grouped' => false,
				'onsale'  => null,
				'stored'  => null,
			);
		}
		$onsale = 0;
		if ( $row->grouped ) {
			$children = maybe_unserialize( $row->children ?? '' );
			$eligible = ProductStatus::PUBLISH === $row->post_status && is_array( $children ) ? $this->has_on_sale_child( wp_parse_id_list( array_filter( $children, 'is_scalar' ) ) ) : false;
			if ( null === $eligible ) {
				return null;
			}
			$onsale = $eligible ? 1 : 0;
		}
		return array(
			'grouped' => (bool) $row->grouped,
			'onsale'  => $onsale,
			'stored'  => null === $row->stored ? null : (int) $row->stored,
		);
	}

	/**
	 * Leave replacement stores in control unless they explicitly override this capability.
	 *
	 * @return bool
	 */
	private function owns_sale_rows(): bool {
		return self::class === get_class( $this ) && WC_Product_Data_Store_CPT::class === WC_Data_Store::load( 'product' )->get_current_class_name();
	}

	/**
	 * Hand off an unsettled write without allowing a running action to suppress its successor.
	 *
	 * @param int  $product_id Product ID.
	 * @param bool $include_children Whether a parent's eligibility changed for its children.
	 */
	private function queue_sale_refresh( int $product_id, bool $include_children = false ): void {
		$args = array( 'product_id' => $product_id );
		if ( $include_children ) {
			$args['include_children'] = true;
		}
		if ( ! WC()->queue()->add( 'woocommerce_refresh_grouped_product_sale', $args, 'woocommerce' ) ) {
			wc_get_logger()->error( 'Could not schedule grouped product sale reconciliation for product ' . $product_id, array( 'source' => 'grouped-product-sales' ) );
		}
	}

	/**
	 * Find publicly purchasable core leaf children with an active sale, without user-dependent object filters.
	 * Catalog visibility and stock do not affect the grouped product's sale classification.
	 *
	 * @param int[] $child_ids Referenced child IDs.
	 * @return bool|null Null on a failed source query.
	 */
	private function has_on_sale_child( array $child_ids ): ?bool {
		global $wpdb;

		$now = time();
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
			foreach ( $prices as $meta ) {
				$price   = $meta['_price'] ?? '';
				$regular = $meta['_regular_price'] ?? '';
				$sale    = $meta['_sale_price'] ?? '';
				$from    = $meta['_sale_price_dates_from'] ?? '';
				$to      = $meta['_sale_price_dates_to'] ?? '';
				if ( is_numeric( $price ) && is_numeric( $regular ) && is_numeric( $sale ) && $price === $sale && $regular > $sale
					&& ( empty( $from ) || ( is_numeric( $from ) && (int) $from <= $now ) )
					&& ( empty( $to ) || ( is_numeric( $to ) && (int) $to >= $now ) ) ) {
					return true;
				}
			}
		}
		return false;
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
