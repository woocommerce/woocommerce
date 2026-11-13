<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use WC_Order;

/**
 * Shared meta-line helpers for Review Order templates.
 */
class Meta {

	/**
	 * The meta line shown above the heading, parts joined by the separator.
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return string
	 */
	public static function line_for_order( WC_Order $order ): string {
		return implode( self::separator(), self::parts_for_order( $order ) );
	}

	/**
	 * The meta line as escaped HTML, with the billing email in `<code>` so wptexturize() leaves it unchanged.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return string
	 */
	public static function line_html_for_order( WC_Order $order ): string {
		$parts = array_map( 'esc_html', self::keyed_parts_for_order( $order ) );

		if ( isset( $parts['email'] ) ) {
			$parts['email'] = '<code class="woocommerce-email">' . $parts['email'] . '</code>';
		}

		return implode( esc_html( self::separator() ), $parts );
	}

	/**
	 * Build the meta-line parts shown above the heading on both the form and
	 * empty-state views (customer name, billing email, order #/date).
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return array<int, string> Non-empty parts ready to be joined with a separator.
	 */
	public static function parts_for_order( WC_Order $order ): array {
		return array_values( self::keyed_parts_for_order( $order ) );
	}

	/**
	 * Separator between the meta-line parts.
	 *
	 * @return string
	 */
	private static function separator(): string {
		// Spaces inside the string: a locale that needs a full-width
		// separator has to be able to drop them.
		return _x( ' · ', 'Review Order meta-line separator', 'woocommerce' );
	}

	/**
	 * The non-empty meta-line parts, keyed by `name`, `email` and `summary`.
	 *
	 * @param WC_Order $order Order being reviewed.
	 * @return array<string, string>
	 */
	private static function keyed_parts_for_order( WC_Order $order ): array {
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

		return array_filter(
			array(
				'name'    => $customer_name,
				'email'   => $customer_email,
				'summary' => $order_summary,
			)
		);
	}
}
