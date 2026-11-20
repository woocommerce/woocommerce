<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Shows regulatory fields in order emails, in their own section after the addresses.
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
		// Priority 25 puts the section after the addresses (20) and above "Additional information" (30).
		add_action( 'woocommerce_email_customer_details', array( $this, 'handle_woocommerce_email_customer_details' ), 25, 3 );
	}

	/**
	 * Output the section in emails to the customer and to the merchant.
	 *
	 * @internal
	 *
	 * @param mixed $order         Order the email is about.
	 * @param mixed $sent_to_admin Whether the email goes to the merchant.
	 * @param mixed $plain_text    Whether the email is plain text.
	 */
	public function handle_woocommerce_email_customer_details( $order, $sent_to_admin = false, $plain_text = false ): void {
		$fields = $order instanceof WC_Order ? $this->regulatory_fields->get_order_display_values( $order ) : array();

		if ( empty( $fields ) ) {
			return;
		}

		$heading = __( 'Additional required information', 'woocommerce' );

		if ( $plain_text ) {
			echo "\n" . esc_html( wc_strtoupper( $heading ) ) . "\n\n";
			foreach ( $fields as $field ) {
				printf( "%s: %s\n", esc_html( $field['label'] ), esc_html( $field['value'] ) );
			}
			return;
		}

		echo '<h2>' . esc_html( $heading ) . '</h2>';
		echo '<ul class="additional-fields" style="margin-bottom: 40px;">';
		foreach ( $fields as $field ) {
			printf( '<li><strong>%s</strong>: %s</li>', esc_html( $field['label'] ), esc_html( $field['value'] ) );
		}
		echo '</ul>';
	}
}
