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
