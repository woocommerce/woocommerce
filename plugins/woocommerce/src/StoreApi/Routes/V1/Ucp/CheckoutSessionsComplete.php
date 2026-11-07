<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\CheckoutMapper;

/**
 * UCP `POST /checkout-sessions/{id}/complete` route.
 */
class CheckoutSessionsComplete extends AbstractCheckoutRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout-sessions-complete';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout-sessions/(?P<id>[\w\-\.]+)/complete';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Hand a ready checkout over to the buyer to pay in the browser.
	 *
	 * No payment is taken through the API, so `payment` is ignored. The cart becomes a pending
	 * order, the Store API draft order of this session, and `continue_url` is its payment page.
	 * A checkout that is not ready yet is returned as it is, with messages naming what is missing.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$state = $this->open_checkout( true );

		if ( $state instanceof \WP_REST_Response ) {
			return $state;
		}

		$checkout_id = $this->route_cart_id( $request );
		$cart        = $this->cart_item_response();
		unset( $state['status'], $state['continue_url'] );

		if ( CheckoutMapper::STATUS_READY_FOR_COMPLETE !== ( new CheckoutMapper() )->build_checkout( $cart, $checkout_id, $state )['status'] ) {
			return $this->checkout_response( $checkout_id, $state, 200, $cart );
		}

		$order = $this->get_draft_order();
		if ( $order ) {
			$this->order_controller->update_order_from_cart( $order );
		} else {
			$order = $this->order_controller->create_order_from_cart();
		}

		// The payment page only takes an order awaiting payment, which a checkout draft is not.
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		if ( ! $order->get_id() ) {
			return $this->ucp_error( 'TRANSPORT_ERROR', __( 'The order could not be created. Please try again.', 'woocommerce' ), 'recoverable', 500 );
		}

		$this->set_draft_order_id( $order->get_id() );

		$state = array_merge(
			$state,
			array(
				'status'       => CheckoutMapper::STATUS_REQUIRES_ESCALATION,
				'continue_url' => $order->get_checkout_payment_url(),
				'order_id'     => $order->get_id(),
			)
		);
		$this->save_checkout_state( $state );

		return $this->checkout_response( $checkout_id, $state, 200, $cart );
	}
}
