<?php
/**
 * Customer refunded order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . $email_heading . " =\n\n"; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

/* translators: %s: Customer first name */
echo sprintf( __( 'Hi %s,', 'woocommerce' ), $order->get_billing_first_name() ) . "\n\n"; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
if ( $partial_refund ) {
	/* translators: %s: Site title */
	echo sprintf( __( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) . "\n\n"; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
} else {
	/* translators: %s: Site title */
	echo sprintf( __( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) . "\n\n"; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
}
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo esc_html__( 'We hope to see you again soon.', 'woocommerce' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
