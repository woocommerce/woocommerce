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
 * Attaches a known customer account to a POS order on request.
 *
 * A POS sale is a guest sale by default (operator ≠ customer), but the
 * cashier can identify the purchaser as an existing customer account — the
 * POS checkout route accepts an optional `customer_id`. Applied on the
 * checkout order-sync hook so the order is owned by that customer, never by
 * the operator.
 *
 * Registered unconditionally; the POS context is evaluated lazily per call
 * (see SessionHandlerSwap for why).
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CustomerAccountPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'maybe_attach_customer_account' ), 10, 2 );
	}

	/**
	 * Attach the identified customer account to the order.
	 *
	 * @throws RouteException When the customer_id does not match an existing user.
	 * @param WC_Order        $order   Order being synced from the request.
	 * @param WP_REST_Request $request Checkout request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_attach_customer_account( $order, $request ): void {
		if ( ! Context::is_pos_request() || ! $order instanceof WC_Order ) {
			return;
		}

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
