<?php
/**
 * StockPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Relaxes the in-stock check for POS Store API requests.
 *
 * Physical retail wants overselling allowed: the customer is at the counter
 * holding the item, so "out of stock" is wrong by construction. Stock is still
 * decremented on order completion, so inventory reconciliation is unaffected.
 *
 * The filters are installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class StockPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_is_in_stock', array( $this, 'maybe_allow_oversell' ) );
		add_filter( 'woocommerce_variation_is_in_stock', array( $this, 'maybe_allow_oversell' ) );
	}

	/**
	 * Force in-stock on POS requests, leaving web behaviour untouched.
	 *
	 * @param bool $is_in_stock Whether the product/variation is in stock.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_allow_oversell( $is_in_stock ) {
		return Context::is_pos_request() ? true : $is_in_stock;
	}
}
