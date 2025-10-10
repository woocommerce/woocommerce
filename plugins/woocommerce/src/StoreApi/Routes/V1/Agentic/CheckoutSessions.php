<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Error;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic\CheckoutSessionSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\AgenticCheckoutSession;

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
	const SCHEMA_TYPE = CheckoutSessionSchema::IDENTIFIER;

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
		return $this->get_path_regex();
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
		$params          = AgenticCheckoutUtils::get_shared_params();
		$params['items'] = array_merge(
			$params['items'],
			[
				'required' => true,
				'minItems' => 1,
			]
		);
		return $params;
	}

	/**
	 * Check if the request is authorized.
	 *
	 * Delegates to the AgenticCheckoutUtils helper.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if authorized, WP_Error otherwise.
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		return AgenticCheckoutUtils::is_authorized( $request );
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
		$checkout_session = new AgenticCheckoutSession( $this->cart_controller->get_cart_instance() );

		// Clear existing cart to start fresh for POST requests.
		$this->cart_controller->empty_cart();

		// Add items to cart.
		$items = $request->get_param( 'items' );
		$error = AgenticCheckoutUtils::add_items_to_cart( $items, $this->cart_controller, $checkout_session->get_messages() );
		// Halt for critical errors.
		if ( $error instanceof Error ) {
			return $error->to_rest_response();
		}

		// Set buyer information.
		$buyer = $request->get_param( 'buyer' );
		if ( $buyer ) {
			AgenticCheckoutUtils::set_buyer_data( $buyer, WC()->customer );
		}

		// Set fulfillment address.
		$address = $request->get_param( 'fulfillment_address' );
		if ( $address ) {
			AgenticCheckoutUtils::set_fulfillment_address( $address, WC()->customer );
		} else {
			// Clear address when not provided (POST creates fresh session).
			AgenticCheckoutUtils::clear_fulfillment_address( WC()->customer );
		}

		// Calculate totals.
		try {
			$this->cart_controller->calculate_totals();
		} catch ( \Exception $e ) {
			$message = wp_specialchars_decode( $e->getMessage(), ENT_QUOTES );
			return Error::processing_error( 'totals_calculation_error', $message )->to_rest_response();
		}

		// Build response from canonical cart schema.
		$response = $this->schema->get_item_response( $checkout_session );

		// Add protocol headers.
		return AgenticCheckoutUtils::add_protocol_headers( rest_ensure_response( $response ), $request );
	}
}
