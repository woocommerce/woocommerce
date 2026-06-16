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
 * Internal data access for option-backed POS locations and product-meta stock.
 *
 * @internal
 */
class LocationStockService {

	/**
	 * Default POS inventory location slug.
	 */
	public const LOCATION_POS = 'pos';

	/**
	 * Option key used to store configured POS locations.
	 */
	public const LOCATIONS_OPTION = 'woocommerce_pos_location_stock_locations';

	/**
	 * Maximum number of POS locations supported by this spike.
	 */
	public const MAX_POS_LOCATIONS = 5;

	private const LOCATION_STOCK_META_PREFIX = '_woocommerce_pos_location_stock_';

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
	 * Ensure the default POS location option exists.
	 */
	public function ensure_pos_location(): void {
		$locations = $this->get_locations();
		if ( isset( $locations[ self::LOCATION_POS ] ) ) {
			return;
		}

		$this->set_locations(
			array_merge(
				array( $this->get_default_pos_location() ),
				array_values( $locations )
			)
		);
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
	 * Get all configured POS locations, keyed by slug.
	 *
	 * @return array<string,array{slug:string,name:string,address_1:string,address_2:string,city:string,state:string,postcode:string,country:string}>
	 */
	public function get_locations(): array {
		$locations = get_option( self::LOCATIONS_OPTION, array() );
		if ( ! is_array( $locations ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $locations as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}

			$location = $this->normalize_location( $location );
			if ( '' === $location['slug'] || isset( $normalized[ $location['slug'] ] ) ) {
				continue;
			}

			$normalized[ $location['slug'] ] = $location;
			if ( count( $normalized ) >= self::MAX_POS_LOCATIONS ) {
				break;
			}
		}

		return $normalized;
	}

	/**
	 * Replace configured POS locations.
	 *
	 * @param array<int,array<string,mixed>> $locations Location data.
	 */
	public function set_locations( array $locations ): void {
		update_option( self::LOCATIONS_OPTION, $this->normalize_locations( $locations ), false );
	}

	/**
	 * Normalize configured POS locations.
	 *
	 * @param array<int,array<string,mixed>> $locations Location data.
	 * @return array<int,array{slug:string,name:string,address_1:string,address_2:string,city:string,state:string,postcode:string,country:string}>
	 */
	public function normalize_locations( array $locations ): array {
		$normalized = array();

		foreach ( $locations as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}

			$location = $this->normalize_location( $location );
			if ( '' === $location['slug'] || isset( $normalized[ $location['slug'] ] ) ) {
				continue;
			}

			$normalized[ $location['slug'] ] = $location;
			if ( count( $normalized ) >= self::MAX_POS_LOCATIONS ) {
				break;
			}
		}

		return array_values( $normalized );
	}

	/**
	 * Get an active configured location by slug.
	 *
	 * @param string $slug Location slug.
	 * @return array{slug:string,name:string,address_1:string,address_2:string,city:string,state:string,postcode:string,country:string}|null
	 */
	public function get_location( string $slug ): ?array {
		$locations = $this->get_locations();
		$slug      = $this->normalize_location_slug( $slug );

		return $locations[ $slug ] ?? null;
	}

	/**
	 * Check whether a slug maps to a configured POS location.
	 *
	 * @param string $slug Location slug.
	 */
	public function is_known_location_slug( string $slug ): bool {
		return null !== $this->get_location( $slug );
	}

	/**
	 * Check whether at least one POS location has been configured.
	 */
	public function has_locations(): bool {
		return ! empty( $this->get_locations() );
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
	 * Get the formatted address for an order's saved inventory location.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function get_order_location_address( \WC_Order $order ): string {
		$location_slug = sanitize_title( (string) $order->get_meta( InventoryController::ORDER_LOCATION_META, true ) );
		if ( '' === $location_slug ) {
			return '';
		}

		return $this->get_location_address( $location_slug );
	}

	/**
	 * Get a newline-separated address for a configured location.
	 *
	 * @param string $slug Location slug.
	 */
	public function get_location_address( string $slug ): string {
		$location = $this->get_location( $slug );
		if ( ! $location ) {
			return '';
		}

		$country = $location['country'];
		$state   = $location['state'];
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$country = WC()->countries->countries[ $location['country'] ] ?? $country;
			$states  = WC()->countries->get_states( $location['country'] );
			if ( is_array( $states ) ) {
				$state = $states[ $location['state'] ] ?? $state;
			}
		}

		$city_region_postcode = implode(
			', ',
			array_filter(
				array(
					$location['city'],
					$state,
					$location['postcode'],
				),
				'strlen'
			)
		);

		return implode(
			"\n",
			array_filter(
				array(
					$location['address_1'],
					$location['address_2'],
					$city_region_postcode,
					$country,
				),
				'strlen'
			)
		);
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
	 * Missing product meta intentionally means zero stock for configured locations.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 */
	public function get_location_stock( $product, string $location_slug ): float {
		return $this->get_location_stock_for_product_id(
			$this->get_stock_managed_product_id( $product ),
			$location_slug
		);
	}

	/**
	 * Get product stock from its own meta row, ignoring Core stock-owner resolution.
	 *
	 * Runtime stock movement should use get_location_stock() so parent-managed variations
	 * follow Core's get_stock_managed_by_id() behavior. This method is for admin edit fields
	 * that need to show the value stored directly on the product or variation being edited.
	 *
	 * @param \WC_Product|int $product       Product object or ID.
	 * @param string          $location_slug Location slug.
	 */
	public function get_location_stock_for_product_record( $product, string $location_slug ): float {
		return $this->get_location_stock_for_product_id(
			$this->get_product_record_id( $product ),
			$location_slug
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
		$quantity   = max( 0, wc_stock_amount( $quantity ) );
		$product_id = $this->get_stock_managed_product_id( $product );
		if ( 0 === $product_id ) {
			return $quantity;
		}

		$updated = update_post_meta( $product_id, $this->get_required_location_stock_meta_key( $location_slug ), (string) $quantity );
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
		$quantity = wc_stock_amount( $quantity );
		if ( (float) $quantity <= 0.0 ) {
			return $this->get_location_stock( $product, $location_slug );
		}

		$product_id = $this->get_stock_managed_product_id( $product );
		if ( 0 === $product_id ) {
			return $this->get_location_stock( $product, $location_slug );
		}

		$stock_meta_key = $this->get_required_location_stock_meta_key( $location_slug );
		$new_stock      = $this->get_location_stock_for_product_id( $product_id, $location_slug ) + $quantity;
		$updated        = update_post_meta( $product_id, $stock_meta_key, (string) wc_stock_amount( $new_stock ) );

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

		$product_id = $this->get_stock_managed_product_id( $product );
		if ( 0 === $product_id ) {
			return null;
		}

		$stock_meta_key = $this->get_required_location_stock_meta_key( $location_slug );
		$updated        = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = CAST( meta_value AS DECIMAL(19,6) ) - %f
				WHERE post_id = %d
				AND meta_key = %s
				AND CAST( meta_value AS DECIMAL(19,6) ) >= %f
				ORDER BY meta_id ASC
				LIMIT 1",
				$quantity,
				$product_id,
				$stock_meta_key,
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
	 * Get stock for a product ID at a configured location.
	 *
	 * @param int    $product_id     Product ID.
	 * @param string $location_slug  Location slug.
	 */
	private function get_location_stock_for_product_id( int $product_id, string $location_slug ): float {
		if ( $product_id <= 0 || ! $this->is_known_location_slug( $location_slug ) ) {
			return 0.0;
		}

		return wc_stock_amount( get_post_meta( $product_id, $this->get_location_stock_meta_key( $location_slug ), true ) );
	}

	/**
	 * Get the meta key for a location's product stock.
	 *
	 * @param string $location_slug Location slug.
	 */
	private function get_location_stock_meta_key( string $location_slug ): string {
		return self::LOCATION_STOCK_META_PREFIX . $this->normalize_location_slug( $location_slug );
	}

	/**
	 * Get a location stock meta key or fail for invalid writes.
	 *
	 * @param string $location_slug Location slug.
	 * @throws \InvalidArgumentException When the location does not exist.
	 */
	private function get_required_location_stock_meta_key( string $location_slug ): string {
		$location_slug = $this->normalize_location_slug( $location_slug );
		if ( $this->is_known_location_slug( $location_slug ) ) {
			return $this->get_location_stock_meta_key( $location_slug );
		}

		throw new \InvalidArgumentException( sprintf( 'Unknown inventory location: %s.', esc_html( $location_slug ) ) );
	}

	/**
	 * Get the product ID that owns stock for the supplied product.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 */
	private function get_stock_managed_product_id( $product ): int {
		$product_with_stock = $this->get_stock_managed_product( $product );

		return $product_with_stock ? (int) $product_with_stock->get_id() : 0;
	}

	/**
	 * Get the supplied product's own record ID.
	 *
	 * @param \WC_Product|int $product Product object or ID.
	 */
	private function get_product_record_id( $product ): int {
		$product = $product instanceof \WC_Product ? $product : wc_get_product( $product );

		return $product instanceof \WC_Product ? (int) $product->get_id() : 0;
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
	 * Get the default POS location seeded by the spike.
	 *
	 * @return array{slug:string,name:string,address_1:string,address_2:string,city:string,state:string,postcode:string,country:string}
	 */
	private function get_default_pos_location(): array {
		$country_state = explode( ':', (string) get_option( 'woocommerce_default_country', '' ), 2 );

		return array(
			'slug'      => self::LOCATION_POS,
			'name'      => __( 'POS', 'woocommerce' ),
			'address_1' => (string) get_option( 'woocommerce_store_address', '' ),
			'address_2' => (string) get_option( 'woocommerce_store_address_2', '' ),
			'city'      => (string) get_option( 'woocommerce_store_city', '' ),
			'state'     => (string) ( $country_state[1] ?? '' ),
			'postcode'  => (string) get_option( 'woocommerce_store_postcode', '' ),
			'country'   => (string) ( $country_state[0] ?? '' ),
		);
	}

	/**
	 * Normalize one location config item.
	 *
	 * @param array<string,mixed> $location Location data.
	 * @return array{slug:string,name:string,address_1:string,address_2:string,city:string,state:string,postcode:string,country:string}
	 */
	private function normalize_location( array $location ): array {
		$name = (string) wc_clean( $this->get_scalar_location_value( $location, 'name' ) );
		$slug = $this->normalize_location_slug( $this->get_scalar_location_value( $location, 'slug' ) );
		if ( '' === $slug && '' !== $name ) {
			$slug = $this->normalize_location_slug( $name );
		}

		return array(
			'slug'      => $slug,
			'name'      => '' === $name ? $slug : $name,
			'address_1' => (string) wc_clean( $this->get_scalar_location_value( $location, 'address_1' ) ),
			'address_2' => (string) wc_clean( $this->get_scalar_location_value( $location, 'address_2' ) ),
			'city'      => (string) wc_clean( $this->get_scalar_location_value( $location, 'city' ) ),
			'state'     => (string) wc_clean( $this->get_scalar_location_value( $location, 'state' ) ),
			'postcode'  => (string) wc_clean( $this->get_scalar_location_value( $location, 'postcode' ) ),
			'country'   => (string) wc_clean( $this->get_scalar_location_value( $location, 'country' ) ),
		);
	}

	/**
	 * Get a scalar location option value.
	 *
	 * @param array<string,mixed> $location Location data.
	 * @param string              $field    Field name.
	 */
	private function get_scalar_location_value( array $location, string $field ): string {
		return is_scalar( $location[ $field ] ?? null ) ? (string) $location[ $field ] : '';
	}

	/**
	 * Update product modified dates after a successful location stock write.
	 *
	 * @param \WC_Product|int $product      Product object or ID.
	 * @param int|bool        $rows_updated Rows updated by the stock query.
	 */
	private function touch_product_modified_date_after_stock_update( $product, $rows_updated ): void {
		if ( ( ! is_int( $rows_updated ) && ! is_bool( $rows_updated ) ) || ! $rows_updated ) {
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
