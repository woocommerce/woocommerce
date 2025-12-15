<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\POSSessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\POSUtils;

/**
 * Abstract Cart Route
 */
abstract class AbstractCartRoute extends AbstractRoute {
	use DraftOrderTrait;

	/**
	 * The route's schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'cart';

	/**
	 * Schema class instance.
	 *
	 * @var CartSchema
	 */
	protected $schema;

	/**
	 * Schema class for the cart.
	 *
	 * @var CartSchema
	 */
	protected $cart_schema;

	/**
	 * Schema class for the cart item.
	 *
	 * @var CartItemSchema
	 */
	protected $cart_item_schema;

	/**
	 * Cart controller class instance.
	 *
	 * @var CartController
	 */
	protected $cart_controller;

	/**
	 * Order controller class instance.
	 *
	 * @var OrderController
	 */
	protected $order_controller;

	/**
	 * Additional fields controller class instance.
	 *
	 * @var CheckoutFields
	 */
	protected $additional_fields_controller;

	/**
	 * True when this route has been requested with a valid cart token.
	 *
	 * @var bool|null
	 */
	protected $has_cart_token = null;

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema Controller instance.
	 * @param AbstractSchema   $schema Schema class for this route.
	 */
	public function __construct( SchemaController $schema_controller, AbstractSchema $schema ) {
		parent::__construct( $schema_controller, $schema );

		$this->cart_schema                  = $this->schema_controller->get( CartSchema::IDENTIFIER );
		$this->cart_item_schema             = $this->schema_controller->get( CartItemSchema::IDENTIFIER );
		$this->cart_controller              = new CartController();
		$this->additional_fields_controller = Package::container()->get( CheckoutFields::class );
		$this->order_controller             = new OrderController();
	}

	/**
	 * Are we updating data or getting data?
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return boolean
	 */
	protected function is_update_request( \WP_REST_Request $request ) {
		return in_array( $request->get_method(), [ 'POST', 'PUT', 'PATCH', 'DELETE' ], true );
	}

	/**
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$response = null;

		try {
			$this->load_cart_session( $request );
		} catch ( RouteException $error ) {
			$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
		}

		if ( ! $response ) {
			$nonce_check = $this->requires_nonce( $request ) ? $this->check_nonce( $request ) : null;

			if ( is_wp_error( $nonce_check ) ) {
				$response = $nonce_check;
			}
		}

		if ( ! $response ) {
			try {
				$response = $this->get_response_by_request_method( $request );
			} catch ( RouteException $error ) {
				$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
			} catch ( \Exception $error ) {
				$response = $this->get_route_error_response( 'woocommerce_rest_unknown_server_error', $error->getMessage(), 500 );
			}
		}

		// For update requests, this will recalculate cart totals and sync draft orders with the current cart.
		if ( $this->is_update_request( $request ) ) {
			$this->cart_updated( $request );
		}

		// Format error responses.
		if ( is_wp_error( $response ) ) {
			$response = $this->error_to_response( $response );
		}

		return $this->add_response_headers( rest_ensure_response( $response ) );
	}

	/**
	 * Add nonce headers to a response object.
	 *
	 * @param \WP_REST_Response $response The response object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function add_response_headers( \WP_REST_Response $response ) {
		$nonce = wp_create_nonce( 'wc_store_api' );

		$response->header( 'Nonce', $nonce );
		$response->header( 'Nonce-Timestamp', time() );
		$response->header( 'User-ID', get_current_user_id() );
		$response->header( 'Cart-Token', $this->get_cart_token() );
		$response->header( 'Cart-Hash', WC()->cart->get_cart_hash() );

		return $response;
	}

	/**
	 * Load the cart session before handling responses.
	 *
	 * @throws RouteException If POS header is present but user is not authorized.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function load_cart_session( \WP_REST_Request $request ) {
		// Check for cart token first (for non-POS requests).
		if ( $this->has_cart_token( $request ) ) {
			// Overrides the core session class.
			add_filter(
				'woocommerce_session_handler',
				function () {
					return SessionHandler::class;
				}
			);
		}

		// POS session setup runs after and takes precedence over cart token.
		$this->maybe_use_pos_session( $request );

		$this->cart_controller->load_cart();
		$this->cart_controller->normalize_cart();

		// For POS sessions, clear the store operator's data from the customer object.
		// The customer being served is not the logged-in user (the store operator).
		$this->maybe_clear_pos_customer_data();
	}

	/**
	 * Clear customer data that was auto-populated from the store operator's account.
	 *
	 * In POS, the logged-in user is the store operator, not the customer. If the
	 * session has no customer data (fresh transaction), we need to clear any data
	 * that was loaded from the operator's WordPress user account.
	 */
	protected function maybe_clear_pos_customer_data(): void {
		if ( ! wc_is_pos_session() ) {
			return;
		}

		// Check if this is a fresh transaction (no customer data in session).
		$session_customer = wc()->session->get( 'customer' );
		if ( ! empty( $session_customer ) ) {
			// Session has customer data, don't override it.
			return;
		}

		// Clear the billing email that was loaded from the store operator's account.
		// The WC_Customer was created with the logged-in user's ID, which loads their email.
		$customer = wc()->customer;
		if ( $customer && $customer->get_id() > 0 ) {
			$customer->set_billing_email( '' );
		}
	}

	/**
	 * Check if this request should use a POS session and set up the handler.
	 *
	 * POS sessions require:
	 * 1. The X-WC-POS header to be present
	 * 2. An authenticated user with the manage_woocommerce capability
	 *
	 * @throws RouteException If POS header is present but user is not authorized.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function maybe_use_pos_session( \WP_REST_Request $request ): void {
		$pos_header = $request->get_header( 'X-WC-POS' );

		if ( ! $pos_header ) {
			return;
		}

		if ( ! POSUtils::current_user_can_pos() ) {
			throw new RouteException(
				'woocommerce_rest_pos_unauthorized',
				__( 'POS requests require authentication with a user that has the manage_woocommerce capability.', 'woocommerce' ),
				401
			);
		}

		// Use POS session handler for authenticated POS requests.
		add_filter(
			'woocommerce_session_handler',
			function () {
				return POSSessionHandler::class;
			}
		);
	}

	/**
	 * Generates a cart token for the response headers.
	 *
	 * Current namespace is used as the token Issuer.
	 * *
	 *
	 * @return string
	 */
	protected function get_cart_token() {
		// Ensure cart is loaded.
		$this->cart_controller->load_cart();

		if ( ! wc()->session ) {
			return null;
		}

		return CartTokenUtils::get_cart_token( (string) wc()->session->get_customer_id() );
	}

	/**
	 * Checks if the request has a valid cart token.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function has_cart_token( \WP_REST_Request $request ) {
		if ( is_null( $this->has_cart_token ) ) {
			$this->has_cart_token = CartTokenUtils::validate_cart_token( $request->get_header( 'Cart-Token' ) ?? '' );
		}
		return $this->has_cart_token;
	}

	/**
	 * Checks if a nonce is required for the route.
	 *
	 * Nonce is required for update requests (POST, PUT, PATCH, DELETE) to prevent CSRF attacks,
	 * unless the request has a valid cart token or is an authenticated POS session.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return bool
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		// POS sessions are authenticated via Application Passwords, so they don't need CSRF protection.
		// Check both wc_is_pos_session() (if session is already initialized) and the request header
		// (in case session was initialized before our filter was added).
		if ( wc_is_pos_session() || $this->is_pos_request( $request ) ) {
			return false;
		}

		return $this->is_update_request( $request ) && ! $this->has_cart_token( $request );
	}

	/**
	 * Check if this is an authenticated POS request.
	 *
	 * This checks the X-WC-POS header and verifies the user has POS permissions.
	 * Used as a fallback when wc_is_pos_session() might return false due to
	 * session initialization timing.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function is_pos_request( \WP_REST_Request $request ): bool {
		return $request->get_header( 'X-WC-POS' ) && POSUtils::current_user_can_pos();
	}

	/**
	 * Triggered after an update to cart data. Re-calculates totals and updates draft orders (if they already exist) to
	 * keep all data in sync.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function cart_updated( \WP_REST_Request $request ) {
		$draft_order = $this->get_draft_order();

		if ( $draft_order ) {
			// This does not trigger a recalculation of the cart--endpoints should have already done so before returning
			// the cart response.
			$this->order_controller->update_order_from_cart( $draft_order, false );

			wc_do_deprecated_action(
				'woocommerce_blocks_cart_update_order_from_request',
				array(
					$draft_order,
					$request,
				),
				'7.2.0',
				'woocommerce_store_api_cart_update_order_from_request',
				'This action was deprecated in WooCommerce Blocks version 7.2.0. Please use woocommerce_store_api_cart_update_order_from_request instead.'
			);

			/**
			 * Fires when the order is synced with cart data from a cart route.
			 *
			 * @since 7.2.0
			 *
			 * @param \WC_Order $draft_order Order object.
			 * @param \WC_Customer $customer Customer object.
			 * @param \WP_REST_Request $request Full details about the request.
			 */
			do_action( 'woocommerce_store_api_cart_update_order_from_request', $draft_order, $request );
		}
	}

	/**
	 * For non-GET endpoints, require and validate a nonce to prevent CSRF attacks.
	 *
	 * Nonces will mismatch if the logged in session cookie is different! If using a client to test, set this cookie
	 * to match the logged in cookie in your browser.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_Error|boolean
	 */
	protected function check_nonce( \WP_REST_Request $request ) {
		$nonce = null;

		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
		}

		/**
		 * Filters the Store API nonce check.
		 *
		 * This can be used to disable the nonce check when testing API endpoints via a REST API client.
		 *
		 * @since 4.5.0
		 *
		 * @param boolean $disable_nonce_check If true, nonce checks will be disabled.
		 *
		 * @return boolean
		 */
		if ( apply_filters( 'woocommerce_store_api_disable_nonce_check', false ) ) {
			return true;
		}

		if ( null === $nonce ) {
			return $this->get_route_error_response( 'woocommerce_rest_missing_nonce', __( 'Missing the Nonce header. This endpoint requires a valid nonce.', 'woocommerce' ), 401 );
		}

		if ( ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_invalid_nonce', __( 'Nonce is invalid.', 'woocommerce' ), 403 );
		}

		return true;
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 500.
	 * @param array  $additional_data Extra data (key value pairs) to expose in the error response.
	 *
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = 500, $additional_data = [] ) {
		$additional_data['status'] = $http_status_code;

		// If there was a conflict, return the cart so the client can resolve it.
		if ( 409 === $http_status_code ) {
			$additional_data['cart'] = $this->cart_schema->get_item_response( $this->cart_controller->get_cart_for_response() );
		}

		return new \WP_Error( $error_code, $error_message, $additional_data );
	}
}
