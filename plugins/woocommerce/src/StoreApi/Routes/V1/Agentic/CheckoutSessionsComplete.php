<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;

/**
 * CheckoutSessionsComplete class.
 *
 * Handles the Agentic Checkout API checkout sessions complete endpoint.
 * This endpoint finalizes checkout sessions with payment processing.
 */
class CheckoutSessionsComplete extends AbstractCartRoute {
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
	const SCHEMA_TYPE = 'agentic-checkout-session';

	/**
	 * Order controller for managing draft orders.
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
					'required'    => true,
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
		return [
			'buyer'        => [
				'description' => __( 'Buyer information.', 'woocommerce' ),
				'type'        => 'object',
				'properties'  => [
					'first_name'   => [
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'last_name'    => [
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'email'        => [
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => 'string',
						'format'      => 'email',
					],
					'phone_number' => [
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'payment_data' => [
				'description' => __( 'Payment data for processing.', 'woocommerce' ),
				'type'        => 'object',
				'required'    => true,
				'properties'  => [
					'token'           => [
						'description' => __( 'Payment token, method ID, or source ID.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'provider'        => [
						'description' => __( 'Payment provider identifier (e.g., stripe).', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'billing_address' => [
						'description' => __( 'Billing address.', 'woocommerce' ),
						'type'        => 'object',
						'properties'  => [
							'name'        => [
								'description' => __( 'Full name.', 'woocommerce' ),
								'type'        => 'string',
							],
							'line_one'    => [
								'description' => __( 'Address line 1.', 'woocommerce' ),
								'type'        => 'string',
							],
							'line_two'    => [
								'description' => __( 'Address line 2.', 'woocommerce' ),
								'type'        => 'string',
							],
							'city'        => [
								'description' => __( 'City.', 'woocommerce' ),
								'type'        => 'string',
							],
							'state'       => [
								'description' => __( 'State/Province.', 'woocommerce' ),
								'type'        => 'string',
							],
							'country'     => [
								'description' => __( 'Country code.', 'woocommerce' ),
								'type'        => 'string',
							],
							'postal_code' => [
								'description' => __( 'Postal/ZIP code.', 'woocommerce' ),
								'type'        => 'string',
							],
						],
					],
				],
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
		$auth_check = AgenticCheckoutUtils::is_authorized();
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
		if ( is_null( $this->has_cart_token ) ) {
			$this->has_cart_token = AgenticCheckoutUtils::validate_and_set_cart_token( $request );
		}
		return $this->has_cart_token;
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$payment_data = $request->get_param( 'payment_data' );
		$buyer        = $request->get_param( 'buyer' );

		// Validate cart has items.
		if ( WC()->cart->is_empty() ) {
			return new \WP_Error(
				'woocommerce_rest_checkout_empty_cart',
				__( 'Cannot complete checkout with an empty cart.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Get the draft order.
		$draft_order = $this->get_draft_order();
		if ( ! $draft_order ) {
			return new \WP_Error(
				'woocommerce_rest_checkout_empty_draft_order',
				__( 'Cannot complete checkout with an empty draft order.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * Fires before processing an agentic checkout payment.
		 *
		 * @since 10.3.0
		 *
		 * @internal This hook is experimental and subject to change.
		 *
		 * @param \WC_Order        $order        The order being processed.
		 * @param array            $payment_data Payment data including token and provider.
		 * @param \WP_REST_Request $request      The complete request object.
		 */
		do_action( 'woocommerce_before_agentic_payment', $draft_order, $payment_data, $request );

		// Update buyer information.
		if ( $buyer ) {
			AgenticCheckoutUtils::set_buyer_data( $buyer, WC()->customer );
			// Also update the order with buyer data.
			if ( isset( $buyer['first_name'] ) ) {
				$draft_order->set_billing_first_name( $buyer['first_name'] );
			}
			if ( isset( $buyer['last_name'] ) ) {
				$draft_order->set_billing_last_name( $buyer['last_name'] );
			}
			if ( isset( $buyer['email'] ) ) {
				$draft_order->set_billing_email( $buyer['email'] );
			}
			if ( isset( $buyer['phone_number'] ) ) {
				$draft_order->set_billing_phone( $buyer['phone_number'] );
			}
		}

		// Set billing address if provided.
		if ( isset( $payment_data['billing_address'] ) ) {
			// Convert billing address to customer format and use the helper.
			$billing_as_fulfillment = $payment_data['billing_address'];
			AgenticCheckoutUtils::set_fulfillment_address( $billing_as_fulfillment, WC()->customer );

			// Also update order directly with billing address.
			$this->set_order_billing_address( $draft_order, $payment_data['billing_address'] );
		}

		// Process the payment.
		$payment_result = $this->process_payment( $draft_order, $payment_data );

		if ( ! is_wp_error( $payment_result ) && 'success' !== $payment_result['result'] ) {
			$payment_result = new \WP_Error(
				'woocommerce_rest_checkout_payment_failed',
				$payment_result['message'] ?? __( 'Payment processing failed.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( is_wp_error( $payment_result ) ) {
			/**
			 * Fires when an agentic payment fails.
			 *
			 * @since 10.3.0
			 *
			 * @internal This hook is experimental and subject to change.
			 *
			 * @param \WC_Order $order        The order that failed payment.
			 * @param \WP_Error $error        The error object.
			 * @param array     $payment_data Payment data that was attempted.
			 */
			do_action( 'woocommerce_agentic_payment_failed', $draft_order, $payment_result, $payment_data );

			return $payment_result;
		}

		/**
		 * Fires after successful agentic payment processing.
		 *
		 * @since 10.3.0
		 *
		 * @internal This hook is experimental and subject to change.
		 *
		 * @param \WC_Order $order        The order that was successfully paid.
		 * @param array     $result       The successful payment result.
		 * @param array     $payment_data Payment data that was used.
		 */
		do_action( 'woocommerce_agentic_payment_complete', $draft_order, $payment_result, $payment_data );

		// Clear the cart after successful payment.
		WC()->cart->empty_cart();

		// Build response.
		$response_data           = $this->schema->get_item_response( WC()->cart );
		$response_data['status'] = 'completed';
		$response_data['order']  = array(
			'id'                  => (string) $draft_order->get_id(),
			'checkout_session_id' => $request->get_param( 'checkout_session_id' ),
			'permalink_url'       => $draft_order->get_checkout_order_received_url(),
		);

		$response = rest_ensure_response( $response_data );

		// Add protocol headers.
		return AgenticCheckoutUtils::add_protocol_headers( $response, $request );
	}

	/**
	 * Process payment using the appropriate gateway.
	 *
	 * @param \WC_Order $order        Order to process payment for.
	 * @param array     $payment_data Payment data including token and provider.
	 * @return array|\WP_Error Payment result or error.
	 */
	protected function process_payment( $order, $payment_data ) {
		$provider = $payment_data['provider'];
		$token    = $payment_data['token'];

		// Map provider to gateway ID.
		$gateway_id = $this->get_gateway_id_from_provider( $provider );

		if ( ! $gateway_id ) {
			return new \WP_Error(
				'woocommerce_rest_checkout_invalid_payment_provider',
				sprintf( __( 'Payment provider "%s" is not supported.', 'woocommerce' ), $provider ),
				array( 'status' => 400 )
			);
		}

		// Get available payment gateways.
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( ! isset( $available_gateways[ $gateway_id ] ) ) {
			return new \WP_Error(
				'woocommerce_rest_checkout_gateway_unavailable',
				sprintf( __( 'Payment gateway "%s" is not available.', 'woocommerce' ), $gateway_id ),
				array( 'status' => 400 )
			);
		}

		$gateway = $available_gateways[ $gateway_id ];

		// Set payment method on order.
		$order->set_payment_method( $gateway );
		$order->save();

		/**
		 * Filters the result of token-based payment processing.
		 *
		 * Allows gateways or third-party code to handle token payment processing
		 * if the gateway doesn't natively support it.
		 *
		 * @since 10.3.0
		 *
		 * @param array|null          $result       The payment result, or null to continue default processing.
		 * @param \WC_Order           $order        The order being processed.
		 * @param \WC_Payment_Gateway $gateway      The payment gateway instance.
		 * @param string              $token        The payment token.
		 * @param array               $payment_data Full payment data array.
		 */
		$result = apply_filters(
			'woocommerce_process_payment_token',
			null,
			$order,
			$gateway,
			$token,
			$payment_data
		);

		// If no filter handled it, try the gateway's method.
		if ( is_null( $result ) && method_exists( $gateway, 'process_payment_token' ) ) {
			$result = $gateway->process_payment_token(
				$order->get_id(),
				$token,
				$payment_data
			);
		}

		// If still no result, fall back to legacy processing.
		if ( is_null( $result ) ) {
			$result = $this->process_payment_legacy( $order, $gateway, $payment_data );
		}

		return $result;
	}

	/**
	 * Legacy payment processing using $_POST manipulation.
	 *
	 * @param \WC_Order           $order        Order to process.
	 * @param \WC_Payment_Gateway $gateway      Payment gateway.
	 * @param array               $payment_data Payment data.
	 * @return array Payment result.
	 */
	protected function process_payment_legacy( $order, $gateway, $payment_data ) {
		// Store original POST data.
		$original_post = $_POST;

		try {
			// Set up $_POST for the gateway.
			$_POST                   = array();
			$_POST['payment_method'] = $gateway->id;

			// Add gateway-specific token fields.
			if ( 'stripe' === $gateway->id ) {
				if ( strpos( $payment_data['token'], 'pm_' ) === 0 ) {
					$_POST['stripe_source'] = $payment_data['token'];
				} else {
					$_POST['stripe_token'] = $payment_data['token'];
				}
			} elseif ( 'woocommerce_payments' === $gateway->id ) {
				$_POST['wcpay-payment-method'] = $payment_data['token'];
			}

			// Process payment.
			return $gateway->process_payment( $order->get_id() );

		} finally {
			// Restore original POST data.
			$_POST = $original_post;
		}
	}

	/**
	 * Map payment provider to gateway ID.
	 *
	 * @param string $provider Provider identifier.
	 * @return string|null Gateway ID or null if not found.
	 */
	protected function get_gateway_id_from_provider( $provider ) {
		/**
		 * Filters the mapping of payment providers to gateway IDs.
		 *
		 * @since 10.3.0
		 *
		 * @param array $mapping Provider to gateway ID mapping.
		 */
		$mapping = apply_filters(
			'woocommerce_agentic_payment_provider_mapping',
			array(
				'stripe'               => 'stripe',
				'woocommerce_payments' => 'woocommerce_payments',
				'paypal'               => 'paypal',
				// Add more mappings as needed.
			)
		);

		return $mapping[ $provider ] ?? null;
	}


	/**
	 * Set billing address on order.
	 *
	 * @param \WC_Order $order   Order to update.
	 * @param array     $address Billing address data.
	 */
	protected function set_order_billing_address( $order, $address ) {
		// Parse name into first and last if provided as single field.
		if ( isset( $address['name'] ) && ! empty( $address['name'] ) ) {
			$name_parts = explode( ' ', $address['name'], 2 );
			$order->set_billing_first_name( $name_parts[0] );
			if ( isset( $name_parts[1] ) ) {
				$order->set_billing_last_name( $name_parts[1] );
			}
		}

		if ( isset( $address['line_one'] ) ) {
			$order->set_billing_address_1( $address['line_one'] );
		}
		if ( isset( $address['line_two'] ) ) {
			$order->set_billing_address_2( $address['line_two'] );
		}
		if ( isset( $address['city'] ) ) {
			$order->set_billing_city( $address['city'] );
		}
		if ( isset( $address['state'] ) ) {
			$order->set_billing_state( $address['state'] );
		}
		if ( isset( $address['country'] ) ) {
			$order->set_billing_country( $address['country'] );
		}
		if ( isset( $address['postal_code'] ) ) {
			$order->set_billing_postcode( $address['postal_code'] );
		}

		$order->save();
	}
}
