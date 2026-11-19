<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Shows regulatory fields in order emails, below the billing address.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class OrderEmails implements RegisterHooksInterface {

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
		add_action( 'woocommerce_email_customer_address_section', array( $this, 'handle_woocommerce_email_customer_address_section' ), 30, 4 );
	}

	/**
	 * Output the values below the billing address, in emails to the customer and to the merchant.
	 *
	 * @internal
	 *
	 * @param mixed $address_type  Address being shown: billing or shipping.
	 * @param mixed $order         Order the email is about.
	 * @param mixed $sent_to_admin Whether the email goes to the merchant.
	 * @param mixed $plain_text    Whether the email is plain text.
	 */
	public function handle_woocommerce_email_customer_address_section( $address_type, $order, $sent_to_admin = false, $plain_text = false ): void {
		if ( 'billing' !== $address_type || ! $order instanceof WC_Order ) {
			return;
		}

		foreach ( $this->regulatory_fields->get_order_display_values( $order ) as $field ) {
			if ( $plain_text ) {
				printf( "%s: %s\n", esc_html( $field['label'] ), esc_html( $field['value'] ) );
			} else {
				printf( '<br><strong>%s</strong>: %s', esc_html( $field['label'] ), esc_html( $field['value'] ) );
			}
		}
	}
}
