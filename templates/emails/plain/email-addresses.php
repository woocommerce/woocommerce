<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n" . esc_html( wc_strtoupper( __( 'Billing address', 'woocommerce' ) ) ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $order->get_formatted_billing_address() ) . "\n"; // WPCS: XSS ok.

if ( $order->get_billing_phone() ) {
	echo $order->get_billing_phone() . "\n"; // WPCS: XSS ok.
}

if ( $order->get_billing_email() ) {
	echo $order->get_billing_email() . "\n"; // WPCS: XSS ok.
}

if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() ) {
	$shipping = $order->get_formatted_shipping_address();

	if ( $shipping ) {
		echo "\n" . esc_html( wc_strtoupper( __( 'Shipping address', 'woocommerce' ) ) ) . "\n\n";
		echo preg_replace( '#<br\s*/?>#i', "\n", $shipping ) . "\n"; // WPCS: XSS ok.
	}
}
