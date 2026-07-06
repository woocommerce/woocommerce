<?php
/**
 * TaxLocationPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Taxes an in-person sale at the store's base location.
 *
 * The default taxable address derives from the customer's billing/shipping
 * address — which an in-person guest doesn't have. The sale happens at the
 * store, so the store's base location is the taxable address. (No dedicated
 * override filter is exposed for now — minimal public surface; the generic
 * `woocommerce_customer_taxable_address` filter still runs for anyone who
 * genuinely needs to intervene.)
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class TaxLocationPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'maybe_use_store_base_address' ) );
	}

	/**
	 * Use the store base location as the taxable address for POS requests.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $taxable_address Country / state / postcode / city tuple.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_use_store_base_address( $taxable_address ) {
		if ( ! Context::is_pos_request() ) {
			return $taxable_address;
		}

		return array(
			(string) WC()->countries->get_base_country(),
			(string) WC()->countries->get_base_state(),
			(string) WC()->countries->get_base_postcode(),
			(string) WC()->countries->get_base_city(),
		);
	}
}
