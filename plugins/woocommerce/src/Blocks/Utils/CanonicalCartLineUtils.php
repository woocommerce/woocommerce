<?php
/**
 * Canonical cart line utility functions.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Provides pure lookups over already-hydrated cart response items.
 *
 * @since 11.1.0
 */
class CanonicalCartLineUtils {

	/**
	 * Get a one-pass index of the first canonical cart line's quantity per product ID.
	 *
	 * Consumes the Store API cart-item response `items` array — the shape
	 * CartItemSchema emits per line (id, type, quantity,
	 * is_canonical_product_line) — which differs from the raw
	 * `WC()->cart->cart_contents` line shape.
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
	public static function get_first_canonical_line_quantities( array $items ): array {
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
