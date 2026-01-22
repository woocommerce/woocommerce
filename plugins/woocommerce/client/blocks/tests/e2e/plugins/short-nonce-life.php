<?php
/**
 * Plugin Name: WooCommerce Blocks Test Short Nonce Life
 * Description: Sets a very short nonce lifetime for testing nonce expiry scenarios.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-short-nonce-life
 */

declare( strict_types=1 );

/**
 * Set nonce lifetime to 2 seconds.
 */
add_filter(
	'nonce_life',
	function () {
		return 2;
	}
);
