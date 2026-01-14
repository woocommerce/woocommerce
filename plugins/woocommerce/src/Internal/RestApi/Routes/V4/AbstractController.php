<?php
/**
 * Abstract REST Controller.
 *
 * Extends WP_REST_Controller. Implements functionality that applies to all route controllers.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4;

use WP_Error;
use WP_Http;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST Controller for WooCommerce REST API V4.
 *
 * Provides common functionality for all V4 route controllers including
 * schema generation, error handling, and hook management.
 *
 * @since 10.2.0
 */
abstract class AbstractController extends WP_REST_Controller {
	/**
	 * Shared error codes.
	 */
	const INVALID_ID          = 'invalid_id';
	const RESOURCE_EXISTS     = 'resource_exists';
	const CANNOT_CREATE       = 'cannot_create';
	const CANNOT_DELETE       = 'cannot_delete';
	const CANNOT_UPDATE       = 'cannot_update';
	const CANNOT_TRASH        = 'cannot_trash';
	const TRASH_NOT_SUPPORTED = 'trash_not_supported';

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Cache for the item schema populated after calling get_item_schema().
	 *
	 * @var array
	 */
	protected $schema;

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 *
	 * This should return the full schema object, not just the properties.
	 *
	 * @return array The full item schema.
	 */
	abstract protected function get_schema(): array;

	/**
	 * Get the collection args schema.
	 *
	 * @return array
	 */
	protected function get_query_schema(): array {
		return array();
	}

	/**
	 * List of args for endpoints. These may alter how data is returned or formatted. Extended by routes.
	 *
	 * @return array
	 */
	protected function get_endpoint_args(): array {
		return array();
	}

	/**
	 * Add default context collection params and filter the result. This does not inherit from
	 * WP_REST_Controller::get_collection_params because some endpoints do not paginate results.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params            = $this->get_query_schema();
		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );

		/**
		 * Filter the collection params.
		 *
		 * @param array $params The collection params.
		 * @since 10.2.0
		 */
		return apply_filters( $this->get_hook_prefix() . 'collection_params', $params, $this );
	}

	/**
	 * Get item schema, conforming to JSON Schema. Extended by routes.
	 *
	 * @return array The item schema.
	 * @since 10.2.0
	 */
	public function get_item_schema() {
		// Cache the schema for the route.
		if ( null === $this->schema ) {
			/**
			 * Filter the item schema for this route.
			 *
			 * @param array $schema The item schema.
			 * @since 10.2.0
			 */
			$this->schema = apply_filters( $this->get_hook_prefix() . 'item_schema', $this->add_additional_fields_schema( $this->get_schema() ) );
		}
		return $this->schema;
	}

	/**
	 * Get the item response.
	 *
	 * @param mixed           $item    WooCommerce representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return array The item response.
	 * @since 10.2.0
	 */
	abstract protected function get_item_response( $item, WP_REST_Request $request ): array;

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item WordPress representation of the item.
	 * @param WP_REST_Request  $request Request object.
	 * @param WP_REST_Response $response Response object.
	 * @return array
	 */
	protected function prepare_links( $item, WP_REST_Request $request, WP_REST_Response $response ): array {
		return array();
	}

	/**
	 * Prepares the item for the REST response. Controllers do not need to override this method as they can define a
	 * get_item_response method to prepare items. This method will take care of filter hooks.
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 10.2.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response_data = $this->get_item_response( $item, $request );
		$response_data = $this->add_additional_fields_to_object( $response_data, $request );
		$response_data = $this->filter_response_by_context( $response_data, $request['context'] ?? 'view' );

		$response = rest_ensure_response( $response_data );
		$response->add_links( $this->prepare_links( $item, $request, $response ) );

		/**
		 * Filter the data for a response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param mixed           $item    WordPress representation of the item.
		 * @param WP_REST_Request  $request  Request object.
		 * @since 10.2.0
		 */
		return rest_ensure_response( apply_filters( $this->get_hook_prefix() . 'item_response', $response, $item, $request ) );
	}

	/**
	 * Get the hook prefix for actions and filters.
	 *
	 * Example: woocommerce_rest_api_v4_orders_
	 *
	 * @return string The hook prefix.
	 * @since 10.2.0
	 */
	protected function get_hook_prefix(): string {
		return 'woocommerce_rest_api_v4_' . str_replace( '-', '_', $this->rest_base ) . '_';
	}

	/**
	 * Get the error prefix for errors.
	 *
	 * Example: woocommerce_rest_api_v4_orders_
	 *
	 * @return string The error prefix.
	 * @since 10.2.0
	 */
	protected function get_error_prefix(): string {
		return 'woocommerce_rest_api_v4_' . str_replace( '-', '_', $this->rest_base ) . '_';
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 400.
	 * @param array  $additional_data Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 * @since 10.2.0
	 */
	protected function get_route_error_response( string $error_code, string $error_message, int $http_status_code = WP_Http::BAD_REQUEST, array $additional_data = array() ): WP_Error {
		if ( empty( $error_code ) ) {
			$error_code = 'invalid_request';
		}

		if ( empty( $error_message ) ) {
			$error_message = __( 'An error occurred while processing your request.', 'woocommerce' );
		}

		return new WP_Error(
			$error_code,
			$error_message,
			array_merge(
				$additional_data,
				array( 'status' => $http_status_code )
			)
		);
	}

	/**
	 * Get route response when something went wrong and the supplied error is a WP_Error.
	 *
	 * @param WP_Error $error_object The WP_Error object containing the error.
	 * @param int      $http_status_code HTTP status. Defaults to 400.
	 * @param array    $additional_data Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 * @since 10.2.0
	 */
	protected function get_route_error_response_from_object( WP_Error $error_object, int $http_status_code = WP_Http::BAD_REQUEST, array $additional_data = array() ): WP_Error {
		if ( ! $error_object instanceof WP_Error ) {
			return $this->get_route_error_response( 'invalid_error_object', __( 'Invalid error object provided.', 'woocommerce' ), $http_status_code, $additional_data );
		}

		$error_object->add_data( array_merge( $additional_data, array( 'status' => $http_status_code ) ) );
		return $error_object;
	}

	/**
	 * Returns an authentication error for a given HTTP verb.
	 *
	 * @param string $method HTTP method.
	 * @return WP_Error|false WP Error object or false if no error is found.
	 */
	protected function get_authentication_error_by_method( string $method ) {
		$errors = array(
			'GET'    => array(
				'code'    => $this->get_error_prefix() . 'cannot_view',
				'message' => __( 'Sorry, you cannot view resources.', 'woocommerce' ),
			),
			'POST'   => array(
				'code'    => $this->get_error_prefix() . 'cannot_create',
				'message' => __( 'Sorry, you cannot create resources.', 'woocommerce' ),
			),
			'PUT'    => array(
				'code'    => $this->get_error_prefix() . 'cannot_update',
				'message' => __( 'Sorry, you cannot update resources.', 'woocommerce' ),
			),
			'PATCH'  => array(
				'code'    => $this->get_error_prefix() . 'cannot_update',
				'message' => __( 'Sorry, you cannot update resources.', 'woocommerce' ),
			),
			'DELETE' => array(
				'code'    => $this->get_error_prefix() . 'cannot_delete',
				'message' => __( 'Sorry, you cannot delete resources.', 'woocommerce' ),
			),
		);

		if ( ! isset( $errors[ $method ] ) ) {
			return false;
		}

		return new WP_Error(
			$errors[ $method ]['code'],
			$errors[ $method ]['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Get an error response for a given error code.
	 *
	 * @param string $error_code The error code.
	 * @return WP_Error WP Error object.
	 */
	protected function get_route_error_by_code( string $error_code ): WP_Error {
		$error_messages    = array(
			self::INVALID_ID          => __( 'Invalid ID.', 'woocommerce' ),
			self::RESOURCE_EXISTS     => __( 'Resource already exists.', 'woocommerce' ),
			self::CANNOT_CREATE       => __( 'Cannot create resource.', 'woocommerce' ),
			self::CANNOT_DELETE       => __( 'Cannot delete resource.', 'woocommerce' ),
			self::CANNOT_UPDATE       => __( 'Cannot update resource.', 'woocommerce' ),
			self::CANNOT_TRASH        => __( 'Cannot trash resource.', 'woocommerce' ),
			self::TRASH_NOT_SUPPORTED => __( 'Trash not supported.', 'woocommerce' ),
		);
		$http_status_codes = array(
			self::INVALID_ID          => WP_Http::NOT_FOUND,
			self::RESOURCE_EXISTS     => WP_Http::BAD_REQUEST,
			self::CANNOT_CREATE       => WP_Http::INTERNAL_SERVER_ERROR,
			self::CANNOT_DELETE       => WP_Http::INTERNAL_SERVER_ERROR,
			self::CANNOT_UPDATE       => WP_Http::INTERNAL_SERVER_ERROR,
			self::CANNOT_TRASH        => WP_Http::GONE,
			self::TRASH_NOT_SUPPORTED => WP_Http::NOT_IMPLEMENTED,
		);
		return $this->get_route_error_response(
			$this->get_error_prefix() . $error_code,
			$error_messages[ $error_code ] ?? __( 'An error occurred while processing your request.', 'woocommerce' ),
			$http_status_codes[ $error_code ] ?? WP_Http::BAD_REQUEST
		);
	}
}
