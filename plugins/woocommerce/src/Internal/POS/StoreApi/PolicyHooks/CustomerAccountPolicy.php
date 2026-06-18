<?php
/**
 * CustomerAccountPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use WC_Order;
use WP_REST_Request;

/**
 * Attaches an existing customer account to a POS order at checkout.
 *
 * POS runs business logic as a guest ({@see CurrentUserSwap}/{@see CustomerSwap})
 * because the cashier is not the customer. But a sale can still be *attributed*
 * to a registered customer when the cashier looks one up: the `customer_id`
 * checkout parameter records that association on the order without making the
 * request behave as that user. Cart pricing, coupons and tax still resolve as a
 * guest — only the order's `customer_id` is set, mirroring how agentic commerce
 * shapes the order through the same `woocommerce_store_api_checkout_update_order_from_request`
 * seam rather than a bespoke path.
 *
 * `customer_id` is an optional, POS-only parameter read straight off the
 * checkout request body — the shared `wc/store/v1/checkout` schema deliberately
 * doesn't declare it, so web checkout neither advertises nor accepts it. This
 * hook coerces and validates it here instead. With no `customer_id` the order
 * stays a guest order (the existing behaviour); an unknown account fails the
 * checkout loudly rather than silently attributing the sale to the wrong
 * account.
 *
 * The action is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CustomerAccountPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'attach_customer_account' ), 10, 2 );
	}

	/**
	 * On POS requests, set the order's customer id from the request's
	 * `customer_id` parameter. No-op on web requests.
	 *
	 * @param WC_Order        $order   Order being built from the checkout request.
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @throws RouteException When `customer_id` is set but no such customer exists.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function attach_customer_account( WC_Order $order, WP_REST_Request $request ): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}

		// Read straight off the request body; absent or non-positive means a
		// guest sale — leave the order untouched.
		$customer_id = (int) ( $request['customer_id'] ?? 0 );
		if ( $customer_id <= 0 ) {
			return;
		}

		if ( false === get_userdata( $customer_id ) ) {
			throw new RouteException(
				'woocommerce_pos_rest_customer_not_found',
				esc_html__( 'No customer was found for the provided customer_id.', 'woocommerce' ),
				400
			);
		}

		$order->set_customer_id( $customer_id );
	}
}
