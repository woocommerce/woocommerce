<?php
/**
 * Customer withdrawal status email (plain text)
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_label = 'approved' === $new_status
	? __( 'approved', 'woocommerce' )
	: __( 'rejected', 'woocommerce' );

echo '= ' . esc_html( $email_heading ) . " =\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ) . "\n\n", esc_html( $order->get_billing_first_name() ) );
}

/* translators: 1: order number, 2: status (approved/rejected) */
printf( esc_html__( 'Your withdrawal request for order %1$s has been %2$s by the store.', 'woocommerce' ) . "\n\n", esc_html( $order->get_order_number() ), esc_html( $status_label ) );

if ( ! empty( $admin_notes ) ) {
	echo esc_html__( 'Notes from the store', 'woocommerce' ) . ":\n";
	echo wp_strip_all_tags( $admin_notes ) . "\n\n";
}

echo "----------------------------------------\n\n";

echo esc_html__( 'Order number', 'woocommerce' ) . ': ' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Withdrawal reference', 'woocommerce' ) . ': ' . esc_html( $request_id ) . "\n";
echo esc_html__( 'Status', 'woocommerce' ) . ': ' . esc_html( $status_label ) . "\n";

echo "\n----------------------------------------\n\n";

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo "\n" . wp_strip_all_tags( $additional_content ) . "\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
