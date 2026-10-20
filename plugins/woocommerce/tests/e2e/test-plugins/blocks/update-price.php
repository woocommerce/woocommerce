<?php
/**
 * Plugin Name: WooCommerce Blocks Test Update Price
 * Description: Update price of products.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-update-price
 */

// Only requests from specs that enable this plugin send the cookie.
if ( ! isset( $_COOKIE['woocommerce-blocks-test-update-price'] ) ) {
	return;
}

function calc_price( $cart_object ) {
	foreach ( $cart_object->get_cart() as $hash => $value ) {
		$value['data']->set_price( 50 );
	}
}

add_action( 'woocommerce_before_calculate_totals', 'calc_price' );
