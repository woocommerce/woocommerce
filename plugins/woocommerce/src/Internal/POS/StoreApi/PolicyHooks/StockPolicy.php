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
 * Registration is gated on {@see Context::is_pos_request()}, so the filters are
 * installed only when the current request is POS.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class StockPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_product_is_in_stock', '__return_true' );
		add_filter( 'woocommerce_variation_is_in_stock', '__return_true' );
	}
}
