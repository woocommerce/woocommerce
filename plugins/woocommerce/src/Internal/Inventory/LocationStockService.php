<?php
/**
 * LocationStockService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductType;

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
	 * Get the inventory locations table name.
	 */
	public function get_locations_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'wc_inventory_locations';
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
	 * Get product stock at a location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 */
	public function get_location_stock( $product, string $location_slug ): float {
		global $wpdb;

		if ( ! $this->tables_exist() ) {
			return 0.0;
		}

		$key         = $this->get_product_inventory_key( $product );
		$location_id = $this->get_location_id( $location_slug );
		if ( ! $key || 0 === $location_id ) {
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

		$quantity = wc_stock_amount( $quantity );
		if ( ! $this->tables_exist() ) {
			return $quantity;
		}

		$key = $this->get_product_inventory_key( $product );
		if ( ! $key ) {
			return $quantity;
		}

		$wpdb->query(
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
		return $this->change_location_stock( $product, $location_slug, wc_stock_amount( $quantity ) );
	}

	/**
	 * Decrease stock for one location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 * @param int|float       $quantity      Quantity.
	 */
	public function decrease_location_stock( $product, string $location_slug, $quantity ): float {
		return $this->change_location_stock( $product, $location_slug, wc_stock_amount( $quantity ) * -1 );
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
	 * Apply a signed stock delta to one location.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 * @param int|float       $delta         Signed quantity delta.
	 */
	private function change_location_stock( $product, string $location_slug, $delta ): float {
		$new_stock = $this->get_location_stock( $product, $location_slug ) + wc_stock_amount( $delta );

		return $this->set_location_stock( $product, $location_slug, $new_stock );
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
		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product );
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$product_with_stock = wc_get_product( $product->get_stock_managed_by_id() );
		if ( ! $product_with_stock instanceof \WC_Product ) {
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
	 * Normalize a location slug.
	 *
	 * @param string $slug Location slug.
	 */
	private function normalize_location_slug( string $slug ): string {
		return sanitize_title( $slug );
	}
}
