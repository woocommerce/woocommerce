<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * CheckoutSessionsUpdate class.
 *
 * Handles the Agentic Checkout API checkout sessions update endpoint.
 * This endpoint allows AI agents to update existing checkout sessions.
 */
class CheckoutSessionsUpdate extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'agentic-checkout-sessions-update';

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
		return '/checkout_sessions/(?P<checkout_session_id>[a-zA-Z0-9._-]+)';
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
				'args'                => $this->get_update_params(),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get the parameters for updating a checkout session.
	 *
	 * @return array Parameters array.
	 */
	protected function get_update_params() {
		return AgenticCheckoutUtils::get_shared_params();
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

		if ( ! $this->has_cart_token( $request ) ) {
			return new \WP_Error(
				'woocommerce_rest_invalid_checkout_session',
				__( 'Invalid or expired checkout session ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// V1: Allow all requests (implement proper auth in future).
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
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		// Update items if provided.
		$items = $request->get_param( 'items' );
		if ( null !== $items ) {
			// Clear existing cart items and replace with new ones.
			WC()->cart->empty_cart();

			$error = AgenticCheckoutUtils::add_items_to_cart( $items, $this->cart_controller );
			if ( $error ) {
				return $error;
			}
		}

		// Update buyer information if provided.
		$buyer = $request->get_param( 'buyer' );
		if ( null !== $buyer ) {
			AgenticCheckoutUtils::set_buyer_data( $buyer, WC()->customer );
		}

		// Update fulfillment address if provided.
		$address = $request->get_param( 'fulfillment_address' );
		if ( null !== $address ) {
			AgenticCheckoutUtils::set_fulfillment_address( $address, WC()->customer );
		}

		// Update selected shipping method if provided.
		$fulfillment_option_id = $request->get_param( 'fulfillment_option_id' );
		if ( null !== $fulfillment_option_id ) {
			WC()->session->set( 'chosen_shipping_methods', array( $fulfillment_option_id ) );
		}

		// Calculate totals after all updates.
		WC()->cart->calculate_totals();

		// Build response from canonical cart schema.
		$response = rest_ensure_response( $this->schema->get_item_response( WC()->cart ) );

		// Add protocol headers.
		return AgenticCheckoutUtils::add_protocol_headers( $response, $request );
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
