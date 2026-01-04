<?php
/**
 * Plugin Name: WooCommerce Blocks Test Lazy Load Variations
 * Description: Lowers the woocommerce_ajax_variation_threshold to trigger lazy loading with fewer variations.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-lazy-load-variations
 */

declare(strict_types=1);

/**
 * Lower the AJAX variation threshold to 3 so products with 4+ variations
 * will use lazy loading. This allows testing lazy load behavior with
 * existing test products like Hoodie (which has ~6 variations).
 */
add_filter(
	'woocommerce_ajax_variation_threshold',
	function () {
		return 3;
	}
);