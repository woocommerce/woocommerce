<?php
/**
 * Plugin Name: WooCommerce Blocks Test Cart Contents Count Filter
 * Description: Overrides the cart contents count to always return 999 for e2e testing.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-cart-contents-count-filter
 */

declare( strict_types = 1 );

add_filter(
	'woocommerce_cart_contents_count',
	function () {
		return 999;
	}
);
