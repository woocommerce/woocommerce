<?php
/**
 * Plugin Name: WooCommerce Blocks Test Stale Nonce In Cart Store
 * Description: Simulates a cached page with stale/invalid nonce for testing nonce refresh logic.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-stale-nonce-in-cart-store
 */

/**
 * Inject an invalid nonce into the interactivity state when the product button block renders.
 * This simulates what happens when a page is served from cache with an expired nonce.
 *
 * @param string $block_content The block content.
 * @param array  $block The block data.
 * @return string The unmodified block content.
 */
function wc_test_inject_stale_nonce_on_product_button( $block_content, $block ) {
	// Inject invalid nonce into the woocommerce interactivity state.
	wp_interactivity_state(
		'woocommerce',
		array(
			'nonce' => 'invalid-stale-nonce-from-cached-page',
		)
	);

	return $block_content;
}
add_filter( 'render_block_woocommerce/product-button', 'wc_test_inject_stale_nonce_on_product_button', 10, 2 );
