<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
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
	 * V1 implementation: Return true for now (skip auth check).
	 * Future: Implement Bearer token authentication.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if authorized, WP_Error otherwise.
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
		$error = AgenticCheckoutUtils::add_items_to_cart( $items, $this->cart_controller );
		if ( $error ) {
			return $error;
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

		// Set selected shipping method if provided.
		$fulfillment_option_id = $request->get_param( 'fulfillment_option_id' );
		if ( $fulfillment_option_id ) {
			WC()->session->set( 'chosen_shipping_methods', array( $fulfillment_option_id ) );
		}

		// Calculate totals after shipping method is set.
		WC()->cart->calculate_totals();

		// Build response from canonical cart schema.
		$response = rest_ensure_response( $this->schema->get_item_response( WC()->cart ) );

		// Add protocol headers.
		return AgenticCheckoutUtils::add_protocol_headers( $response, $request );
	}

	/**
	 * Handle cart updated event.
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
