<?php
/**
 * CheckoutEmailPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutRequirements;
use WC_Order;

/**
 * Cart-aware policy for the Store API's "billing_email is required" guard on
 * POS requests.
 *
 * The typical in-store cash sale has no email to capture, but some cart
 * contents need one (a downloadable must be delivered somewhere). The shared
 * {@see CheckoutRequirements} decides per-cart; when no email is needed the
 * order proceeds with an empty `billing_email`, otherwise the standard
 * validation runs unchanged. Uses the `woocommerce_store_api_require_billing_email`
 * filter added in {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_email}.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CheckoutEmailPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_require_billing_email', array( $this, 'require_when_cart_needs_email' ), 10, 2 );
	}

	/**
	 * On POS requests, require an email only if the cart contents need one
	 * (currently: any downloadable line item) AND the upstream filter chain
	 * hasn't already opted out. Web requests are left untouched.
	 *
	 * @param bool     $required Original value from the filter chain.
	 * @param WC_Order $order   Order being validated.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function require_when_cart_needs_email( bool $required, WC_Order $order ): bool {
		if ( ! Context::is_pos_request() ) {
			return $required;
		}
		return $required && CheckoutRequirements::for_order( $order )->requires_email();
	}
}
