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
	 *     // The stored cart key matches the key a plain add (no extra
	 *     // cart_item_data) would produce for this product + variation.
	 * }
	 * ```
	 *
	 * Code that needs to know whether a line counts toward a product's
	 * in-cart count should read the Store API cart-item response's
	 * is_canonical_product_line field instead: it applies the
	 * woocommerce_store_api_cart_item_is_canonical_product_line filter on top
	 * of this helper's result, and that filter is what an extension can
	 * override. {@see CartItemUtils::build_canonical_quantity_index()} resolves
	 * a product's first-canonical-line quantity from that response's `items`.
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

	/**
	 * Build a one-pass index of the first canonical cart line's quantity per product ID.
	 *
	 * Consumes the Store API cart-item response `items` array — the shape
	 * CartItemSchema emits per line (id, type, quantity,
	 * is_canonical_product_line) — which differs from the raw
	 * `WC()->cart->cart_contents` line shape is_standalone_line() documents
	 * above.
	 *
	 * Applies the canonical-line matching rule: an entry with no `id` key (the
	 * schema's empty-array placeholder for a line whose product no longer
	 * resolves) is skipped; an entry whose is_canonical_product_line is
	 * present and strictly `false` is skipped, while a missing field counts;
	 * an entry whose `type` is `variation` is never matched by product ID
	 * alone and is skipped; and among the entries that survive for a given
	 * product ID, only the first one in cart order is kept. The quantity
	 * passes through un-cast (`$item['quantity'] ?? 0`, hence `int|float`).
	 * The method is pure — all state enters through $items.
	 *
	 * @since 11.1.0
	 *
	 * @param array $items Store API cart-item response items.
	 * @return array Quantity keyed by product ID.
	 * @phpstan-return array<int, int|float>
	 */
	public static function build_canonical_quantity_index( array $items ): array {
		$index = array();

		foreach ( $items as $item ) {
			if ( ! isset( $item['id'] ) ) {
				continue;
			}

			if ( false === ( $item['is_canonical_product_line'] ?? true ) ) {
				continue;
			}

			if ( 'variation' === ( $item['type'] ?? null ) ) {
				continue;
			}

			if ( isset( $index[ $item['id'] ] ) ) {
				continue;
			}

			$index[ $item['id'] ] = $item['quantity'] ?? 0;
		}

		return $index;
	}
}
