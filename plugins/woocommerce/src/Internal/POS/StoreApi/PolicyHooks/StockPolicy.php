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
 * Physical retail almost always wants overselling allowed: the customer is
 * standing at the counter holding the item, so the stock-system answer of
 * "out of stock" is wrong by construction. Stock is still decremented on
 * order completion, which is the behaviour merchants rely on for inventory
 * reconciliation.
 *
 * The hook only takes effect when {@see Context::is_pos_request()} is true,
 * so web checkout behaviour is unaffected.
 *
 * Canonical example of the "POS policy point" pattern: a single filter
 * callback whose only POS-specific logic is the context flag check. See
 * DECISIONS.md for the full pattern rationale and the broader policy-point
 * audit that this hook is one entry in.
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
		add_filter( 'woocommerce_product_is_in_stock', array( $this, 'allow_oversell_for_pos' ) );
		add_filter( 'woocommerce_variation_is_in_stock', array( $this, 'allow_oversell_for_pos' ) );
	}

	/**
	 * Force is_in_stock to true for POS requests.
	 *
	 * @param bool $is_in_stock Original value.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function allow_oversell_for_pos( bool $is_in_stock ): bool {
		if ( Context::is_pos_request() ) {
			return true;
		}

		return $is_in_stock;
	}
}
