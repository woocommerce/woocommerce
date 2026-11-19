<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Shows regulatory fields to the customer, below the billing address, on the order confirmation and in My Account.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class OrderDetails implements RegisterHooksInterface {

	/**
	 * The regulatory fields service.
	 *
	 * @var RegulatoryFields
	 */
	private RegulatoryFields $regulatory_fields;

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param RegulatoryFields $regulatory_fields The regulatory fields service.
	 */
	final public function init( RegulatoryFields $regulatory_fields ): void {
		$this->regulatory_fields = $regulatory_fields;
	}

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'woocommerce_order_details_after_customer_address', array( $this, 'handle_woocommerce_order_details_after_customer_address' ), 10, 2 );
	}

	/**
	 * Output the values below the billing address on the shortcode order confirmation and in My Account.
	 *
	 * @internal
	 *
	 * @param mixed $address_type Address being shown: billing or shipping.
	 * @param mixed $order        Order being shown.
	 */
	public function handle_woocommerce_order_details_after_customer_address( $address_type, $order ): void {
		if ( 'billing' !== $address_type || ! $order instanceof WC_Order ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in get_html().
		echo $this->get_html( $order );
	}

	/**
	 * Build the list of labels and values for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string HTML, or an empty string when the order has no values.
	 */
	public function get_html( WC_Order $order ): string {
		$items = '';

		foreach ( $this->regulatory_fields->get_order_display_values( $order ) as $field ) {
			$items .= sprintf( '<dt>%1$s</dt><dd>%2$s</dd>', esc_html( $field['label'] ), esc_html( $field['value'] ) );
		}

		return '' === $items ? '' : '<dl class="wc-block-components-additional-fields-list">' . $items . '</dl>';
	}
}
