<?php
/**
 * A class of utilities for dealing with orders.
 */

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use WC_Order;
use WP_Post;

/**
 * A class of utilities for dealing with orders.
 */
final class OrderUtil {

	/**
	 * Helper function to get screen name of orders page in wp-admin.
	 *
	 * This method is a proxy for get_order_admin_screen_from_instance function so that we can call it without going via DI container.
	 *
	 * @return string
	 */
	public static function get_order_admin_screen() : string {
		return wc_get_container()->get( PageController::class )->get_order_admin_screen();
	}

	/**
	 * Helper function to get screen name of orders page in wp-admin.
	 *
	 * @return string
	 */
	public function get_order_admin_screen_from_instance() : string {
		return self::custom_orders_table_usage_is_enabled()
			? \wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';
	}

	/**
	 * Helper function to get whether custom order tables are enabled or not.
	 *
	 * @return bool
	 */
	public static function custom_orders_table_usage_is_enabled() : bool {
		return wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();
	}

	/**
	 * Gets value of a meta key from WC_Data object if passed, otherwise from the post object.
	 * This helper function support backward compatibility for meta box functions, when moving from posts based store to custom tables.
	 *
	 * This method is a proxy for get_post_or_object_meta_from_instance function so that we can call it without going via DI container.
	 *
	 * @param WP_Post|null  $post Post object, meta will be fetched from this only when `$data` is not passed.
	 * @param \WC_Data|null $data WC_Data object, will be preferred over post object when passed.
	 * @param string        $key Key to fetch metadata for.
	 * @param bool          $single Whether metadata is single.
	 *
	 * @return array|mixed|string Value of the meta key.
	 */
	public static function get_post_or_object_meta( ?WP_Post $post, ?\WC_Data $data, string $key, bool $single ) {
		return wc_get_container()->get( self::class )->get_post_or_object_meta_from_instance( $post, $data, $key, $single );
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
	public function get_post_or_object_meta_from_instance( ?WP_Post $post, ?\WC_Data $data, string $key, bool $single ) {
		if ( isset( $data ) ) {
			if ( method_exists( $data, "get$key" ) ) {
				return $data->{"get$key"}();
			}
			return $data->get_meta( $key, $single );
		} else {
			return get_post_meta( $post->ID, $key, $single );
		}
	}

	/**
	 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
	 *
	 * This method is a proxy for init_theorder_object_from_instance function so that we can call it without going via DI container.
	 *
	 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
	 *
	 * @return WC_Order WC_Order object.
	 */
	public static function init_theorder_object( $post_or_order_object ) : WC_Order {
		return wc_get_container()->get( self::class )->init_theorder_object_from_instance( $post_or_order_object );
	}

	/**
	 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
	 *
	 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
	 *
	 * @return WC_Order WC_Order object.
	 */
	public function init_theorder_object_from_instance( $post_or_order_object ) : WC_Order {
		global $theorder;
		if ( $theorder instanceof WC_Order ) {
			return $theorder;
		}

		if ( $post_or_order_object instanceof WC_Order ) {
			$theorder = $post_or_order_object;
		} else {
			$theorder = wc_get_order( $post_or_order_object->ID );
		}
		return $theorder;
	}
}
