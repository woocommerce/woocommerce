<?php
/**
 * PointOfSaleOrderUtil class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use WC_Abstract_Order;

/**
 * Helper class for POS order related functionality.
 *
 * @internal Just for internal use.
 */
class PointOfSaleOrderUtil {
	/**
	 * Check if the order is a POS (Point of Sale) order.
	 *
	 * This method determines if an order was created via the POS REST API
	 * by checking the 'created_via' property of the order.
	 *
	 * @param WC_Abstract_Order $order Order instance.
	 * @return bool True if the order is a POS order, false otherwise.
	 */
	public static function is_pos_order( WC_Abstract_Order $order ): bool {
		return 'pos-rest-api' === $order->get_created_via();
	}
}
