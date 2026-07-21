<?php
/**
 * Cart item utility functions.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Cart item utility functions for the Store API.
 *
 * @since 11.1.0
 */
class CartItemUtils {

	/**
	 * Determines whether a cart line is the standalone (non-meta-differentiated) line for its product.
	 *
	 * A standalone cart line is one whose stored cart key equals the key that
	 * WooCommerce's {@see WC_Cart::generate_cart_id()} would produce when called
	 * with only the line's product_id, variation_id, and variation attributes —
	 * that is, with no additional cart_item_data. Such lines represent a
	 * standalone product in the cart that has not been differentiated by extra
	 * metadata (e.g. bundle components, subscription switches, composite parts).
	 *
	 * When the stored key differs from that recomputed baseline, some plugin or
	 * WooCommerce extension passed non-empty cart_item_data when the line was
	 * originally added, and the line is therefore NOT the standalone line
	 * for that product+variation.
	 *
	 * Usage:
	 * ```php
	 * use Automattic\WooCommerce\StoreApi\Utilities\CartItemUtils;
	 *
	 * if ( CartItemUtils::is_standalone_line( $cart_item ) ) {
	 *     // This is the standalone line — show an "Add to cart" button.
	 * }
	 * ```
	 *
	 * @since 11.1.0
	 *
	 * @param array $cart_item A cart-line array as stored in `WC()->cart->cart_contents`.
	 *                         Expected keys: 'key' (string), 'product_id' (int),
	 *                         'variation_id' (int), 'variation' (array).
	 *                         Missing keys default to 0 / empty array so that a
	 *                         malformed entry degrades gracefully without a fatal.
	 * @return bool True when the stored key equals the standalone key — the line IS the
	 *              standalone per-product line; false when meta-differentiated, cart
	 *              unavailable, or array malformed.
	 */
	public static function is_standalone_line( array $cart_item ): bool {
		// @phpstan-ignore isset.property (WC()->cart is declared non-null but can be null before WC fully initialises; the guard is load-bearing for early-bootstrap callers)
		if ( ! isset( WC()->cart ) ) {
			return false;
		}

		$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		$variation    = is_array( $cart_item['variation'] ?? null ) ? $cart_item['variation'] : array();

		$standalone_key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation );

		return ( $cart_item['key'] ?? '' ) === $standalone_key;
	}
}
