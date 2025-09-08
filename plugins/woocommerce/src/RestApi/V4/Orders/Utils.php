<?php
/**
 * Utils class.
 *
 * @package WooCommerce\RestApi
 */

namespace Automattic\WooCommerce\RestApi\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use WP_REST_Request;
use WC_REST_Exception;
use WC_Order;

/**
 * Utils class.
 */
class Utils {
	/**
	 * Helper to determine if the current user has permission to access an item. Read access is always checked in addition to the permissions passed in.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @param  string          $permission The permissions to check e.g. read, edit, delete.
	 * @return boolean
	 */
	public static function check_permissions( $request, $permission = 'read' ) {
		$object = wc_get_order( (int) $request['id'] );

		if ( ! $object || ! $object instanceof WC_Order ) {
			return false;
		}

		if ( ! wc_rest_check_post_permissions( 'shop_order', 'read', $object->get_id() ) ) {
			return false;
		}

		if ( 'read' !== $permission ) {
			return wc_rest_check_post_permissions( 'shop_order', 'read', $object->get_id() );
		}

		return true;
	}

	/**
	 * Get order statuses without prefixes.
	 *
	 * @return array
	 */
	public static function get_order_statuses() {
		$order_statuses = array( OrderStatus::AUTO_DRAFT );

		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			$order_statuses[] = str_replace( 'wc-', '', $status );
		}

		return $order_statuses;
	}
}
