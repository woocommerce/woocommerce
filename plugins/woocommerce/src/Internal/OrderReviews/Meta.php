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
	 * empty-state views: the order number and date, preceded by the customer's name
	 * and billing email only when the viewer is signed in as that customer.
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

		$parts = self::viewer_is_signed_in_owner( $order )
			? array( $customer_name, $customer_email, $order_summary )
			: array( $order_summary );

		return array_values( array_filter( $parts ) );
	}

	/**
	 * Whether the viewer is signed in as the customer who owns the order.
	 *
	 * The billing email is the second proof the Store API order route and the
	 * order-received page ask for on top of the key, so the page must not print it to
	 * everyone holding one.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return bool
	 */
	private static function viewer_is_signed_in_owner( WC_Order $order ): bool {
		$customer_id = $order->get_customer_id();

		return $customer_id > 0 && is_user_logged_in() && get_current_user_id() === $customer_id;
	}
}
