<?php
/**
 * CheckoutPaymentMethodPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Opts POS requests out of the Store API's "payment_method is required on
 * POST /checkout" guard.
 *
 * POS legitimately defers payment selection past order creation — the cashier
 * may not have chosen a tender yet. The order is created in `pending` and a
 * separate flow (WooPayments capture for cards, POS cash-paid endpoint for
 * cash) marks it paid. Uses the
 * `woocommerce_store_api_checkout_require_payment_method` filter added in
 * {@see \Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait::update_order_from_request}.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CheckoutPaymentMethodPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_checkout_require_payment_method', array( $this, 'maybe_skip_payment_method_requirement' ) );
	}

	/**
	 * Drop the payment-method requirement on POS requests, leaving web behaviour
	 * untouched.
	 *
	 * @param bool $required Whether a payment method is required.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_skip_payment_method_requirement( $required ) {
		return Context::is_pos_request() ? false : $required;
	}
}
