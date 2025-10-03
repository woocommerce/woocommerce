<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * CheckoutSessions class.
 *
 * Handles the Agentic Checkout API checkout sessions endpoint.
 * This endpoint allows AI agents to create and manage checkout sessions.
 */
class CheckoutSessions extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'agentic-checkout-sessions';

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
		return '/checkout_sessions';
	}

	/**
	 * Get the path regex for this REST route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout_sessions';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => $this->get_create_params(),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get the parameters for creating a checkout session.
	 *
	 * @return array Parameters array.
	 */
	protected function get_create_params() {
		return [
			'items'                 => [
				'description' => __( 'Line items to add to the cart.', 'woocommerce' ),
				'type'        => 'array',
				'required'    => true,
				'minItems'    => 1,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'       => [
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => 'string',
						],
						'quantity' => [
							'description' => __( 'Quantity.', 'woocommerce' ),
							'type'        => 'integer',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'id', 'quantity' ],
				],
			],
			'buyer'                 => [
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
			'fulfillment_address'   => [
				'description' => __( 'Fulfillment/shipping address.', 'woocommerce' ),
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
						'description' => __( 'State/province.', 'woocommerce' ),
						'type'        => 'string',
					],
					'country'     => [
						'description' => __( 'Country code (ISO 3166-1 alpha-2).', 'woocommerce' ),
						'type'        => 'string',
					],
					'postal_code' => [
						'description' => __( 'Postal/ZIP code.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
				'required'    => [ 'line_one', 'city', 'country', 'postal_code' ],
			],
			'fulfillment_option_id' => [
				'description' => __( 'Selected fulfillment option ID.', 'woocommerce' ),
				'type'        => 'string',
			],
		];
	}

	/**
	 * Check if the request is authorized.
	 *
	 * V1 implementation: Return true for now (skip auth check).
	 * Future: Implement Bearer token authentication.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool True if authorized.
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		// Check if feature is enabled.
		$features_controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $features_controller->feature_is_enabled( 'agentic_checkout' ) ) {
			return new \WP_Error(
				'woocommerce_rest_agentic_checkout_disabled',
				__( 'Agentic Checkout API is not enabled.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// V1: Allow all requests (implement proper auth in future).
		return true;
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
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {

		// Add items to cart.
		$items = $request->get_param( 'items' );

		foreach ( $items as $index => $item ) {
			if ( ! is_numeric( $item['id'] ) ) {
				return new \WP_REST_Response(
					[
						'type'    => 'invalid_request',
						'code'    => 'invalid_product_id',
						'message' => __( 'Product ID must be numeric.', 'woocommerce' ),
						'param'   => '$.items[' . $index . '].id',
					],
					400
				);
			}

			$product_id = (int) $item['id'];
			$quantity   = (int) $item['quantity'];

			try {
				$this->cart_controller->add_to_cart(
					[
						'id'       => $product_id,
						'quantity' => $quantity,
					]
				);
			} catch ( \Exception $e ) {
				// Map exception messages to protocol-compliant error responses.
				$error_code    = 'invalid_product';
				$error_message = $e->getMessage();

				// Detect specific error types from exception message.
				if ( strpos( $error_message, 'stock' ) !== false ) {
					$error_code = 'out_of_stock';
				} elseif ( strpos( $error_message, 'purchasable' ) !== false || strpos( $error_message, 'available' ) !== false ) {
					$error_code = 'product_not_purchasable';
				} elseif ( strpos( $error_message, 'not found' ) !== false || strpos( $error_message, 'does not exist' ) !== false ) {
					$error_code = 'invalid_product';
				}

				return new \WP_REST_Response(
					[
						'type'    => 'invalid_request',
						'code'    => $error_code,
						'message' => $error_message,
						'param'   => '$.items[' . $index . ']',
					],
					400
				);
			}
		}

		// Set buyer information.
		$buyer = $request->get_param( 'buyer' );
		if ( $buyer ) {
			$this->set_buyer_data( $buyer );
		}

		// Set fulfillment address.
		$address = $request->get_param( 'fulfillment_address' );
		if ( $address ) {
			$this->set_fulfillment_address( $address );
		} else {
			// Clear address when not provided (POST creates fresh session).
			$this->clear_fulfillment_address();
		}

		// Set selected shipping method if provided.
		$fulfillment_option_id = $request->get_param( 'fulfillment_option_id' );
		if ( $fulfillment_option_id ) {
			WC()->session->set( 'chosen_shipping_methods', array( $fulfillment_option_id ) );
		}

		// Calculate totals after shipping method is set.
		WC()->cart->calculate_totals();

		// Build response from canonical cart schema.
		$response = rest_ensure_response( $this->schema->get_item_response( WC()->cart ) );

		// Echo Agentic Commerce Protocol headers if provided.
		$idempotency_key = $request->get_header( 'Idempotency-Key' );
		if ( $idempotency_key ) {
			$response->header( 'Idempotency-Key', $idempotency_key );
		}

		$request_id = $request->get_header( 'Request-Id' );
		if ( $request_id ) {
			$response->header( 'Request-Id', $request_id );
		}

		return $response;
	}

	/**
	 * Set buyer data on customer.
	 *
	 * @param array $buyer Buyer data.
	 */
	protected function set_buyer_data( $buyer ) {
		$customer = WC()->customer;

		if ( isset( $buyer['first_name'] ) ) {
			$first_name = wc_clean( wp_unslash( $buyer['first_name'] ) );
			$customer->set_billing_first_name( $first_name );
			$customer->set_shipping_first_name( $first_name );
		}

		if ( isset( $buyer['last_name'] ) ) {
			$last_name = wc_clean( wp_unslash( $buyer['last_name'] ) );
			$customer->set_billing_last_name( $last_name );
			$customer->set_shipping_last_name( $last_name );
		}

		if ( isset( $buyer['email'] ) ) {
			$email = sanitize_email( wp_unslash( $buyer['email'] ) );
			if ( is_email( $email ) ) {
				$customer->set_billing_email( $email );
			}
		}

		if ( isset( $buyer['phone_number'] ) ) {
			$phone = wc_clean( wp_unslash( $buyer['phone_number'] ) );
			$customer->set_billing_phone( $phone );
		}

		$customer->save();
	}

	/**
	 * Set fulfillment address on customer.
	 *
	 * @param array $address Address data.
	 */
	protected function set_fulfillment_address( $address ) {
		$customer = WC()->customer;

		// Parse name into first and last.
		$name       = isset( $address['name'] ) ? wc_clean( wp_unslash( $address['name'] ) ) : '';
		$name_parts = $name ? explode( ' ', $name, 2 ) : array( '', '' );
		$first_name = $name_parts[0];
		$last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';

		// Sanitize all address fields.
		$line_one    = wc_clean( wp_unslash( $address['line_one'] ?? '' ) );
		$line_two    = wc_clean( wp_unslash( $address['line_two'] ?? '' ) );
		$city        = wc_clean( wp_unslash( $address['city'] ?? '' ) );
		$state       = wc_clean( wp_unslash( $address['state'] ?? '' ) );
		$postal_code = wc_clean( wp_unslash( $address['postal_code'] ?? '' ) );
		$country     = wc_clean( wp_unslash( $address['country'] ?? '' ) );

		// Set shipping address.
		$customer->set_shipping_first_name( $first_name );
		$customer->set_shipping_last_name( $last_name );
		$customer->set_shipping_address_1( $line_one );
		$customer->set_shipping_address_2( $line_two );
		$customer->set_shipping_city( $city );
		$customer->set_shipping_state( $state );
		$customer->set_shipping_postcode( $postal_code );
		$customer->set_shipping_country( $country );

		// Also set as billing address if not already set.
		if ( ! $customer->get_billing_address_1() ) {
			$customer->set_billing_first_name( $first_name );
			$customer->set_billing_last_name( $last_name );
			$customer->set_billing_address_1( $line_one );
			$customer->set_billing_address_2( $line_two );
			$customer->set_billing_city( $city );
			$customer->set_billing_state( $state );
			$customer->set_billing_postcode( $postal_code );
			$customer->set_billing_country( $country );
		}

		$customer->save();

		// Recalculate shipping.
		WC()->cart->calculate_shipping();
	}

	/**
	 * Clear fulfillment address from customer.
	 */
	protected function clear_fulfillment_address() {
		$customer = WC()->customer;

		// Clear shipping address.
		$customer->set_shipping_first_name( '' );
		$customer->set_shipping_last_name( '' );
		$customer->set_shipping_address_1( '' );
		$customer->set_shipping_address_2( '' );
		$customer->set_shipping_city( '' );
		$customer->set_shipping_state( '' );
		$customer->set_shipping_postcode( '' );
		$customer->set_shipping_country( '' );

		$customer->save();

		// Recalculate shipping.
		WC()->cart->calculate_shipping();
	}

	/**
	 * When the cart is updated, create or update draft order.
	 * Overrides parent to ensure draft order is created if it doesn't exist.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function cart_updated( \WP_REST_Request $request ) {
		// Only create/update draft order if cart has items.
		// This prevents errors when validation fails and cart is empty.
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			$draft_order = $this->get_draft_order();

			if ( ! $draft_order ) {
				// Create new draft order from cart using core OrderController.
				$draft_order = $this->order_controller->create_order_from_cart();
				$draft_order->save();
				$this->set_draft_order_id( $draft_order->get_id() );
			}

			parent::cart_updated( $request );
		}
	}
}
