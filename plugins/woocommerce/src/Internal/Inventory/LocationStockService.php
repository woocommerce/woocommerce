<?php
/**
 * LocationStockService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Internal data access for POS location stock.
 *
 * @internal
 */
class LocationStockService {

	/**
	 * POS inventory location slug.
	 */
	public const LOCATION_POS = 'pos';

	/**
	 * Deferred modified-date update nesting level.
	 *
	 * @var int
	 */
	private $modified_date_update_deferral_level = 0;

	/**
	 * Product IDs queued for a deferred modified-date update.
	 *
	 * @var array<int,true>
	 */
	private $deferred_modified_date_product_ids = array();

	/**
	 * Get the locations table name.
	 */
	public function get_locations_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'wc_locations';
	}

	/**
	 * Get the product inventory table name.
	 */
	public function get_product_inventory_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'wc_product_inventory';
	}

	/**
	 * Get the database schema.
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate                 = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$locations_table         = $this->get_locations_table_name();
		$product_inventory_table = $this->get_product_inventory_table_name();

		return "
CREATE TABLE $locations_table (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	slug varchar(100) NOT NULL,
	name varchar(255) NOT NULL,
	created_at_gmt datetime NOT NULL,
	deleted_at_gmt datetime NULL DEFAULT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY slug (slug)
) $collate;
CREATE TABLE $product_inventory_table (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	product_id bigint(20) unsigned NOT NULL,
	variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
	location_id bigint(20) unsigned NOT NULL,
	quantity decimal(19,6) NOT NULL DEFAULT 0,
	PRIMARY KEY  (id),
	UNIQUE KEY product_variation_location (product_id, variation_id, location_id),
	KEY location_product (location_id, product_id, variation_id)
) $collate;
		";
	}

	/**
	 * Determine whether both inventory tables exist.
	 */
	public function tables_exist(): bool {
		global $wpdb;

		$locations_table         = $this->get_locations_table_name();
		$product_inventory_table = $this->get_product_inventory_table_name();

		$locations_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $locations_table ) );
		$inventory_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $product_inventory_table ) );

		return $locations_table === $locations_exists && $product_inventory_table === $inventory_exists;
	}

	/**
	 * Ensure the POS location row exists.
	 */
	public function ensure_pos_location(): void {
		$this->ensure_location( self::LOCATION_POS, 'POS' );
	}

	/**
	 * Defer product modified-date updates until a group of stock writes completes.
	 *
	 * @param callable $callback Stock write callback.
	 */
	public function with_deferred_product_modified_date_updates( callable $callback ): void {
		++$this->modified_date_update_deferral_level;

		try {
			$callback();
		} finally {
			--$this->modified_date_update_deferral_level;

			if ( 0 === $this->modified_date_update_deferral_level ) {
				$this->flush_deferred_product_modified_date_updates();
			}
		}
	}

	/**
	 * Get an active location row by slug.
	 *
	 * @param string $slug Location slug.
	 * @return array{id:int,slug:string,name:string}|null
	 */
	public function get_location( string $slug ): ?array {
		global $wpdb;

		if ( ! $this->tables_exist() ) {
			return null;
		}

		$slug = $this->normalize_location_slug( $slug );
		if ( '' === $slug ) {
			return null;
		}

		$location = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, slug, name FROM %i WHERE slug = %s AND deleted_at_gmt IS NULL',
				$this->get_locations_table_name(),
				$slug
			),
			ARRAY_A
		);

		if ( ! is_array( $location ) ) {
			return null;
		}

		return array(
			'id'   => (int) $location['id'],
			'slug' => (string) $location['slug'],
			'name' => (string) $location['name'],
		);
	}

	/**
	 * Check whether a slug maps to an active inventory location.
	 *
	 * @param string $slug Location slug.
	 */
	public function is_known_location_slug( string $slug ): bool {
		return null !== $this->get_location( $slug );
	}

	/**
	 * Get a location display name by slug.
	 *
	 * @param string $slug Location slug.
	 */
	public function get_location_name( string $slug ): string {
		$location = $this->get_location( $slug );

		return $location ? $location['name'] : $slug;
	}

	/**
	 * Build an insufficient-stock error for a location.
	 *
	 * @param string    $location_slug Location slug.
	 * @param string    $item_name     Name to display.
	 * @param int|float $requested     Requested quantity.
	 * @param int|float $available     Available quantity.
	 * @param bool      $rest_error    Whether to build a REST-facing error (adds a 400 status).
	 */
	public function get_insufficient_stock_error( string $location_slug, string $item_name, $requested, $available, bool $rest_error = false ): \WP_Error {
		return new \WP_Error(
			$rest_error ? 'woocommerce_rest_location_stock_insufficient' : 'woocommerce_location_stock_insufficient',
			sprintf(
				/* translators: 1: location name 2: item name 3: requested quantity 4: available quantity */
				__( 'Not enough stock at %1$s for %2$s. Requested %3$s, available %4$s.', 'woocommerce' ),
				$this->get_location_name( $location_slug ),
				$item_name,
				wc_stock_amount( $requested ),
				wc_stock_amount( $available )
			),
			$rest_error ? array( 'status' => 400 ) : array()
		);
	}

	/**
	 * Get product stock at a location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 */
	public function get_location_stock( $product, string $location_slug ): float {
		return $this->get_location_stock_for_inventory_key(
			$this->get_product_inventory_key( $product ),
			$location_slug
		);
	}

	/**
	 * Get product stock from its own inventory row, ignoring Core stock-owner resolution.
	 *
	 * Runtime stock movement should use get_location_stock() so parent-managed variations
	 * follow Core's get_stock_managed_by_id() behavior. This method is for admin edit fields
	 * that need to show the value stored directly on the product or variation being edited.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 */
	public function get_location_stock_for_product_record( $product, string $location_slug ): float {
		return $this->get_location_stock_for_inventory_key(
			$this->get_product_record_inventory_key( $product ),
			$location_slug
		);
	}

	/**
	 * Get stock for an inventory key at a location.
	 *
	 * @param array{product_id:int,variation_id:int}|null $key           Product inventory key.
	 * @param string                                      $location_slug Location slug.
	 */
	private function get_location_stock_for_inventory_key( ?array $key, string $location_slug ): float {
		global $wpdb;

		if ( ! $key || ! $this->tables_exist() ) {
			return 0.0;
		}

		$location_id = $this->get_location_id( $location_slug );
		if ( 0 === $location_id ) {
			return 0.0;
		}

		return wc_stock_amount(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT quantity FROM %i WHERE product_id = %d AND variation_id = %d AND location_id = %d',
					$this->get_product_inventory_table_name(),
					$key['product_id'],
					$key['variation_id'],
					$location_id
				)
			)
		);
	}

	/**
	 * Set stock for one location without changing legacy _stock.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 * @param int|float       $quantity      Quantity.
	 */
	public function set_location_stock( $product, string $location_slug, $quantity ): float {
		global $wpdb;

		$quantity = max( 0, wc_stock_amount( $quantity ) );
		if ( ! $this->tables_exist() ) {
			return $quantity;
		}

		$key = $this->get_product_inventory_key( $product );
		if ( ! $key ) {
			return $quantity;
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i ( product_id, variation_id, location_id, quantity )
				VALUES ( %d, %d, %d, %f )
				ON DUPLICATE KEY UPDATE quantity = VALUES( quantity )",
				$this->get_product_inventory_table_name(),
				$key['product_id'],
				$key['variation_id'],
				$this->get_required_location_id( $location_slug ),
				$quantity
			)
		);

		$this->touch_product_modified_date_after_stock_update( $product, $updated );

		return $quantity;
	}

	/**
	 * Increase stock for one location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 * @param int|float       $quantity      Quantity.
	 */
	public function increase_location_stock( $product, string $location_slug, $quantity ): float {
		global $wpdb;

		$quantity = wc_stock_amount( $quantity );
		if ( (float) $quantity <= 0.0 || ! $this->tables_exist() ) {
			return $this->get_location_stock( $product, $location_slug );
		}

		$key = $this->get_product_inventory_key( $product );
		if ( ! $key ) {
			return $this->get_location_stock( $product, $location_slug );
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i ( product_id, variation_id, location_id, quantity )
				VALUES ( %d, %d, %d, %f )
				ON DUPLICATE KEY UPDATE quantity = quantity + VALUES( quantity )",
				$this->get_product_inventory_table_name(),
				$key['product_id'],
				$key['variation_id'],
				$this->get_required_location_id( $location_slug ),
				$quantity
			)
		);

		$this->touch_product_modified_date_after_stock_update( $product, $updated );

		return $this->get_location_stock( $product, $location_slug );
	}

	/**
	 * Decrease stock for one location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 * @param int|float       $quantity      Quantity.
	 * @return float|null New stock, or null when the decrease would make stock negative.
	 */
	public function decrease_location_stock( $product, string $location_slug, $quantity ): ?float {
		global $wpdb;

		$quantity = wc_stock_amount( $quantity );
		if ( (float) $quantity <= 0.0 ) {
			return $this->get_location_stock( $product, $location_slug );
		}

		if ( ! $this->tables_exist() ) {
			return null;
		}

		$key         = $this->get_product_inventory_key( $product );
		$location_id = $this->get_location_id( $location_slug );
		if ( ! $key || 0 === $location_id ) {
			return null;
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i
				SET quantity = quantity - %f
				WHERE product_id = %d
				AND variation_id = %d
				AND location_id = %d
				AND quantity >= %f",
				$this->get_product_inventory_table_name(),
				$quantity,
				$key['product_id'],
				$key['variation_id'],
				$location_id,
				$quantity
			)
		);

		if ( 1 !== $updated ) {
			return null;
		}

		$this->touch_product_modified_date_after_stock_update( $product, $updated );

		return $this->get_location_stock( $product, $location_slug );
	}

	/**
	 * Ensure one location row exists.
	 *
	 * @param string $slug Location slug.
	 * @param string $name Location display name.
	 */
	private function ensure_location( string $slug, string $name ): void {
		global $wpdb;

		if ( ! $this->tables_exist() ) {
			return;
		}

		$slug = $this->normalize_location_slug( $slug );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i ( slug, name, created_at_gmt, deleted_at_gmt )
				VALUES ( %s, %s, %s, NULL )
				ON DUPLICATE KEY UPDATE name = VALUES( name ), deleted_at_gmt = NULL",
				$this->get_locations_table_name(),
				$slug,
				$name,
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}

	/**
	 * Get a location ID by slug.
	 *
	 * @param string $slug Location slug.
	 */
	private function get_location_id( string $slug ): int {
		$location = $this->get_location( $slug );

		return $location ? $location['id'] : 0;
	}

	/**
	 * Get an active location ID or fail for invalid writes.
	 *
	 * @param string $slug Location slug.
	 * @throws \InvalidArgumentException When the location does not exist.
	 */
	private function get_required_location_id( string $slug ): int {
		$location_id = $this->get_location_id( $slug );
		if ( $location_id > 0 ) {
			return $location_id;
		}

		throw new \InvalidArgumentException( sprintf( 'Unknown inventory location: %s.', $slug ) );
	}

	/**
	 * Get product/variation key columns for inventory rows.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 * @return array{product_id:int,variation_id:int}|null
	 */
	private function get_product_inventory_key( $product ): ?array {
		$product_with_stock = $this->get_stock_managed_product( $product );
		if ( ! $product_with_stock ) {
			return null;
		}

		if ( $product_with_stock->is_type( ProductType::VARIATION ) ) {
			return array(
				'product_id'   => (int) $product_with_stock->get_parent_id(),
				'variation_id' => (int) $product_with_stock->get_id(),
			);
		}

		return array(
			'product_id'   => (int) $product_with_stock->get_id(),
			'variation_id' => 0,
		);
	}

	/**
	 * Get product/variation key columns for the supplied product's own inventory row.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 * @return array{product_id:int,variation_id:int}|null
	 */
	private function get_product_record_inventory_key( $product ): ?array {
		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product );
		if ( ! $product instanceof \WC_Product || $product->get_id() <= 0 ) {
			return null;
		}

		if ( $product->is_type( ProductType::VARIATION ) ) {
			$parent_id = (int) $product->get_parent_id();
			if ( $parent_id <= 0 ) {
				return null;
			}

			return array(
				'product_id'   => $parent_id,
				'variation_id' => (int) $product->get_id(),
			);
		}

		return array(
			'product_id'   => (int) $product->get_id(),
			'variation_id' => 0,
		);
	}

	/**
	 * Get the product object that owns stock for the supplied product.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 */
	private function get_stock_managed_product( $product ): ?\WC_Product {
		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product );
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$product_with_stock = wc_get_product( $product->get_stock_managed_by_id() );

		return $product_with_stock instanceof \WC_Product ? $product_with_stock : null;
	}

	/**
	 * Update product modified dates after a successful location stock write.
	 *
	 * @param \WC_Product|int $product      Product object or ID.
	 * @param int|bool        $rows_updated Rows updated by the stock query.
	 */
	private function touch_product_modified_date_after_stock_update( $product, $rows_updated ): void {
		if ( ! is_int( $rows_updated ) || $rows_updated < 1 ) {
			return;
		}

		$product_ids = $this->get_product_ids_to_touch_after_stock_update( $product );
		if ( empty( $product_ids ) ) {
			return;
		}

		if ( $this->modified_date_update_deferral_level > 0 ) {
			foreach ( $product_ids as $product_id ) {
				$this->deferred_modified_date_product_ids[ $product_id ] = true;
			}
			return;
		}

		$this->touch_product_modified_dates( $product_ids );
	}

	/**
	 * Get product IDs whose catalog rows changed after a location stock write.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 * @return array<int,int>
	 */
	private function get_product_ids_to_touch_after_stock_update( $product ): array {
		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product );
		if ( ! $product instanceof \WC_Product ) {
			return array();
		}

		$product_with_stock = $this->get_stock_managed_product( $product );
		if ( ! $product_with_stock ) {
			return array();
		}

		$product_ids = array( $product_with_stock->get_id() );
		if ( $product->is_type( ProductType::VARIATION ) && $product->get_id() !== $product_with_stock->get_id() ) {
			$product_ids[] = $product->get_id();
		}

		if ( $product_with_stock->is_type( ProductType::VARIABLE ) ) {
			$product_ids = array_merge( $product_ids, $this->get_parent_managed_variation_ids( $product_with_stock ) );
		}

		return $this->normalize_product_ids( $product_ids );
	}

	/**
	 * Get variation IDs that inherit stock from a parent product.
	 *
	 * @param \WC_Product $parent_product Parent product object.
	 * @return array<int,int>
	 */
	private function get_parent_managed_variation_ids( \WC_Product $parent_product ): array {
		$children = $this->normalize_product_ids( $parent_product->get_children() );
		if ( empty( $children ) ) {
			return array();
		}

		global $wpdb;

		$children_sql = implode( ', ', array_map( 'absint', $children ) );

		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $children_sql is an absint-normalized ID list.
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s AND post_id IN ($children_sql)",
				'_manage_stock',
				'yes'
			)
		);

		return $this->normalize_product_ids( $variation_ids );
	}

	/**
	 * Flush product modified-date updates queued during a deferred stock write group.
	 */
	private function flush_deferred_product_modified_date_updates(): void {
		if ( empty( $this->deferred_modified_date_product_ids ) ) {
			return;
		}

		$product_ids                              = array_keys( $this->deferred_modified_date_product_ids );
		$this->deferred_modified_date_product_ids = array();

		$this->touch_product_modified_dates( $product_ids );
	}

	/**
	 * Update product modified dates.
	 *
	 * @param array<int,int> $product_ids Product IDs.
	 */
	private function touch_product_modified_dates( array $product_ids ): void {
		$product_ids = $this->normalize_product_ids( $product_ids );
		if ( empty( $product_ids ) ) {
			return;
		}

		global $wpdb;

		$product_ids_sql = implode( ', ', array_map( 'absint', $product_ids ) );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $product_ids_sql is an absint-normalized ID list.
				"UPDATE {$wpdb->posts} SET post_modified = %s, post_modified_gmt = %s WHERE ID IN ($product_ids_sql)",
				current_time( 'mysql' ),
				current_time( 'mysql', 1 )
			)
		);

		$this->clear_product_caches_after_modified_date_update( $product_ids );
	}

	/**
	 * Clear caches affected by directly touching product modified dates.
	 *
	 * @param array<int,int> $product_ids Product IDs.
	 */
	private function clear_product_caches_after_modified_date_update( array $product_ids ): void {
		$product_ids           = $this->normalize_product_ids( $product_ids );
		$transient_product_ids = array();

		foreach ( $product_ids as $product_id ) {
			$transient_product_ids[] = $product_id;
			$parent_id               = wp_get_post_parent_id( $product_id );

			clean_post_cache( $product_id );
			\WC_Cache_Helper::invalidate_cache_group( 'product_' . $product_id );

			if ( $parent_id ) {
				$transient_product_ids[] = $parent_id;
				\WC_Cache_Helper::invalidate_cache_group( 'product_' . $parent_id );
			}
		}

		$transient_product_ids = $this->normalize_product_ids( $transient_product_ids );
		foreach ( $transient_product_ids as $product_id ) {
			wc_delete_product_transients( $product_id );
		}

		if ( FeaturesUtil::feature_is_enabled( 'product_instance_caching' ) ) {
			$product_cache = wc_get_container()->get( ProductCache::class );
			foreach ( $transient_product_ids as $product_id ) {
				$product_cache->remove( $product_id );
			}
		}
	}

	/**
	 * Normalize a list of product IDs.
	 *
	 * @param array<int|string,int|string> $product_ids Product IDs.
	 * @return array<int,int>
	 */
	private function normalize_product_ids( array $product_ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
	}

	/**
	 * Normalize a location slug.
	 *
	 * @param string $slug Location slug.
	 */
	private function normalize_location_slug( string $slug ): string {
		return sanitize_title( $slug );
	}
}
