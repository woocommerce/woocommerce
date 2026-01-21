<?php
/**
 * Plugin Name: WooCommerce Blocks Test Short Nonce Life
 * Description: Sets a very short nonce lifetime for testing nonce expiry scenarios.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-short-nonce-life
 */

/**
 * Set nonce lifetime to 4 seconds.
 *
 * WordPress nonces are valid for one "tick" (nonce_life / 2), so with a 4 second
 * nonce_life, nonces will be valid for approximately 2 seconds.
 *
 * @return int The nonce lifetime in seconds.
 */
function wc_test_short_nonce_life() {
	return 4;
}
add_filter( 'nonce_life', 'wc_test_short_nonce_life' );
