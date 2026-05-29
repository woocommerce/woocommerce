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
 * The Store API's web-checkout default stamps the first enabled gateway
 * (typically WooPayments) onto the draft order. POS legitimately defers
 * payment-method selection past order creation — the cashier picks tender
 * post-order and the chosen gateway is recorded via the existing per-tender
 * REST flows (WooPayments terminal capture for cards, the established
 * cash mark-paid endpoint). The order should therefore be created with no
 * `payment_method` so an unfinished POS sale never carries a misleading
 * gateway attribution.
 *
 * Relies on the `woocommerce_store_api_order_default_payment_method` filter
 * added in {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::update_order_from_cart}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class DefaultPaymentMethodPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_store_api_order_default_payment_method', '__return_empty_string' );
	}
}
