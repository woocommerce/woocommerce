<?php
/**
 * Plugin Name: Custom Add to Cart Text
 * Description: Modifies the "Add to Cart" button text for WooCommerce products.
 * @package    WordPress
 */

/**
 * Modifies the "Add to Cart" button text
 *
 *
 * @return string The new text.
 */
function woocommerce_add_to_cart_button_text_archives() {
	return 'Buy Now';
}

add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_add_to_cart_button_text_archives' );
