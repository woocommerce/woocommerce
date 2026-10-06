<?php
/**
 * ReadyForPickupStatus class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use WC_Cache_Helper;
use WC_Order;
use WC_Order_Item;

/**
 * Adds the "Ready for pickup" order status on stores that offer local pickup.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
class ReadyForPickupStatus implements RegisterHooksInterface {

	/**
	 * The status as it is stored in the database.
	 */
	public const DB_STATUS = 'wc-ready-for-pickup';

	/**
	 * Option that records whether an order has ever been given the status.
	 */
	public const USED_OPTION = 'woocommerce_ready_for_pickup_status_used';

	/**
	 * ID of the pickup locations shipping method.
	 */
	private const PICKUP_LOCATION_METHOD_ID = 'pickup_location';

	/**
	 * Transient that caches whether a shipping zone has an enabled Local pickup method.
	 */
	private const ZONE_PICKUP_TRANSIENT = 'wc_zone_local_pickup_enabled';

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_filter( 'wc_order_statuses', array( $this, 'handle_wc_order_statuses' ) );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'handle_woocommerce_register_shop_order_post_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'add_status_where_processing_is_listed' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_status_where_processing_is_listed' ) );
		add_filter( 'woocommerce_purchase_note_order_statuses', array( $this, 'add_status_where_processing_is_listed' ) );
		add_action( 'woocommerce_order_status_' . OrderStatus::READY_FOR_PICKUP, array( $this, 'handle_woocommerce_order_status_ready_for_pickup' ) );
		add_action( 'woocommerce_shipping_zone_method_added', array( $this, 'clear_zone_local_pickup_cache' ) );
		add_action( 'woocommerce_shipping_zone_method_deleted', array( $this, 'clear_zone_local_pickup_cache' ) );
		add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'clear_zone_local_pickup_cache' ) );
	}

	/**
	 * Whether the status is available on this store.
	 *
	 * It is available while local pickup is offered, either as pickup locations or as a Local pickup
	 * method in a shipping zone. Once an order has used the status it stays available, because an
	 * order whose status is no longer registered drops out of the orders list and is reset to
	 * pending payment the next time it is saved.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = LocalPickupUtils::is_local_pickup_enabled() || $this->is_zone_local_pickup_enabled() || $this->has_been_used();

		/**
		 * Filters whether the "Ready for pickup" order status is available.
		 *
		 * Extensions that provide their own pickup shipping method can return true to turn the status on.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $enabled Whether the status is available.
		 */
		return (bool) apply_filters( 'woocommerce_ready_for_pickup_order_status_enabled', $enabled );
	}

	/**
	 * Whether the order has a local pickup shipping method.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order $order The order.
	 * @return bool
	 */
	public function order_has_local_pickup( WC_Order $order ): bool {
		return count( $this->get_pickup_locations( $order ) ) > 0;
	}

	/**
	 * Get the pickup locations the customer chose for an order.
	 *
	 * Pickup locations carry a name, an address and pickup details. Other local pickup methods only
	 * have a title, which is returned as the name.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order $order The order.
	 * @return array<int, array{name: string, address: string, details: string}>
	 */
	public function get_pickup_locations( WC_Order $order ): array {
		$locations = array();

		foreach ( $order->get_shipping_methods() as $shipping_item ) {
			if ( ! $this->is_local_pickup_method( (string) $shipping_item->get_method_id() ) ) {
				continue;
			}

			$name = self::get_string_meta( $shipping_item, 'pickup_location' );

			$locations[] = array(
				'name'    => '' !== $name ? $name : (string) $shipping_item->get_name(),
				'address' => self::get_string_meta( $shipping_item, 'pickup_address' ),
				'details' => self::get_string_meta( $shipping_item, 'pickup_details' ),
			);
		}

		return $locations;
	}

	/**
	 * Add the status to the order statuses, after "Processing".
	 *
	 * @internal
	 *
	 * @param array $statuses Order statuses, keyed by their database value.
	 * @return array
	 */
	public function handle_wc_order_statuses( $statuses ) {
		if ( ! is_array( $statuses ) || isset( $statuses[ self::DB_STATUS ] ) || ! $this->is_enabled() ) {
			return $statuses;
		}

		$status   = array( self::DB_STATUS => _x( 'Ready for pickup', 'Order status', 'woocommerce' ) );
		$position = array_search( OrderInternalStatus::PROCESSING, array_keys( $statuses ), true );

		if ( false === $position ) {
			return $statuses + $status;
		}

		return array_slice( $statuses, 0, $position + 1, true ) + $status + array_slice( $statuses, $position + 1, null, true );
	}

	/**
	 * Register the post status.
	 *
	 * It is registered on every store so that existing orders can always be queried by it. The
	 * `wc_order_statuses` filter decides whether the status is offered.
	 *
	 * @internal
	 *
	 * @param array $statuses Post status properties, keyed by post status.
	 * @return array
	 */
	public function handle_woocommerce_register_shop_order_post_statuses( $statuses ) {
		if ( ! is_array( $statuses ) ) {
			return $statuses;
		}

		$statuses[ self::DB_STATUS ] = array(
			'label'                     => _x( 'Ready for pickup', 'Order status', 'woocommerce' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Ready for pickup <span class="count">(%s)</span>', 'Ready for pickup <span class="count">(%s)</span>', 'woocommerce' ),
		);

		return $statuses;
	}

	/**
	 * Add the status to a list of statuses that includes "Processing".
	 *
	 * An order that is ready for pickup has been paid for and had its stock reduced, the same as a
	 * processing order, so it belongs in the same lists.
	 *
	 * @internal
	 *
	 * @param array $statuses Order statuses, without the `wc-` prefix.
	 * @return array
	 */
	public function add_status_where_processing_is_listed( $statuses ) {
		if (
			! is_array( $statuses )
			|| ! in_array( OrderStatus::PROCESSING, $statuses, true )
			|| in_array( OrderStatus::READY_FOR_PICKUP, $statuses, true )
			|| ! $this->is_enabled()
		) {
			return $statuses;
		}

		$statuses[] = OrderStatus::READY_FOR_PICKUP;

		return $statuses;
	}

	/**
	 * Record that the status has been used, so it stays available.
	 *
	 * @internal
	 */
	public function handle_woocommerce_order_status_ready_for_pickup(): void {
		update_option( self::USED_OPTION, 'yes', true );
	}

	/**
	 * Forget whether a shipping zone has an enabled Local pickup method.
	 *
	 * Not every change to a zone method refreshes the shipping cache version, so the cached answer
	 * is also cleared when a method is added, deleted, enabled or disabled.
	 *
	 * @internal
	 */
	public function clear_zone_local_pickup_cache(): void {
		delete_transient( self::ZONE_PICKUP_TRANSIENT );
	}

	/**
	 * Whether an order has ever been given the status.
	 *
	 * @return bool
	 */
	private function has_been_used(): bool {
		$used = get_option( self::USED_OPTION );

		if ( false === $used ) {
			// Store the default so the option is autoloaded, not looked up on every request.
			add_option( self::USED_OPTION, 'no', '', true );
		}

		return 'yes' === $used;
	}

	/**
	 * Whether any shipping zone has an enabled Local pickup method.
	 *
	 * The answer is cached until shipping zones or their methods change.
	 *
	 * @return bool
	 */
	private function is_zone_local_pickup_enabled(): bool {
		global $wpdb;

		$version = WC_Cache_Helper::get_transient_version( 'shipping' );
		$cached  = get_transient( self::ZONE_PICKUP_TRANSIENT );

		if ( is_array( $cached ) && isset( $cached['version'], $cached['enabled'] ) && $cached['version'] === $version ) {
			return 'yes' === $cached['enabled'];
		}

		// The table is missing until WooCommerce has been installed on the site.
		$suppress_errors = $wpdb->suppress_errors();
		$enabled         = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = %s AND is_enabled = 1 LIMIT 1",
				'local_pickup'
			)
		);
		$query_failed    = '' !== $wpdb->last_error;
		$wpdb->suppress_errors( $suppress_errors );

		if ( $query_failed ) {
			return false;
		}

		set_transient(
			self::ZONE_PICKUP_TRANSIENT,
			array(
				'version' => $version,
				'enabled' => $enabled ? 'yes' : 'no',
			)
		);

		return $enabled;
	}

	/**
	 * Whether a shipping method ID belongs to a local pickup method.
	 *
	 * The pickup locations method is only loaded while the Checkout block is in use, so it is matched
	 * by its ID to keep recognizing the orders that were placed with it.
	 *
	 * @param string $method_id The shipping method ID.
	 * @return bool
	 */
	private function is_local_pickup_method( string $method_id ): bool {
		return self::PICKUP_LOCATION_METHOD_ID === $method_id || LocalPickupUtils::is_local_pickup_method( $method_id );
	}

	/**
	 * Read a meta value from an order item as a string.
	 *
	 * @param WC_Order_Item $item The order item.
	 * @param string        $key  The meta key.
	 * @return string
	 */
	private static function get_string_meta( WC_Order_Item $item, string $key ): string {
		$value = $item->get_meta( $key );

		return is_string( $value ) ? $value : '';
	}
}
