<?php
/**
 * ShippingPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Tells the cart there is nothing to ship for POS requests.
 *
 * An in-store POS transaction is by definition an in-person sale: the
 * customer leaves the store with the goods. Even when the cart contains a
 * shipping-needing physical product, no shipping should be computed and
 * no shipping line item should be stamped on the resulting order.
 *
 * Returning false from {@see \WC_Cart::needs_shipping} via the existing
 * `woocommerce_cart_needs_shipping` filter short-circuits this at the
 * source — shipping packages are never generated, the cart totals never
 * include a shipping cost, and `update_line_items_from_cart` consequently
 * never creates a shipping line item on the order. Nothing to undo.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class ShippingPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'no_shipping_for_pos' ) );
	}

	/**
	 * Return false for POS requests, otherwise pass the original value
	 * through.
	 *
	 * @param bool $needs_shipping Original value from the filter chain.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function no_shipping_for_pos( bool $needs_shipping ): bool {
		if ( Context::is_pos_request() ) {
			return false;
		}

		return $needs_shipping;
	}
}
