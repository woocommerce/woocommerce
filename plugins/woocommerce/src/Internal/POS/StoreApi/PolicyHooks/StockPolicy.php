<?php
/**
 * StockPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Lets POS sell regardless of recorded stock.
 *
 * At the register the item is physically in the customer's hand — recorded
 * inventory is at best an approximation (drift, unsynced deliveries, returns).
 * Blocking the sale because the database disagrees with reality helps nobody,
 * so during POS requests:
 *
 * - out-of-stock products/variations are purchasable
 *   (`woocommerce_product_is_in_stock` / `woocommerce_variation_is_in_stock`), and
 * - quantities above the recorded stock level are accepted
 *   (`woocommerce_product_backorders_allowed`, which has_enough_stock() consults).
 *
 * Stock still decrements normally when the order is placed, so inventory
 * converges back toward reality.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class StockPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * Variations are covered by `woocommerce_product_is_in_stock` too —
	 * WC_Product_Variation resolves is_in_stock() through the shared filter
	 * in the product base class; no variation-specific filter exists.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_is_in_stock', array( $this, 'maybe_force_in_stock' ) );
		add_filter( 'woocommerce_product_backorders_allowed', array( $this, 'maybe_force_in_stock' ) );
	}

	/**
	 * Treat products as sellable during POS requests, pass through otherwise.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $value Whether the product is in stock / backorderable.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_force_in_stock( $value ) {
		return Context::is_pos_request() ? true : $value;
	}
}
