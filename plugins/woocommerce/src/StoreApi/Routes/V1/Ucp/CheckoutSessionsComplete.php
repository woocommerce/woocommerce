<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\CheckoutMapper;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

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
	 * Stock is held for the order first, as the Store API checkout does, because the payment
	 * page trusts a pending order's stock to be held and checks nothing itself.
	 * A cart with nothing to pay is refused: these routes complete no order, and the payment page takes no such order.
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
		$order       = $this->get_draft_order();
		unset( $state['status'], $state['continue_url'] );

		// The cart can change behind the checkout, through the Store API or a price edit, and a
		// new order is then made for it: the one already handed off must not stay payable too.
		if ( ! empty( $state['order_id'] ) && ( ! $order || $order->get_id() !== (int) $state['order_id'] ) ) {
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session cart changed after payment was handed off.', 'woocommerce' ) );

			if ( ! empty( $state['order_id'] ) ) {
				return $this->refuse_kept_order();
			}
		}

		if ( CheckoutMapper::STATUS_READY_FOR_COMPLETE !== ( new CheckoutMapper() )->build_checkout( $cart, $checkout_id, $state )['status'] ) {
			// An order handed off while the checkout was ready, for stock since taken say, must not stay payable.
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session is no longer ready to complete.', 'woocommerce' ) );

			if ( $this->hand_off_is_payable( $state ) ) {
				return $this->refuse_kept_order();
			}

			return $this->checkout_response( $checkout_id, $this->checkout_state() ?? $state, 200, $cart );
		}

		if ( ! $this->cart_controller->get_cart_instance()->needs_payment() ) {
			return $this->ucp_error( 'payment_not_required', __( 'This checkout has nothing to pay, so it cannot be handed off for payment. It must be made on the store itself.', 'woocommerce' ), 'unrecoverable', 200 );
		}

		if ( $order ) {
			$this->order_controller->update_order_from_cart( $order );
		} else {
			$order = $this->order_controller->create_order_from_cart();
		}

		if ( ! $order->get_id() ) {
			return $this->ucp_error( 'TRANSPORT_ERROR', __( 'The order could not be created. Please try again.', 'woocommerce' ), 'recoverable', 500 );
		}

		// Remembered before the hold so a refused hold leaves a draft the next attempt reuses.
		$this->set_draft_order_id( $order->get_id() );

		// Taken before the hooks below run, so a cart one of them changes no longer matches the order.
		$fingerprint = $this->hand_off_fingerprint();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_update_order_meta', $order );

		try {
			$this->order_controller->validate_order_before_payment( $order );
		} catch ( RouteException $e ) {
			// An order the Store API checkout would refuse must not be, or stay, payable.
			$this->cancel_handed_off_order( $state, __( 'UCP checkout session no longer passes checkout validation.', 'woocommerce' ) );

			return $this->ucp_error( 'VALIDATION_ERROR', wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200 );
		}

		try {
			wc_reserve_stock_for_order( $order );
		} catch ( ReserveStockException $e ) {
			// The hold failed on stock another checkout took since the cart was last checked. A draft
			// stays one, which the payment page refuses; an order handed off earlier is closed.
			$this->cancel_handed_off_order( $state, __( 'UCP checkout session stock could no longer be held.', 'woocommerce' ) );

			return $this->ucp_error( Utils::cart_error_code( (string) $e->getErrorCode() ), wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200, '$.line_items' );
		}

		// The payment page only takes an order awaiting payment, which a checkout draft is not.
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_order_processed', $order );

		$state = array_merge(
			$state,
			array(
				'status'       => CheckoutMapper::STATUS_REQUIRES_ESCALATION,
				'continue_url' => $order->get_checkout_payment_url(),
				'order_id'     => $order->get_id(),
				'fingerprint'  => $fingerprint,
			)
		);
		$this->save_checkout_state( $state );

		return $this->checkout_response( $checkout_id, $this->checkout_state() ?? $state, 200, $cart );
	}
}
