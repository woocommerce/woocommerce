<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractSchema;

/**
 * AbstractRoute class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
abstract class AbstractRoute implements RouteInterface {
	/**
	 * Schema class instance.
	 *
	 * @var AbstractSchema
	 */
	protected $schema;

	/**
	 * Constructor.
	 *
	 * @param AbstractSchema $schema Schema class for this route.
	 */
	public function __construct( AbstractSchema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Get the namespace for this route.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return 'wc/store';
	}

	/**
	 * Get item schema properties.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$response = null;
		try {
			if ( 'GET' !== $request->get_method() ) {
				$this->check_nonce( $request );
			}
			switch ( $request->get_method() ) {
				case 'POST':
					$response = $this->get_route_post_response( $request );
					break;
				case 'PUT':
				case 'PATCH':
					$response = $this->get_route_update_response( $request );
					break;
				case 'DELETE':
					$response = $this->get_route_delete_response( $request );
					break;
				default:
					$response = $this->get_route_response( $request );
					break;
			}
			if ( 'GET' !== $request->get_method() && ! is_wp_error( $response ) ) {
				$response->header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
			}
		} catch ( RouteException $error ) {
			$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
		} catch ( \Exception $error ) {
			$response = $this->get_route_error_response( 'unknown_server_error', $error->getMessage(), 500 );
		}
		return $response;
	}

	/**
	 * For non-GET endpoints, require and validate a nonce to prevent CSRF attacks.
	 *
	 * Nonces will mismatch if the logged in session cookie is different! If using a client to test, set this cookie
	 * to match the logged in cookie in your browser.
	 *
	 * @throws RouteException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function check_nonce( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WC-Store-API-Nonce' );

		if ( apply_filters( 'woocommerce_store_api_disable_nonce_check', false ) ) {
			return;
		}

		if ( null === $nonce ) {
			throw new RouteException( 'woocommerce_rest_missing_nonce', __( 'Missing the X-WC-Store-API-Nonce header. This endpoint requires a valid nonce.', 'woocommerce' ), 401 );
		}

		$valid_nonce = wp_verify_nonce( $nonce, 'wc_store_api' );

		if ( ! $valid_nonce ) {
			throw new RouteException( 'woocommerce_rest_invalid_nonce', __( 'X-WC-Store-API-Nonce is invalid.', 'woocommerce' ), 403 );
		}
	}

	/**
	 * Get route response for GET requests.
	 *
	 * When implemented, should return a \WP_REST_Response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		throw new RouteException( 'woocommerce_rest_invalid_endpoint', __( 'Method not implemented', 'woocommerce' ), 404 );
	}

	/**
	 * Get route response for POST requests.
	 *
	 * When implemented, should return a \WP_REST_Response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		throw new RouteException( 'woocommerce_rest_invalid_endpoint', __( 'Method not implemented', 'woocommerce' ), 404 );
	}

	/**
	 * Get route response for PUT requests.
	 *
	 * When implemented, should return a \WP_REST_Response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function get_route_update_response( \WP_REST_Request $request ) {
		throw new RouteException( 'woocommerce_rest_invalid_endpoint', __( 'Method not implemented', 'woocommerce' ), 404 );
	}

	/**
	 * Get route response for DELETE requests.
	 *
	 * When implemented, should return a \WP_REST_Response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function get_route_delete_response( \WP_REST_Request $request ) {
		throw new RouteException( 'woocommerce_rest_invalid_endpoint', __( 'Method not implemented', 'woocommerce' ), 404 );
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
		return new \WP_Error( $error_code, $error_message, array_merge( $additional_data, [ 'status' => $http_status_code ] ) );
	}

	/**
	 * Prepare a single item for response.
	 *
	 * @param mixed            $item Item to format to schema.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, \WP_REST_Request $request ) {
		$response = rest_ensure_response( $this->schema->get_item_response( $item ) );
		$response->add_links( $this->prepare_links( $item, $request ) );

		return $response;
	}

	/**
	 * Retrieves the context param.
	 *
	 * Ensures consistent descriptions between endpoints, and populates enum from schema.
	 *
	 * @param array $args Optional. Additional arguments for context parameter. Default empty array.
	 * @return array Context parameter details.
	 */
	protected function get_context_param( $args = array() ) {
		$param_details = array(
			'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$schema = $this->get_item_schema();

		if ( empty( $schema['properties'] ) ) {
			return array_merge( $param_details, $args );
		}

		$contexts = array();

		foreach ( $schema['properties'] as $attributes ) {
			if ( ! empty( $attributes['context'] ) ) {
				$contexts = array_merge( $contexts, $attributes['context'] );
			}
		}

		if ( ! empty( $contexts ) ) {
			$param_details['enum'] = array_unique( $contexts );
			rsort( $param_details['enum'] );
		}

		return array_merge( $param_details, $args );
	}

	/**
	 * Prepares a response for insertion into a collection.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @return array|mixed Response data, ready for insertion into collection data.
	 */
	protected function prepare_response_for_collection( \WP_REST_Response $response ) {
		$data   = (array) $response->get_data();
		$server = rest_get_server();
		$links  = $server::get_compact_response_links( $response );

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Item to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		return [];
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param(),
		);
	}

	/**
	 * Makes the cart and sessions available to a route by loading them from core.
	 */
	protected function maybe_load_cart() {
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
			wc_load_cart();
		}
	}
}
