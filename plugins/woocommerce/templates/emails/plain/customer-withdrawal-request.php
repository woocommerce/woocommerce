<?php
/**
 * Customer withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-withdrawal-request.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
$first_name = $order->get_billing_first_name();
if ( '' === $first_name ) {
	echo esc_html__( 'Hi,', 'woocommerce' ) . "\n\n";
} else {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $first_name ) ) . "\n\n";
}
echo esc_html__( 'We have received your withdrawal request. Here is a summary:', 'woocommerce' ) . "\n\n";

echo esc_html__( 'Order number', 'woocommerce' ) . ': ' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Withdrawal reference', 'woocommerce' ) . ': ' . esc_html( $request_id ) . "\n";
echo esc_html__( 'Date of request', 'woocommerce' ) . ': ' . esc_html( current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . "\n";
echo esc_html__( 'Status', 'woocommerce' ) . ': ' . esc_html__( 'Pending review', 'woocommerce' ) . "\n\n";

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}
