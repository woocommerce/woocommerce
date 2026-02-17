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
 * Automatically expire nonces immediately for testing purposes.
 * It is used to check new requests use the nonce from the latest response.
 */
add_filter(
	'nonce_life',
	function () {
		return 0.001;
	}
);
