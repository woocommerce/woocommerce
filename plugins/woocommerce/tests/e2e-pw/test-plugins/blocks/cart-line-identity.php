<?php
/**
 * Plugin Name: WooCommerce Blocks Test Cart Line Identity
 * Description: Simulates a meta-differentiated cart line (a bundle child / booking / add-on / recipient stand-in) by attaching a unique cart_item_data entry to a flagged add-to-cart request, so core's generate_cart_id produces a distinct cart line for the same product id. Activated only in e2e tests; changes no WooCommerce source behavior.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-cart-line-identity
 */

declare(strict_types=1);

/**
 * Query/request flag the e2e harness toggles to mark an add-to-cart request.
 *
 * When this flag is present on the request (e.g. appended to the Store API
 * add-item URL or the legacy add-to-cart URL), the added item receives a unique
 * cart_item_data entry. Because core's WC_Cart::generate_cart_id() folds
 * cart_item_data into the cart-line hash, the flagged add produces a line whose
 * cart id differs from a plain (unflagged) add of the same product, exactly as a
 * bundle child / meta-differentiated line does — without modifying any product
 * or shipping a real extension.
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
