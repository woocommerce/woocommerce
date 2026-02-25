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

	/**
	 * Check if the order was paid at POS, regardless of where it was created.
	 *
	 * An order is considered paid at POS if:
	 * - It was created via the POS REST API, OR
	 * - It was paid via card terminal (_wcpay_ipp_channel = mobile_pos), OR
	 * - It was paid via cash at POS (_cash_change_amount meta present).
	 *
	 * @param WC_Abstract_Order $order Order instance.
	 * @return bool
	 *
	 * @since 10.6.0
	 */
	public static function is_order_paid_at_pos( WC_Abstract_Order $order ): bool {
		if ( self::is_pos_order( $order ) ) {
			return true;
		}

		if ( 'mobile_pos' === $order->get_meta( '_wcpay_ipp_channel' ) ) {
			return true;
		}

		if ( '' !== $order->get_meta( '_cash_change_amount' ) ) {
			return true;
		}

		return false;
	}
}
