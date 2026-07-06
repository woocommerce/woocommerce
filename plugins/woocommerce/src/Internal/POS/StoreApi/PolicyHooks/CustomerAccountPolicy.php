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
 * cashier can identify the purchaser as an existing customer account via the
 * POS checkout route's optional `customer_id`.
 *
 * The attach is two-phase on purpose. `customer_id` is validated when the
 * order is synced from the request, but only APPLIED after the checkout
 * pipeline's customer-processing step: that step syncs the order's addresses
 * back onto the order's customer account, and a POS order's addresses are
 * deliberately blank — attaching earlier would erase the account's saved
 * addresses on every POS sale. POS writes nothing to customer accounts; the
 * account is recorded on the order, full stop.
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
	 * Customer account captured from the request, pending attachment.
	 *
	 * @var int
	 */
	private $pending_customer_id = 0;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'maybe_capture_customer_account' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'maybe_attach_customer_account' ) );
	}

	/**
	 * Validate and remember the requested customer account.
	 *
	 * @throws RouteException When the customer_id does not match an existing user.
	 * @param WC_Order        $order   Order being synced from the request.
	 * @param WP_REST_Request $request Checkout request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_capture_customer_account( $order, $request ): void {
		$this->pending_customer_id = 0;

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

		$this->pending_customer_id = $customer_id;
	}

	/**
	 * Attach the captured customer account once customer processing is over.
	 *
	 * @param WC_Order $order The processed order.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_attach_customer_account( $order ): void {
		if ( ! Context::is_pos_request() || ! $order instanceof WC_Order || $this->pending_customer_id <= 0 ) {
			return;
		}

		$order->set_customer_id( $this->pending_customer_id );
		$order->save();
		$this->pending_customer_id = 0;
	}
}
