<?php
/**
 * A class of utilities for dealing with orders.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Caches\OrderCacheController;
use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Utilities\COTMigrationUtil;
use WC_Order;
use WP_Post;

/**
 * A class of utilities for dealing with orders.
 */
final class OrderUtil {

	/**
	 * Helper function to get screen name of orders page in wp-admin.
	 *
	 * @return string
	 */
	public static function get_order_admin_screen(): string {
		return wc_get_container()->get( COTMigrationUtil::class )->get_order_admin_screen();
	}


	/**
	 * Helper function to get whether custom order tables are enabled or not.
	 *
	 * @return bool
	 */
	public static function custom_orders_table_usage_is_enabled(): bool {
		return wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();
	}

	/**
	 * Helper function to get whether custom order tables are enabled or not.
	 *
	 * @return bool
	 */
	public static function custom_orders_table_datastore_cache_enabled(): bool {
		return wc_get_container()->get( CustomOrdersTableController::class )->hpos_data_caching_is_enabled();
	}

	/**
	 * Helper function to get whether the orders cache should be used or not.
	 *
	 * @return bool True if the orders cache should be used, false otherwise.
	 */
	public static function orders_cache_usage_is_enabled(): bool {
		return wc_get_container()->get( OrderCacheController::class )->orders_cache_usage_is_enabled();
	}

	/**
	 * Checks if posts and order custom table sync is enabled and there are no pending orders.
	 *
	 * @return bool
	 */
	public static function is_custom_order_tables_in_sync(): bool {
		return wc_get_container()->get( COTMigrationUtil::class )->is_custom_order_tables_in_sync();
	}

	/**
	 * Gets value of a meta key from WC_Data object if passed, otherwise from the post object.
	 * This helper function support backward compatibility for meta box functions, when moving from posts based store to custom tables.
	 *
	 * @param WP_Post|null  $post Post object, meta will be fetched from this only when `$data` is not passed.
	 * @param \WC_Data|null $data WC_Data object, will be preferred over post object when passed.
	 * @param string        $key Key to fetch metadata for.
	 * @param bool          $single Whether metadata is single.
	 *
	 * @return array|mixed|string Value of the meta key.
	 */
	public static function get_post_or_object_meta( ?WP_Post $post, ?\WC_Data $data, string $key, bool $single ) {
		return wc_get_container()->get( COTMigrationUtil::class )->get_post_or_object_meta( $post, $data, $key, $single );
	}

	/**
	 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
	 *
	 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
	 *
	 * @return bool|WC_Order|WC_Order_Refund WC_Order object.
	 */
	public static function init_theorder_object( $post_or_order_object ) {
		return wc_get_container()->get( COTMigrationUtil::class )->init_theorder_object( $post_or_order_object );
	}

	/**
	 * Helper function to id from an post or order object.
	 *
	 * @param WP_Post/WC_Order $post_or_order_object WP_Post/WC_Order object to get ID for.
	 *
	 * @return int Order or post ID.
	 */
	public static function get_post_or_order_id( $post_or_order_object ): int {
		return wc_get_container()->get( COTMigrationUtil::class )->get_post_or_order_id( $post_or_order_object );
	}

	/**
	 * Checks if passed id, post or order object is a WC_Order object.
	 *
	 * @param int|WP_Post|WC_Order $order_id Order ID, post object or order object.
	 * @param string[]             $types    Types to match against.
	 *
	 * @return bool Whether the passed param is an order.
	 */
	public static function is_order( $order_id, $types = array( 'shop_order' ) ) {
		return wc_get_container()->get( COTMigrationUtil::class )->is_order( $order_id, $types );
	}

	/**
	 * Returns type pf passed id, post or order object.
	 *
	 * @param int|WP_Post|WC_Order $order_id Order ID, post object or order object.
	 *
	 * @return string|null Type of the order.
	 */
	public static function get_order_type( $order_id ) {
		return wc_get_container()->get( COTMigrationUtil::class )->get_order_type( $order_id );
	}

	/**
	 * Helper method to generate admin url for an order.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return string Admin url for an order.
	 */
	public static function get_order_admin_edit_url( int $order_id ): string {
		return wc_get_container()->get( PageController::class )->get_edit_url( $order_id );
	}

	/**
	 * Helper method to generate admin URL for new order.
	 *
	 * @return string Link for new order.
	 */
	public static function get_order_admin_new_url(): string {
		return wc_get_container()->get( PageController::class )->get_new_page_url();
	}

	/**
	 * Check if the current admin screen is an order list table.
	 *
	 * @param string $order_type Optional. The order type to check for. Default shop_order.
	 *
	 * @return bool
	 */
	public static function is_order_list_table_screen( $order_type = 'shop_order' ): bool {
		return wc_get_container()->get( PageController::class )->is_order_screen( $order_type, 'list' );
	}

	/**
	 * Check if the current admin screen is for editing an order.
	 *
	 * @param string $order_type Optional. The order type to check for. Default shop_order.
	 *
	 * @return bool
	 */
	public static function is_order_edit_screen( $order_type = 'shop_order' ): bool {
		return wc_get_container()->get( PageController::class )->is_order_screen( $order_type, 'edit' );
	}

	/**
	 * Check if the current admin screen is adding a new order.
	 *
	 * @param string $order_type Optional. The order type to check for. Default shop_order.
	 *
	 * @return bool
	 */
	public static function is_new_order_screen( $order_type = 'shop_order' ): bool {
		return wc_get_container()->get( PageController::class )->is_order_screen( $order_type, 'new' );
	}

	/**
	 * Get the name of the database table that's currently in use for orders.
	 *
	 * @return string
	 */
	public static function get_table_for_orders() {
		return wc_get_container()->get( COTMigrationUtil::class )->get_table_for_orders();
	}

	/**
	 * Get the name of the database table that's currently in use for orders.
	 *
	 * @return string
	 */
	public static function get_table_for_order_meta() {
		return wc_get_container()->get( COTMigrationUtil::class )->get_table_for_order_meta();
	}

	/**
	 * Counts number of orders of a given type.
	 *
	 * @since 8.7.0
	 *
	 * @param string $order_type Order type.
	 * @return array<string,int> Array of order counts indexed by order type.
	 */
	public static function get_count_for_type( $order_type ) {
		global $wpdb;

		$order_type = (string) $order_type;

		$order_count_cache = new OrderCountCache();
		$count_per_status  = $order_count_cache->get( $order_type );

		if ( null === $count_per_status ) {
			if ( self::custom_orders_table_usage_is_enabled() ) {
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT `status`, COUNT(*) AS `count` FROM ' . self::get_table_for_orders() . ' WHERE `type` = %s GROUP BY `status`',
						$order_type
					),
					ARRAY_A
				);
				// phpcs:enable

				$count_per_status = array_map( 'absint', array_column( $results, 'count', 'status' ) );

			} else {
				$count_per_status = (array) wp_count_posts( $order_type );
			}

			// Make sure all order statuses are included just in case.
			$count_per_status = array_merge(
				array_fill_keys( array_merge( array_keys( wc_get_order_statuses() ), array( OrderStatus::TRASH ) ), 0 ),
				$count_per_status
			);

			$order_count_cache->set_multiple( $order_type, $count_per_status );
		}

		return $count_per_status;
	}

	/**
	 * Removes the 'wc-' prefix from status.
	 *
	 * @param string $status The status to remove the prefix from.
	 *
	 * @return string The status without the prefix.
	 * @since 9.2.0
	 */
	public static function remove_status_prefix( string $status ): string {
		if ( strpos( $status, 'wc-' ) === 0 ) {
			$status = substr( $status, 3 );
		}

		return $status;
	}

	/**
	 * Checks if the new full refund data is used.
	 *
	 * @return bool
	 */
	public static function uses_new_full_refund_data() {
		$db_version                = get_option( 'woocommerce_db_version', null );
		$uses_old_full_refund_data = get_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );
		if ( null === $db_version ) {
			return 'no' === $uses_old_full_refund_data;
		}
		return version_compare( $db_version, '10.2.0', '>=' ) && 'no' === $uses_old_full_refund_data;
	}

	/**
	 * Checks if the data store currently in use for orders is unknown (none of the ones managed by WooCommerce core).
	 *
	 * @return bool True if the data store currently in use for orders is neither the HPOS one nor the CPT one.
	 */
	public static function unknown_orders_data_store_in_use(): bool {
		return ! self::custom_orders_table_usage_is_enabled() &&
			( \WC_Order_Data_Store_CPT::class !== \WC_Data_Store::load( 'order' )->get_current_class_name() );
	}
}
