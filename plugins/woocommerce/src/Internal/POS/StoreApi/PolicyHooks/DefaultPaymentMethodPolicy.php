<?php
/**
 * DefaultPaymentMethodPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Suppresses the default payment-method stamp on POS draft orders.
 *
 * Web checkout stamps the first enabled gateway (typically WooPayments) onto
 * the draft order, but POS picks tender after order creation (recorded via
 * per-tender REST flows). Creating the order with no `payment_method` keeps an
 * unfinished POS sale from carrying a misleading gateway attribution. Uses the
 * `woocommerce_store_api_order_default_payment_method` filter added in
 * {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::update_order_from_cart}.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class DefaultPaymentMethodPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_order_default_payment_method', array( $this, 'maybe_clear_default_payment_method' ) );
	}

	/**
	 * Clear the default payment method on POS requests, leaving web behaviour
	 * untouched.
	 *
	 * @param string $payment_method Default payment method id.
	 * @return string
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_clear_default_payment_method( $payment_method ) {
		return Context::is_pos_request() ? '' : $payment_method;
	}
}
