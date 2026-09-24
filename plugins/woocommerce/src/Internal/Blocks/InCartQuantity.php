<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Blocks;

/**
 * Counts a product's quantity in Store API cart-item lines.
 */
final class InCartQuantity {

	/**
	 * Sum the matching cart-item quantities, excluding declared child lines.
	 *
	 * @since 11.3.0
	 *
	 * @param array<int, mixed> $cart_items Store API cart-item lines.
	 * @param int               $product_id Product or variation ID to count.
	 *
	 * @return int|float The sum of eligible cart-item quantities.
	 */
	public static function for_product( array $cart_items, int $product_id ) {
		$total = 0;

		foreach ( $cart_items as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['id'] ) || ! is_int( $item['id'] ) || $item['id'] !== $product_id ) {
				continue;
			}

			$parent_item_key = $item['parent_item_key'] ?? null;
			if ( is_string( $parent_item_key ) && '' !== $parent_item_key ) {
				continue;
			}

			$quantity = $item['quantity'] ?? null;
			if ( is_int( $quantity ) || is_float( $quantity ) ) {
				$total += $quantity;
			}
		}

		return $total;
	}
}
