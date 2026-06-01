<?php
/**
 * Admin new withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-withdrawal-request.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'A customer has submitted a new withdrawal request for the following order:', 'woocommerce' ) . "\n\n";

echo esc_html__( 'Order number', 'woocommerce' ) . ': ' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Withdrawal reference', 'woocommerce' ) . ': ' . esc_html( $request_id ) . "\n";
echo esc_html__( 'Date of request', 'woocommerce' ) . ': ' . esc_html( ! empty( $request_date_created ) ? $request_date_created : wc_format_datetime( new WC_DateTime() ) ) . "\n";
echo esc_html__( 'Customer', 'woocommerce' ) . ': ' . esc_html( $order->get_formatted_billing_full_name() ) . ' (' . esc_html( $order->get_billing_email() ) . ")\n\n";

/* translators: %s: admin edit order URL */
echo sprintf( esc_html__( 'Review and process: %s', 'woocommerce' ), esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' ) ) ) . "\n\n";

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

if ( $additional_content ) {
	echo "\n\n----------------------------------------\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
}
