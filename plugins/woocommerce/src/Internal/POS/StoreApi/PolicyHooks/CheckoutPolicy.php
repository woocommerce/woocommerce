<?php
/**
 * CheckoutPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Adapts Store API checkout requirements to an in-person sale.
 *
 * Consumes the additive checkout filters shipped for exactly this purpose
 * (POS series steps 1–2), all gated on POS context per call:
 *
 * - No payment method is required and none is set on the order — payment is
 *   taken at the register (card reader / cash) and recorded via wc/v3 after
 *   the `pending` order exists.
 * - Billing email is not required. A provided email is still validated and
 *   stored. (The client should collect one when the cart contains
 *   downloadable products, or the customer will have no way to receive the
 *   download.)
 * - Address validation is relaxed — an in-person guest usually has none.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CheckoutPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_checkout_require_payment_method', array( $this, 'maybe_relax_requirement' ) );
		add_filter( 'woocommerce_store_api_validate_addresses', array( $this, 'maybe_relax_requirement' ) );
		add_filter( 'woocommerce_store_api_require_billing_email', array( $this, 'maybe_relax_requirement' ) );
		add_filter( 'woocommerce_store_api_order_default_payment_method', array( $this, 'maybe_clear_default_payment_method' ) );

		// A POS sale is account-independent: the purchaser is a guest at the
		// register regardless of how the web checkout is configured.
		// registration_required=no keeps guest-checkout-disabled stores from
		// rejecting every POS sale ("You must be logged in to checkout");
		// registration_enabled=no keeps registration-required stores from
		// force-creating a WP account (and auth cookie, and welcome email)
		// for a walk-in customer.
		add_filter( 'woocommerce_checkout_registration_required', array( $this, 'maybe_relax_requirement' ) );
		add_filter( 'woocommerce_checkout_registration_enabled', array( $this, 'maybe_relax_requirement' ) );
	}

	/**
	 * Relax a boolean checkout requirement during POS requests.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $required Whether the requirement applies.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_relax_requirement( $required ) {
		return Context::is_pos_request() ? false : $required;
	}

	/**
	 * Leave the order's payment method empty during POS requests.
	 *
	 * Untyped parameter on purpose — see SessionHandlerSwap::swap_session_handler().
	 *
	 * @param mixed $payment_method Default payment method id.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_clear_default_payment_method( $payment_method ) {
		return Context::is_pos_request() ? '' : $payment_method;
	}
}
