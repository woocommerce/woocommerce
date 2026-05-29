<?php
/**
 * CheckoutEmailPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Opts POS requests out of the Store API's "billing_email is required" guard.
 *
 * Web checkout always requires a customer email so that confirmation emails
 * can be delivered. In-store POS sales legitimately don't capture one — the
 * customer is in front of the cashier and leaving with the goods. Skipping
 * this guard at the source (in `OrderController::validate_email`) means we
 * never accept an empty email for web while still allowing it for POS, with
 * no synthesised placeholder addresses on the order.
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
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_require_billing_email', array( $this, 'allow_missing_email_for_pos' ) );
	}

	/**
	 * Return false when the current request is a POS request, otherwise
	 * pass the original value through.
	 *
	 * @param bool $require Original value from the filter chain.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function allow_missing_email_for_pos( bool $require ): bool {
		if ( Context::is_pos_request() ) {
			return false;
		}

		return $require;
	}
}
