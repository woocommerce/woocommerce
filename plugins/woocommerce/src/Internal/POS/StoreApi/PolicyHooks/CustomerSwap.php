<?php
/**
 * CustomerSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Customer;

/**
 * Replaces WC()->customer with a blank guest for POS route handling.
 *
 * WC()->customer is initialized from the session and the authenticated user —
 * in POS terms, from the *operator*. The purchaser is an in-person guest, so
 * the transaction must run against a blank customer: no operator addresses on
 * the order, no operator data in totals calculation.
 *
 * The default-location fallback (store-base country/state that
 * wc_get_customer_default_location() seeds on fresh customers) is stripped
 * too: the order's billing/shipping address should be truly empty, and tax
 * already computes at the store base via TaxLocationPolicy.
 *
 * Runs on `rest_dispatch_request` at a later priority than CurrentUserSwap so
 * the blank customer is built after the operator identity is dropped.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CustomerSwap implements RegisterHooksInterface {

	/**
	 * Priority after CurrentUserSwap (default 10).
	 */
	private const PRIORITY = 11;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'maybe_swap_customer' ), self::PRIORITY );
	}

	/**
	 * Install a blank guest customer before POS route callbacks run.
	 *
	 * @param mixed $dispatch_result Dispatch result passed through unchanged.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_swap_customer( $dispatch_result ) {
		if ( ! Context::is_pos_request() || ! function_exists( 'WC' ) ) {
			return $dispatch_result;
		}

		$customer = new WC_Customer( 0, true );
		$customer->set_billing_country( '' );
		$customer->set_billing_state( '' );
		$customer->set_shipping_country( '' );
		$customer->set_shipping_state( '' );

		WC()->customer = $customer;

		return $dispatch_result;
	}
}
