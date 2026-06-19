<?php
/**
 * Plugin Name: WooCommerce Blocks Test Cart Line Identity
 * Description: Simulates a meta-differentiated cart line for blocks e2e tests by marking flagged add-to-cart requests.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-cart-line-identity
 */

/**
 * What this simulates.
 *
 * Some cart lines share a product id with another line but are differentiated
 * by item metadata: a bundle child, a booking, a Product Add-Ons configuration,
 * a gift-card recipient. Those extensions are not installed in the e2e
 * environment, so this helper stands in for one such "meta-differentiated"
 * line. When the request carries the marker flag below, the added item gets a
 * unique cart_item_data entry; because core's WC_Cart::generate_cart_id() folds
 * cart_item_data into the cart-line hash, the flagged add lands as a line whose
 * cart id differs from a plain (unflagged) add of the same product — exactly as
 * a real meta-differentiated line does, without shipping a real extension.
 *
 * How to activate it.
 *
 * This is a test-only helper that ships no real behavior and modifies no
 * WooCommerce source: with the flag absent (its default state for every
 * untouched request) it is inert. An e2e test opts in by activating the plugin
 * via its WordPress slug — the @package value above,
 * "woocommerce-blocks-test-cart-line-identity" — e.g.
 * requestUtils.activatePlugin( ... ), as the sibling helper plugins here are.
 *
 * How a test uses it.
 *
 * To set up the "product X is already in the cart as a meta line" precondition,
 * a test issues one flagged add (append CART_LINE_IDENTITY_FLAG to the add-to-cart
 * URL); that seeds the meta-differentiated line. A subsequent plain (unflagged)
 * add of the same product through the block UI then exercises the behavior under
 * test, and core resolves it as a separate standalone line. Toggling the marker
 * is what produces the meta-differentiated line: a flagged add creates/extends a
 * meta line, an unflagged add follows the normal standalone-line identity.
 */

declare(strict_types=1);

/**
 * Request flag a test toggles to mark an add as a meta line (see file header).
 *
 * Append it to the add-to-cart request (the Store API add-item URL or the legacy
 * add-to-cart URL); when present, the added item receives the unique
 * cart_item_data entry below. Absent, the add is left untouched.
 */
const CART_LINE_IDENTITY_FLAG = 'cart_line_identity_marker';

/**
 * cart_item_data key used to carry the marker on the cart line.
 *
 * The leading underscore keeps it out of the customer-visible item data list,
 * matching how extensions store internal differentiators.
 */
const CART_LINE_IDENTITY_KEY = '_cart_line_identity';

add_filter(
	'woocommerce_add_cart_item_data',
	/**
	 * Attach a unique cart_item_data marker to a flagged add-to-cart request.
	 *
	 * Has effect only when the request carries the CART_LINE_IDENTITY_FLAG; a
	 * plain add returns the cart item data untouched, so it follows the normal
	 * (standalone-line) identity and increments any existing standalone line.
	 *
	 * The marker value is taken from the flag so a test can mint more than one
	 * distinct meta line if needed; an empty/bare flag falls back to a stable
	 * default value, which is enough to differentiate one meta line from the
	 * plain line.
	 *
	 * @param array $cart_item_data Existing cart item data.
	 * @return array Cart item data, with the marker added when the flag is present.
	 */
	function ( $cart_item_data ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only test marker, no state change; nonce handled by the underlying add-to-cart request.
		if ( ! isset( $_REQUEST[ CART_LINE_IDENTITY_FLAG ] ) ) {
			return $cart_item_data;
		}

		$marker = sanitize_text_field( wp_unslash( $_REQUEST[ CART_LINE_IDENTITY_FLAG ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' === $marker ) {
			$marker = 'meta-line';
		}

		$cart_item_data[ CART_LINE_IDENTITY_KEY ] = $marker;

		return $cart_item_data;
	},
	10,
	1
);
