<?php
/**
 * Cart item utility functions.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Cart item utility functions for the Store API.
 *
 * Provides shared predicates used by the Store API cart-item schema and the
 * ProductButton block server-side rendering to determine the nature of a cart
 * line without duplicating the identity logic at each call site.
 *
 * @since 11.0.0
 */
class CartItemUtils {

	/**
	 * Determines whether a cart line was created with non-empty cart_item_data.
	 *
	 * A "plain" cart line is one whose stored cart key equals the key that
	 * WooCommerce's {@see WC_Cart::generate_cart_id()} would produce when called
	 * with only the line's product_id, variation_id, and variation attributes —
	 * that is, with no additional cart_item_data. Such lines represent a
	 * standalone product in the cart that has not been differentiated by extra
	 * metadata (e.g. bundle components, subscription switches, composite parts).
	 *
	 * When the stored key differs from that recomputed baseline, some plugin or
	 * WooCommerce extension passed non-empty cart_item_data when the line was
	 * originally added, and the line is therefore NOT the plain/standalone line
	 * for that product+variation.
	 *
	 * Usage:
	 * ```php
	 * use Automattic\WooCommerce\StoreApi\Utilities\CartItemUtils;
	 *
	 * if ( ! CartItemUtils::has_cart_item_data( $cart_item ) ) {
	 *     // This is the plain, standalone line — show an "Add to cart" button.
	 * }
	 * ```
	 *
	 * @since 11.0.0
	 *
	 * @param array $cart_item A cart-line array as stored in `WC()->cart->cart_contents`.
	 *                         Expected keys: 'key' (string), 'product_id' (int),
	 *                         'variation_id' (int), 'variation' (array).
	 *                         Missing keys default to 0 / empty array so that a
	 *                         malformed entry degrades gracefully without a fatal.
	 * @return bool True when the line's stored key was generated with non-empty
	 *              cart_item_data (i.e. the line is meta-differentiated).
	 *              False when the line is plain, when WC()->cart is unavailable,
	 *              or when the $cart_item array is malformed.
	 */
	public static function has_cart_item_data( array $cart_item ): bool {
		if ( ! isset( WC()->cart ) ) {
			return false;
		}

		$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		$variation    = is_array( $cart_item['variation'] ?? null ) ? $cart_item['variation'] : array();

		$plain_key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation );

		return ( $cart_item['key'] ?? '' ) !== $plain_key;
	}
}
