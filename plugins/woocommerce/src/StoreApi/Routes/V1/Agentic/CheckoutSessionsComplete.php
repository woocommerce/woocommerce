<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\ErrorCode;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\CheckoutSessionStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Error;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic\CheckoutSessionSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
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
		$checkout_session = new AgenticCheckoutSession( $this->cart_controller->get_cart_instance() );

		AgenticCheckoutUtils::validate( $checkout_session );

		/**
		 * Verify checkout session is ready for payment.
		 */
		$current_status = AgenticCheckoutUtils::calculate_status( $checkout_session );
		if ( CheckoutSessionStatus::READY_FOR_PAYMENT !== $current_status ) {
			$message = sprintf(
				/* translators: %s: current session status */
				__( 'Checkout session is not ready for payment. Current status: %s', 'woocommerce' ),
				$current_status
			);
			return Error::invalid_request( ErrorCode::INVALID, $message )->to_rest_response();
		}

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

		try {
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
		} catch ( \Exception $e ) {
			$message = wp_specialchars_decode( $e->getMessage(), ENT_QUOTES );
			return Error::processing_error( ErrorCode::INVALID, $message )->to_rest_response();
		}

		/**
		 * Similar to Checkout::create_or_update_draft_order.
		 * Can move this to CheckoutTrait to share between Checkout.php and this controller.
		 */
		$this->order = $this->get_draft_order();
		if ( ! $this->order ) {
			$this->order = $this->order_controller->create_order_from_cart();
		} else {
			$this->order_controller->update_order_from_cart( $this->order, true );
		}

		/**
		 * Stores the checkout session ID to the order meta.
		 */
		$this->order->update_meta_data( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID, $request->get_param( 'checkout_session_id' ) );
		$this->order->save_meta_data();

		/**
		 * Validate updated order before payment is attempted.
		 */
		try {
			$this->order_controller->validate_order_before_payment( $this->order );
		} catch ( \Exception $e ) {
			$message = wp_specialchars_decode( $e->getMessage(), ENT_QUOTES );
			return Error::invalid_request( ErrorCode::INVALID, $message )->to_rest_response();
		}

		try {
			wc_reserve_stock_for_order( $this->order );
		} catch ( \Exception $e ) {
			$message = wp_specialchars_decode( $e->getMessage(), ENT_QUOTES );
			return Error::invalid_request( ErrorCode::INVALID, $message )->to_rest_response();
		}

		// Set the order status to 'pending' as an initial step.
		$this->order->update_status( 'pending' );

		/**
		 * Process payment (reuse CheckoutTrait).
		 */
		$payment_result = new PaymentResult();

		try {
			/**
			 * Set IN_PROGRESS status to prevent concurrent payment attempts.
			 * Save this status right away so that any concurrent request will not be able to access the payment process.
			 */
			WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, true );
			WC()->session->save_data();

			$this->process_payment( $request, $payment_result );
		} catch ( \Exception $e ) {
			$message = wp_specialchars_decode( $e->getMessage(), ENT_QUOTES );
			return Error::processing_error( ErrorCode::INVALID, $message )->to_rest_response();
		} finally {
			/**
			 * Clear IN_PROGRESS status after payment attempt.
			 * Do not save session here as it will be done after the shutdown.
			 */
			WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, false );
		}

		/**
		 * If payment failed, return error.
		 */
		if ( 'failure' === $payment_result->status || 'error' === $payment_result->status ) {
			// Clear IN_PROGRESS status to allow retry.
			$message = $payment_result->message ?? __( 'Payment was declined.', 'woocommerce' );
			$message = wp_specialchars_decode( $message, ENT_QUOTES );
			return Error::processing_error( ErrorCode::PAYMENT_DECLINED, $message )->to_rest_response();
		}

		/**
		 * Store the completed order ID into the session. This will prevent new orders in this session.
		 */
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID, $this->order->get_id() );

		/**
		 * Build response from canonical cart schema.
		 */
		$response_data = $this->schema->get_item_response( $checkout_session );
		$response      = rest_ensure_response( $response_data );

		return AgenticCheckoutUtils::add_protocol_headers( $response, $request );
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
		$payment_data = [];
		$agentic_data = $request->get_param( 'payment_data' );

		if ( ! $agentic_data ) {
			return $payment_data;
		}

		// Transform agentic format to Store API payment_data format.
		if ( isset( $agentic_data['token'] ) ) {
			$payment_data['wc-agentic_commerce-token'] = wc_clean( $agentic_data['token'] );
		}

		if ( isset( $agentic_data['provider'] ) ) {
			$payment_data['wc-agentic_commerce-provider'] = wc_clean( $agentic_data['provider'] );
		}

		return $payment_data;
	}

	/**
	 * Gets the chosen payment method (gateway) ID for CheckoutTrait.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 * @throws RouteException If no payment gateway is available.
	 */
	private function get_request_payment_method_id( \WP_REST_Request $request ) {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			throw new RouteException(
				'woocommerce_checkout_session_no_payment_gateway_available',
				esc_html__( 'No payment gateway available.', 'woocommerce' ),
				400
			);
		}

		// Look for gateway with agentic_commerce capability.
		$gateway = AgenticCheckoutUtils::get_agentic_commerce_gateway( $available_gateways );

		if ( null === $gateway ) {
			throw new RouteException(
				'woocommerce_checkout_session_no_agentic_payment_gateway_available',
				esc_html__( 'No agentic-supported payment gateway available.', 'woocommerce' ),
				400
			);
		}

		return $gateway->id;
	}
}
