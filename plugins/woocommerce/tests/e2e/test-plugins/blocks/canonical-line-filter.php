<?php
/**
 * Plugin Name: WooCommerce Blocks Test Canonical Line Filter
 * Description: Marks cart-line-identity-marked lines canonical via the woocommerce_store_api_cart_item_is_canonical_line filter, for blocks e2e tests.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-canonical-line-filter
 */

/**
 * What this simulates.
 *
 * Real callers of woocommerce_store_api_cart_item_is_canonical_line are
 * extensions that flag their own meta-differentiated lines as canonical for
 * their own purposes (e.g. a bundle stamping its container line with
 * cart_item_data, then marking that line canonical so it still counts toward
 * the product's in-cart number). Those extensions are not installed in the
 * e2e environment, so this helper stands in for one: it marks canonical
 * exactly the lines the sibling cart-line-identity helper plugin
 * differentiates — those carrying the `_cart_line_identity` cart_item_data
 * key — and returns every other line's incoming value untouched.
 *
 * The targeting matters: a blanket __return_true callback would mark every
 * cart line canonical, changing the meaning of every other test in this
 * suite's file if both helper plugins were ever active together outside the
 * tests that intend it.
 *
 * How to activate it.
 *
 * This is a test-only helper that ships no real behavior and modifies no
 * WooCommerce source. An e2e test opts in by activating the plugin via its
 * WordPress slug — the @package value above,
 * "woocommerce-blocks-test-canonical-line-filter" — e.g.
 * requestUtils.activatePlugin( ... ), alongside the cart-line-identity
 * helper plugin that produces the marked lines this filter recognizes.
 */

declare( strict_types = 1 );

add_filter(
	'woocommerce_store_api_cart_item_is_canonical_line',
	/**
	 * Mark a cart-line-identity-marked line canonical; leave others untouched.
	 *
	 * @param bool  $is_canonical Core-computed default.
	 * @param array $cart_item    Cart item array.
	 * @return bool True for a line carrying the `_cart_line_identity`
	 *              cart_item_data key (see the cart-line-identity helper
	 *              plugin); the incoming $is_canonical value otherwise.
	 */
	function ( $is_canonical, $cart_item ) {
		if ( isset( $cart_item['_cart_line_identity'] ) ) {
			return true;
		}

		return $is_canonical;
	},
	10,
	2
);
