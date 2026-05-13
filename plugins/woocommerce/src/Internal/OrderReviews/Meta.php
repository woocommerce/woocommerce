<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use WC_Order;

/**
 * Shared meta-line helpers for Review Order templates.
 */
class Meta {

	/**
	 * Build the meta-line parts shown above the heading on both the form and
	 * empty-state views (customer name, billing email, order #/date).
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return array<int, string> Non-empty parts ready to be joined with a separator.
	 */
	public static function parts_for_order( WC_Order $order ): array {
		$date_created    = $order->get_date_created();
		$customer_name   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_email  = $order->get_billing_email();
		$order_number    = $order->get_order_number();
		$order_date_text = $date_created ? wc_format_datetime( $date_created ) : '';

		if ( '' !== $order_date_text ) {
			$order_summary = sprintf(
				/* translators: 1: order number, 2: order date */
				__( 'Order #%1$s (%2$s)', 'woocommerce' ),
				$order_number,
				$order_date_text
			);
		} else {
			$order_summary = sprintf(
				/* translators: %s: order number */
				__( 'Order #%s', 'woocommerce' ),
				$order_number
			);
		}

		return array_values(
			array_filter(
				array(
					$customer_name,
					$customer_email,
					$order_summary,
				)
			)
		);
	}
}
