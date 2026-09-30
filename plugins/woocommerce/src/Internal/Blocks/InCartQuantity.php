<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Blocks;

/**
 * Counts a product's quantity in Store API cart-item lines.
 */
final class InCartQuantity {

	/**
	 * Sum the matching cart-item quantities, excluding declared child lines.
	 * An entry without an id key, such as the `[]` placeholder, matches nothing.
	 *
	 * @since 11.3.0
	 *
	 * @param array<int, array<string, mixed>> $cart_items Store API cart-item lines.
	 * @param int                              $product_id Product or variation ID to count.
	 *
	 * @return int|float The sum of eligible cart-item quantities.
	 */
	public static function for_product( array $cart_items, int $product_id ) {
		$total = 0;

		foreach ( $cart_items as $item ) {
			if ( ( $item['id'] ?? null ) !== $product_id || '' !== ( $item['parent_item_key'] ?? '' ) ) {
				continue;
			}

			$total += $item['quantity'];
		}

		return $total;
	}
}
