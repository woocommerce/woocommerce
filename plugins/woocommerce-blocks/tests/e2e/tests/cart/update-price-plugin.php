<?php
/**
 * Plugin Name: Update Price
 * Description: Update price of products
 * @package     WordPress
 */

/**
 * Update price of products
 *
 * @param object $cart_object Cart object.
 */
function calc_price( $cart_object ) {
	foreach ( $cart_object->get_cart() as $hash => $value ) {
		$value['data']->set_price( 50 );
	}
}

add_action( 'woocommerce_before_calculate_totals', 'calc_price' );
