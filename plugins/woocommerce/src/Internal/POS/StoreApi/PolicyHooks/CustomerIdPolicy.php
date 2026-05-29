<?php
/**
 * CustomerIdPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Forces customer_id to 0 on POS draft orders.
 *
 * The Store API's web-checkout default stamps `get_current_user_id()` onto
 * the draft order, which for POS means the cashier's WP user ends up
 * attributed as the customer. In an in-store sale the cashier is not the
 * customer, and POS doesn't (yet) support choosing one — the order is
 * anonymous by definition.
 *
 * Relies on the `woocommerce_store_api_order_customer_id` filter added in
 * {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::update_order_from_cart}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CustomerIdPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_order_customer_id', array( $this, 'force_guest_for_pos' ) );
	}

	/**
	 * Return 0 for POS requests, otherwise pass the original value through.
	 *
	 * @param int $customer_id Original customer_id from the filter chain.
	 * @return int
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function force_guest_for_pos( int $customer_id ): int {
		if ( Context::is_pos_request() ) {
			return 0;
		}

		return $customer_id;
	}
}
