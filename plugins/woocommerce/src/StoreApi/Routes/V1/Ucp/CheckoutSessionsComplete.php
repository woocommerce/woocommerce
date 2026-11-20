<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\OrderAttributionController;
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
	 * So is one already handed off and unchanged since, with a warning: its order is not written to again.
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

		// Taken before the hooks below run, so a cart one of them changes no longer matches the order.
		$fingerprint = $this->hand_off_fingerprint();

		// The cart or its addresses can change behind the checkout, through the Store API or a price edit, and
		// a new order is then made for it: the one already handed off must not stay payable too.
		if ( ! empty( $state['order_id'] ) && ( ! $order || $order->get_id() !== (int) $state['order_id'] || ( $state['fingerprint'] ?? '' ) !== $fingerprint ) ) {
			$state = $this->cancel_handed_off_order( $state, __( 'UCP checkout session cart changed after payment was handed off.', 'woocommerce' ) );

			if ( ! empty( $state['order_id'] ) ) {
				return $this->refuse_kept_order();
			}

			$order = $this->get_draft_order();
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

		// Handed off already and unchanged. The buyer may be paying this order now, so it is not written to again.
		if ( ! empty( $state['order_id'] ) ) {
			return $this->already_handed_off_response( $checkout_id, $cart );
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

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_update_order_meta', $order );

		try {
			$this->order_controller->validate_order_before_payment( $order );
		} catch ( RouteException $e ) {
			// The order stays a draft, which the payment page refuses.
			return $this->ucp_error( 'VALIDATION_ERROR', wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200 );
		}

		try {
			wc_reserve_stock_for_order( $order );
		} catch ( ReserveStockException $e ) {
			// The hold failed on stock another checkout took since the cart was last checked. The order
			// stays a draft, which the payment page refuses.
			return $this->ucp_error( Utils::cart_error_code( (string) $e->getErrorCode() ), wp_specialchars_decode( $e->getMessage(), ENT_QUOTES ), 'recoverable', 200, '$.line_items' );
		}

		$this->record_attribution( $order, $request );

		// The payment page only takes an order awaiting payment, which a checkout draft is not.
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		// Stored before the processed callbacks, which can pay the order. A callback that then
		// fails must leave this association in place so a retry finds the order and does not
		// create another. The hook is not run again on that retry: a callback that already
		// ran must not run twice, which is also how the Store API checkout behaves.
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

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Store API hook, documented in Routes\V1\Checkout.
		do_action( 'woocommerce_store_api_checkout_order_processed', $order );

		return $this->checkout_response( $checkout_id, $this->checkout_state() ?? $state, 200, $cart );
	}

	/**
	 * Record the order as started by an AI shopper, so its origin is not Unknown.
	 *
	 * The payment page records no attribution of its own.
	 * The source is the host of the `profile` parameter in the `UCP-Agent` header, or `ucp` when that parameter is missing or its host is not a hostname.
	 * A parameter only counts when its name is exactly `profile` and it is outside a quoted value.
	 * The header is not verified, so the host is a detail and not part of the origin.
	 * Saved only with order attribution on, and never over attribution the order already has.
	 *
	 * @param \WC_Order        $order   The order being handed off.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private function record_attribution( \WC_Order $order, \WP_REST_Request $request ): void {
		$attribution = wc_get_container()->get( OrderAttributionController::class );

		if ( $attribution->has_attribution( $order ) ) {
			return;
		}

		// Every field is set, because the listener reads each one without checking it exists.
		$data = array_merge(
			array_fill_keys( $attribution->get_field_names(), '' ),
			array(
				'source_type' => 'ai_shopper',
				'utm_source'  => $this->agent_profile_host( (string) $request->get_header( 'UCP-Agent' ) ),
				'utm_medium'  => 'ucp',
			)
		);

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in OrderAttributionController.
		do_action( 'woocommerce_order_save_attribution_data', $order, $data );
	}

	/**
	 * Host of the `profile` parameter in a UCP-Agent header, or `ucp` when it is missing or not a hostname.
	 *
	 * Only a parameter named exactly `profile`, outside a quoted value, counts.
	 *
	 * @param string $header UCP-Agent header value.
	 * @return string
	 */
	private function agent_profile_host( string $header ): string {
		$profile = $this->quoted_header_parameter( $header, 'profile' );
		$host    = strtolower( (string) wp_parse_url( $profile, PHP_URL_HOST ) );

		if ( '' === $host ) {
			return 'ucp';
		}

		$valid = filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );

		return is_string( $valid ) ? $valid : 'ucp';
	}

	/**
	 * Value of one double-quoted parameter, matched by its exact name.
	 *
	 * A match counts only outside quotes, at the start of the header or after whitespace or a semicolon.
	 *
	 * @param string $header Header value.
	 * @param string $name   Parameter name.
	 * @return string Empty when the parameter is absent.
	 */
	private function quoted_header_parameter( string $header, string $name ): string {
		$prefix = $name . '="';
		$offset = 0;
		$length = strlen( $header );

		while ( $offset < $length ) {
			$start = strpos( $header, $prefix, $offset );
			if ( false === $start ) {
				return '';
			}

			// An even number of quotes before the name means it is outside a value.
			$outside_quotes = 0 === ( substr_count( substr( $header, 0, $start ), '"' ) % 2 );
			$is_parameter   = 0 === $start;
			if ( ! $is_parameter ) {
				$previous     = substr( $header, $start - 1, 1 );
				$is_parameter = ' ' === $previous || "\t" === $previous || ';' === $previous;
			}

			if ( $outside_quotes && $is_parameter ) {
				$value_start = $start + strlen( $prefix );
				$end         = strpos( $header, '"', $value_start );
				if ( false === $end ) {
					return '';
				}
				return substr( $header, $value_start, $end - $value_start );
			}

			$offset = $start + strlen( $prefix );
		}

		return '';
	}

	/**
	 * Return a checkout whose payment was already handed off, as it stands, with a warning that nothing was done.
	 *
	 * @param string               $checkout_id Checkout identifier.
	 * @param array<string, mixed> $cart Cart response.
	 * @return \WP_REST_Response
	 */
	private function already_handed_off_response( string $checkout_id, array $cart ): \WP_REST_Response {
		// Read again, so an order paid while this request ran is reported as it now stands.
		$response = $this->checkout_response( $checkout_id, (array) $this->checkout_state(), 200, $cart );
		$data     = (array) $response->get_data();

		if ( ! empty( $data['continue_url'] ) ) {
			$data['messages'][] = array(
				'type'         => 'warning',
				'code'         => 'ALREADY_HANDED_OFF',
				'content'      => __( 'Payment for this checkout session was already handed off, so it was returned unchanged. Update the checkout session to change it, or send the buyer to the payment page.', 'woocommerce' ),
				'path'         => '$.status',
				'content_type' => 'plain',
			);
			$response->set_data( $data );
		}

		return $response;
	}
}
