<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Shows regulatory fields to the customer, in their own section after the addresses, on the order confirmation and in My Account.
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
		// Priority 5 puts the section above "Additional information", which core adds at 10.
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'handle_woocommerce_order_details_after_customer_details' ), 5 );
	}

	/**
	 * Output the section after the addresses on the shortcode order confirmation and in My Account.
	 *
	 * @internal
	 *
	 * @param mixed $order Order being shown.
	 */
	public function handle_woocommerce_order_details_after_customer_details( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in get_section_html().
		echo $this->get_section_html( $order );
	}

	/**
	 * Build the section that lists an order's regulatory fields: a heading and the labels and values.
	 *
	 * @param WC_Order $order Order.
	 * @return string HTML, or an empty string when the order has no values.
	 */
	public function get_section_html( WC_Order $order ): string {
		$items = '';

		foreach ( $this->regulatory_fields->get_order_display_values( $order ) as $field ) {
			$items .= sprintf( '<dt>%1$s</dt><dd>%2$s</dd>', esc_html( $field['label'] ), esc_html( $field['value'] ) );
		}

		if ( '' === $items ) {
			return '';
		}

		return '<section class="wc-block-order-confirmation-regulatory-fields">'
			. '<h2 class="wp-block-heading">' . esc_html__( 'Additional required information', 'woocommerce' ) . '</h2>'
			. '<dl class="wc-block-components-additional-fields-list">' . $items . '</dl>'
			. '</section>';
	}
}
