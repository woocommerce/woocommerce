<?php
/**
 * Plugin Name: WooCommerce Blocks Test Custom Add to Cart Button Text
 * Description: Modifies the "Add to Cart" button text for WooCommerce products.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-custom-add-to-cart-button-text
 */

function woocommerce_add_to_cart_button_text_archives() {
	return 'Buy Now';
}

add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_add_to_cart_button_text_archives' );
