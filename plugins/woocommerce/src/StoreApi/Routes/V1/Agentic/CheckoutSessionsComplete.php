<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic\CheckoutSessionSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CheckoutSessionsComplete class.
 *
 * Handles the Agentic Checkout API checkout sessions complete endpoint.
 * This endpoint allows AI agents to complete checkout sessions with payment.
 */
class CheckoutSessionsComplete extends AbstractCartRoute {
	use CheckoutTrait;
	use DraftOrderTrait;

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'agentic-checkout-sessions-complete';

	/**
	 * The route's schema type.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = CheckoutSessionSchema::IDENTIFIER;

	/**
	 * Order controller for managing orders.
	 *
	 * @var OrderController
	 */
	protected $order_controller;

	/**
	 * Cart controller for managing cart operations.
	 *
	 * @var CartController
	 */
	protected $cart_controller;

	/**
	 * The order object for the current request.
	 *
	 * @var \WC_Order|null
	 */
	protected $order;

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema Controller instance.
	 * @param AbstractSchema   $schema Schema class instance.
	 */
	public function __construct( $schema_controller, $schema ) {
		parent::__construct( $schema_controller, $schema );
		$this->order_controller = new OrderController();
		$this->cart_controller  = new CartController();
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path regex for this REST route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout_sessions/(?P<checkout_session_id>[a-zA-Z0-9._-]+)/complete';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => [
				'checkout_session_id' => [
					'description' => __( 'The checkout session ID (Cart-Token JWT).', 'woocommerce' ),
					'type'        => 'string',
				],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => $this->get_complete_params(),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get the parameters for completing a checkout session.
	 *
	 * @return array Parameters array.
	 */
	protected function get_complete_params() {
		$shared_params = AgenticCheckoutUtils::get_shared_params();

		return [
			'buyer'        => $shared_params['buyer'],
			'payment_data' => [
				'description' => __( 'Payment data including token and provider.', 'woocommerce' ),
				'type'        => 'object',
				'properties'  => [
					'token'           => [
						'description' => __( 'Payment token from the payment provider.', 'woocommerce' ),
						'type'        => 'string',
					],
					'provider'        => [
						'description' => __( 'Payment provider identifier.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => [ 'stripe' ],
					],
					'billing_address' => $shared_params['fulfillment_address'],
				],
				'required'    => [ 'token', 'provider' ],
			],
		];
	}

	/**
	 * Check if the request is authorized.
	 *
	 * Checks feature enablement and cart token validity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if authorized, WP_Error otherwise.
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		// Check if feature is enabled using helper.
		$auth_check = AgenticCheckoutUtils::is_authorized( $request );
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		// Additional check for cart token validity.
		if ( ! $this->has_cart_token( $request ) ) {
			return new \WP_Error(
				'woocommerce_rest_invalid_checkout_session',
				__( 'Invalid or expired checkout session ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	/**
	 * Use the checkout_session_id as Cart-Token, and set the respective values to HTTP header and request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|null
	 */
	protected function has_cart_token( \WP_REST_Request $request ) {
		$session_id = $request->get_param( 'checkout_session_id' );
		if ( is_null( $this->has_cart_token ) ) {
			$this->has_cart_token = CartTokenUtils::validate_cart_token( $session_id );
		}

		// This allows the session will be loaded later without any further intervention.
		if ( true === $this->has_cart_token ) {
			$request->set_header( 'Cart-Token', $session_id );
			$_SERVER['HTTP_CART_TOKEN'] = $session_id;
		}

		return $this->has_cart_token;
	}

	/**
	 * Check if a nonce is required for the route.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool False, Bearer token auth used instead.
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		// Should use `is_authorized` to validate Bearer token authentication.
		return false;
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		/**
		 * Before triggering validation, ensure totals are current and in turn, things such as shipping costs are present.
		 * This is so plugins that validate other cart data (e.g. conditional shipping and payments) can access this data.
		 */
		$this->cart_controller->calculate_totals();

		/**
		 * Validate that the cart is not empty.
		 */
		$this->cart_controller->validate_cart_not_empty();

		/**
		 * Validate items and fix violations before the order is processed.
		 */
		$this->cart_controller->validate_cart();

		/**
		 * Set buyer data if exists.
		 */
		$buyer = $request->get_param( 'buyer' );
		if ( null !== $buyer ) {
			AgenticCheckoutUtils::set_buyer_data( $buyer, WC()->customer );
		}

		/**
		 * Set billing address from payment_data if provided.
		 */
		$payment_data = $request->get_param( 'payment_data' );
		if ( isset( $payment_data['billing_address'] ) ) {
			AgenticCheckoutUtils::set_billing_address( $payment_data['billing_address'], WC()->customer );
		}

		/**
		 * Setup payment_data
		 */

		/**
		 * Get draft order (must exist from create/update).
		 */
		$this->order = $this->get_draft_order();
		if ( ! $this->order ) {
			return new \WP_REST_Response(
				[
					'type'    => 'invalid_request',
					'code'    => 'session_not_found',
					'message' => __( 'Checkout session not found or expired.', 'woocommerce' ),
				],
				404
			);
		}

		// 5. Set payment method from payment_data.
		$this->set_payment_method_from_request( $request );

		/**
		 * Validate updated order before payment is attempted.
		 */
		try {
			$this->order_controller->validate_order_before_payment( $this->order );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				[
					'type'    => 'invalid_request',
					'code'    => 'validation_failed',
					'message' => $e->getMessage(),
				],
				400
			);
		}

		try {
			wc_reserve_stock_for_order( $this->order );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				[
					'type'    => 'invalid_request',
					'code'    => 'stock_reservation_failed',
					'message' => $e->getMessage(),
				],
				400
			);
		}

		// 9. Fire pre-completion hook.
		do_action( 'woocommerce_store_api_checkout_order_processed', $this->order );

		// 10. Process payment (reuse CheckoutTrait).
		$payment_result = new PaymentResult();

		try {
			if ( $this->order->needs_payment() ) {
				$this->process_payment( $request, $payment_result );
			} else {
				$this->process_without_payment( $request, $payment_result );
			}
		} catch ( RouteException $e ) {
			return new \WP_REST_Response(
				[
					'type'    => 'processing_error',
					'code'    => $e->getErrorCode(),
					'message' => $e->getMessage(),
				],
				$e->getCode()
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				[
					'type'    => 'processing_error',
					'code'    => 'payment_failed',
					'message' => $e->getMessage(),
				],
				400
			);
		}

		// 11. If payment failed, return error.
		if ( 'failure' === $payment_result->status || 'error' === $payment_result->status ) {
			return new \WP_REST_Response(
				[
					'type'    => 'processing_error',
					'code'    => 'payment_declined',
					'message' => $payment_result->message ?? __( 'Payment was declined.', 'woocommerce' ),
				],
				400
			);
		}

		// 13. Build response from canonical cart schema with order.
		$response_data = $this->schema->get_item_response( WC()->cart );

		// Add order data.
		$response_data['order'] = [
			'id'                  => (string) $this->order->get_id(),
			'checkout_session_id' => $request->get_param( 'checkout_session_id' ),
			'permalink_url'       => $this->order->get_checkout_order_received_url(),
		];

		$response_data['status'] = 'completed';

		$response = rest_ensure_response( $response_data );

		return AgenticCheckoutUtils::add_protocol_headers( $response, $request );
	}

	/**
	 * Set payment method from agentic payment_data.
	 *
	 * Maps the payment provider to WooCommerce payment method ID.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @throws RouteException If payment method is invalid.
	 */
	private function set_payment_method_from_request( \WP_REST_Request $request ) {
		$payment_data = $request->get_param( 'payment_data' );
		$provider     = $payment_data['provider'] ?? '';

		// Map provider to WooCommerce payment method ID.
		// This mapping can be extended via filter.
		$payment_method_id = apply_filters(
			'woocommerce_agentic_payment_provider_to_method_id',
			$this->map_provider_to_payment_method( $provider ),
			$provider
		);

		if ( empty( $payment_method_id ) ) {
			throw new RouteException(
				'woocommerce_rest_invalid_payment_provider',
				sprintf(
					/* translators: %s: payment provider */
					__( 'Invalid payment provider: %s', 'woocommerce' ),
					$provider
				),
				400
			);
		}

		// Get the payment gateway.
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$payment_method     = $available_gateways[ $payment_method_id ] ?? null;

		if ( ! $payment_method ) {
			throw new RouteException(
				'woocommerce_rest_payment_method_not_available',
				sprintf(
					/* translators: %s: payment method ID */
					__( 'Payment method %s is not available.', 'woocommerce' ),
					$payment_method_id
				),
				400
			);
		}

		// Set payment method on order.
		WC()->session->set( 'chosen_payment_method', $payment_method_id );
		$this->order->set_payment_method( $payment_method );
		$this->order->save();
	}

	/**
	 * Map payment provider to WooCommerce payment method ID.
	 *
	 * @param string $provider Payment provider identifier.
	 * @return string Payment method ID.
	 */
	private function map_provider_to_payment_method( $provider ) {
		$mapping = [
			'stripe' => 'stripe', // Default mapping, can be overridden.
		];

		/**
		 * Filter the payment provider to method ID mapping.
		 *
		 * @param array $mapping Provider to method ID mapping.
		 */
		$mapping = apply_filters( 'woocommerce_agentic_payment_provider_mapping', $mapping );

		return $mapping[ $provider ] ?? '';
	}

	/**
	 * Gets and formats payment request data for CheckoutTrait.
	 *
	 * Transforms agentic payment_data format to Store API format.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	private function get_request_payment_data( \WP_REST_Request $request ) {
		$payment_data   = [];
		$agentic_data   = $request->get_param( 'payment_data' );

		if ( ! $agentic_data ) {
			return $payment_data;
		}

		// Transform agentic format to Store API payment_data format.
		if ( isset( $agentic_data['token'] ) ) {
			$payment_data['token'] = wc_clean( $agentic_data['token'] );
		}

		if ( isset( $agentic_data['provider'] ) ) {
			$payment_data['provider'] = wc_clean( $agentic_data['provider'] );
		}

		/**
		 * Filter the transformed payment data.
		 *
		 * @param array $payment_data Transformed payment data.
		 * @param array $agentic_data Original agentic payment data.
		 * @param \WP_REST_Request $request Request object.
		 */
		return apply_filters( 'woocommerce_agentic_payment_data', $payment_data, $agentic_data, $request );
	}

	/**
	 * Gets the chosen payment method ID from the request.
	 *
	 * Required by CheckoutTrait.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 */
	private function get_request_payment_method_id( \WP_REST_Request $request ) {
		$payment_data = $request->get_param( 'payment_data' );
		$provider     = $payment_data['provider'] ?? '';

		return $this->map_provider_to_payment_method( $provider );
	}
}
