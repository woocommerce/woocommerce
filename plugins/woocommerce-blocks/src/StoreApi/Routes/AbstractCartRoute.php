<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\OrderController;
/**
 * Abstract Cart Route
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
abstract class AbstractCartRoute extends AbstractRoute {
	use DraftOrderTrait;

	/**
	 * Schema class for this route's response.
	 *
	 * @var AbstractSchema|CartSchema
	 */
	protected $schema;

	/**
	 * Schema class for the cart.
	 *
	 * @var CartSchema
	 */
	protected $cart_schema;

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
	 * Constructor accepts two types of schema; one for the item being returned, and one for the cart as a whole. These
	 * may be the same depending on the route.
	 *
	 * @param CartSchema      $cart_schema Schema class for the cart.
	 * @param AbstractSchema  $item_schema Schema class for this route's items if it differs from the cart schema.
	 * @param CartController  $cart_controller Cart controller class.
	 * @param OrderController $order_controller Order controller class.
	 */
	public function __construct( CartSchema $cart_schema, AbstractSchema $item_schema = null, CartController $cart_controller, OrderController $order_controller ) {
		$this->schema           = is_null( $item_schema ) ? $cart_schema : $item_schema;
		$this->cart_schema      = $cart_schema;
		$this->cart_controller  = $cart_controller;
		$this->order_controller = $order_controller;
	}

	/**
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$this->cart_controller->load_cart();
		$this->calculate_totals();

		if ( $this->requires_nonce( $request ) ) {
			$nonce_check = $this->check_nonce( $request );

			if ( is_wp_error( $nonce_check ) ) {
				return $this->add_nonce_headers( $this->error_to_response( $nonce_check ) );
			}
		}

		try {
			$response = parent::get_response( $request );
		} catch ( RouteException $error ) {
			$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
		} catch ( \Exception $error ) {
			$response = $this->get_route_error_response( 'unknown_server_error', $error->getMessage(), 500 );
		}

		if ( is_wp_error( $response ) ) {
			$response = $this->error_to_response( $response );
		} elseif ( in_array( $request->get_method(), [ 'POST', 'PUT', 'PATCH', 'DELETE' ], true ) ) {
			$this->cart_updated( $request );
		}

		return $this->add_nonce_headers( $response );
	}

	/**
	 * Add nonce headers to a response object.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @return \WP_REST_Response
	 */
	protected function add_nonce_headers( \WP_REST_Response $response ) {
		$response->header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response->header( 'X-WC-Store-API-Nonce-Timestamp', time() );
		$response->header( 'X-WC-Store-API-User', get_current_user_id() );
		return $response;
	}

	/**
	 * Checks if a nonce is required for the route.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		return 'GET' !== $request->get_method();
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
			$this->order_controller->update_order_from_cart( $draft_order );

			/**
			 * Fires when the order is synced with cart data from a cart route.
			 *
			 * @param \WC_Order $draft_order Order object.
			 * @param \WC_Customer $customer Customer object.
			 * @param \WP_REST_Request $request Full details about the request.
			 */
			do_action( 'woocommerce_blocks_cart_update_order_from_request', $draft_order, $request );
		}
	}

	/**
	 * Ensures the cart totals are calculated before an API response is generated.
	 */
	protected function calculate_totals() {
		wc()->cart->get_cart();
		wc()->cart->calculate_fees();
		wc()->cart->calculate_shipping();
		wc()->cart->calculate_totals();
	}

	/**
	 * For non-GET endpoints, require and validate a nonce to prevent CSRF attacks.
	 *
	 * Nonces will mismatch if the logged in session cookie is different! If using a client to test, set this cookie
	 * to match the logged in cookie in your browser.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|boolean
	 */
	protected function check_nonce( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WC-Store-API-Nonce' );

		/**
		 * Filters the Store API nonce check.
		 *
		 * This can be used to disable the nonce check when testing API endpoints via a REST API client.
		 *
		 * @param boolean $disable_nonce_check If true, nonce checks will be disabled.
		 * @return boolean
		 */
		if ( apply_filters( 'woocommerce_store_api_disable_nonce_check', false ) ) {
			return true;
		}

		if ( null === $nonce ) {
			return $this->get_route_error_response( 'woocommerce_rest_missing_nonce', __( 'Missing the X-WC-Store-API-Nonce header. This endpoint requires a valid nonce.', 'woo-gutenberg-products-block' ), 401 );
		}

		if ( ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_invalid_nonce', __( 'X-WC-Store-API-Nonce is invalid.', 'woo-gutenberg-products-block' ), 403 );
		}

		return true;
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 500.
	 * @param array  $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = 500, $additional_data = [] ) {
		switch ( $http_status_code ) {
			case 409:
				// If there was a conflict, return the cart so the client can resolve it.
				$cart = $this->cart_controller->get_cart_instance();

				return new \WP_Error(
					$error_code,
					$error_message,
					array_merge(
						$additional_data,
						[
							'status' => $http_status_code,
							'cart'   => $this->cart_schema->get_item_response( $cart ),
						]
					)
				);
		}
		return new \WP_Error( $error_code, $error_message, [ 'status' => $http_status_code ] );
	}
}
