<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\CheckoutSessionStatus;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\ErrorCode;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Error;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic\CheckoutSessionSchema;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\AgenticCheckoutSession;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;

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
		$params = AgenticCheckoutUtils::get_shared_params();

		$params['fulfillment_option_id'] = [
			'description' => __( 'Selected fulfillment option ID.', 'woocommerce' ),
			'type'        => 'string',
		];

		return $params;
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
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$cart             = $this->cart_controller->get_cart_instance();
		$checkout_session = new AgenticCheckoutSession( $cart );

		$current_status = AgenticCheckoutUtils::calculate_status( $checkout_session );
		if ( ! in_array( $current_status, CheckoutSessionStatus::ALLOWED_STATUSES_FOR_UPDATE, true ) ) {
			$allowed_statuses = implode( ', ', CheckoutSessionStatus::ALLOWED_STATUSES_FOR_UPDATE );
			$message          = sprintf(
				/* translators: 1: current session status, 2: allowed statuses */
				__( 'Checkout session cannot be updated. Current status: %1$s. Allowed statuses: %2$s', 'woocommerce' ),
				$current_status,
				$allowed_statuses
			);
			return Error::invalid_request( ErrorCode::INVALID, $message )->to_rest_response();
		}

		// Update items if provided.
		$items = $request->get_param( 'items' );
		if ( null !== $items ) {
			// Clear existing cart items and replace with new ones.
			$this->cart_controller->empty_cart();

			$error = AgenticCheckoutUtils::add_items_to_cart(
				$items,
				$this->cart_controller,
				$checkout_session->get_messages()
			);
			if ( $error instanceof Error ) {
				return $error->to_rest_response();
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
			$option_id = wc_clean( (string) $fulfillment_option_id );
			$packages  = WC()->shipping()->get_packages();
			foreach ( $packages as $package ) {
				foreach ( (array) ( $package['rates'] ?? array() ) as $rate ) {
					if ( $rate->get_id() === $option_id ) {
						WC()->session->set( SessionKey::CHOSEN_SHIPPING_METHODS, array( $option_id ) );
						break 2;
					}
				}
			}
		}

		// Calculate totals after all updates.
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
