<?php
/**
 * WooCommerce Checkout Hook Implementation
 *
 * @package WooCommerce
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Checkout_Hook Class.
 */
class WC_Checkout_Hook {

	/**
	 * Initialize the hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_review_order_after_cart_item_meta', array( __CLASS__, 'cart_item_meta_hook' ), 10, 3 );
	}

	/**
	 * Cart item meta hook for classic checkout.
	 *
	 * @param WC_Product $_product     Product object.
	 * @param array        $cart_item  Cart item.
	 * @param string       $cart_item_key Cart item key.
	 */
	public static function cart_item_meta_hook( $_product, $cart_item, $cart_item_key ) {
		do_action( 'woocommerce_review_order_after_cart_item_meta', $_product, $cart_item, $cart_item_key );
	}
}

// Initialize the hooks.
WC_Checkout_Hook::init();
