<?php
/**
 * CheckoutEmailPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PosCheckoutRequirements;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Cart-aware policy for the Store API's "billing_email is required" guard
 * on POS requests.
 *
 * The typical in-store sale is a cash transaction for physical goods the
 * customer is walking out with — there's no email to capture and no
 * confirmation to deliver. But some cart contents genuinely need an email:
 * a downloadable product has to be sent somewhere. {@see PosCheckoutRequirements}
 * decides per-cart; this hook just consumes its verdict.
 *
 * When the cart doesn't need an email, the standard Store API validation is
 * skipped and the order proceeds with an empty `billing_email`. When the
 * cart does need one and the cashier hasn't supplied it, validation runs
 * unchanged and Store API returns the standard
 * `woocommerce_rest_missing_email_address` 400 — the mobile client gets the
 * same error shape web checkout would produce.
 *
 * Relies on the `woocommerce_store_api_require_billing_email` filter added
 * in {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_email}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CheckoutEmailPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_store_api_require_billing_email', array( $this, 'require_when_cart_needs_email' ), 10, 2 );
	}

	/**
	 * Require an email only if the cart contents need one (currently: any
	 * downloadable line item) AND the upstream filter chain hasn't already
	 * opted out.
	 *
	 * @param bool     $required Original value from the filter chain.
	 * @param WC_Order $order   Order being validated.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function require_when_cart_needs_email( bool $required, WC_Order $order ): bool {
		return $required && PosCheckoutRequirements::for_order( $order )->requires_email();
	}
}
