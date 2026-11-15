<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Counts a product's quantity in the cart published to the `woocommerce` Interactivity API state.
 */
final class InCartQuantity {

	/**
	 * Sum the product's quantity in the published cart, excluding declared child lines.
	 * Reads the cart that `BlocksSharedState::load_cart_state()` publishes, so call it after that.
	 * An entry without an id key, such as the `[]` placeholder, matches nothing.
	 *
	 * @since 11.3.0
	 *
	 * @param int $product_id Product or variation ID to count.
	 *
	 * @return int|float The sum of eligible cart-item quantities.
	 */
	public static function for_product( int $product_id ) {
		$woocommerce_state = wp_interactivity_state( 'woocommerce' );
		$total             = 0;

		foreach ( $woocommerce_state['cart']['items'] ?? array() as $item ) {
			if ( ( $item['id'] ?? null ) !== $product_id || '' !== ( $item['parent_item_key'] ?? '' ) ) {
				continue;
			}

			$total += $item['quantity'];
		}

		return $total;
	}
}
