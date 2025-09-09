<?php
/**
 * Abstract REST Controller.
 *
 * Extends WP_REST_Controller. Implements functionality that applies to all route controllers.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\V4;

use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Orders Controller.
 */
abstract class AbstractController extends \WP_REST_Controller {
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
	 * Hook prefix.
	 *
	 * @var string
	 */
	protected $hook_prefix = 'woocommerce_rest_api_v4_';

	/**
	 * Error prefix.
	 *
	 * @var string
	 */
	protected $error_prefix = 'woocommerce_rest_api_';

	/**
	 * Schema instance.
	 *
	 * @var AbstractSchema
	 */
	protected $schema;

	/**
	 * Get item schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( ! $this->schema ) {
			return array();
		}
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'type'       => 'object',
			'title'      => $this->schema::IDENTIFIER,
			'properties' => $this->schema->get_item_properties(),
		);
	}

	/**
	 * Get the hook prefix for actions and filters. e.g. woocommerce_rest_api_v4_orders_
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->hook_prefix . $this->rest_base . '_';
	}

	/**
	 * Get the error prefix for errors. e.g. woocommerce_rest_api_v4_orders_
	 *
	 * @return string
	 */
	protected function get_error_prefix() {
		return $this->error_prefix . $this->rest_base . '_';
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 400.
	 * @param array  $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = WP_Http::BAD_REQUEST, $additional_data = array() ) {
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
	 * @param array    $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return WP_Error WP Error object.
	 */
	protected function get_route_error_response_from_object( $error_object, $http_status_code = WP_Http::BAD_REQUEST, $additional_data = array() ) {
		$error_object->add_data( array_merge( $additional_data, array( 'status' => $http_status_code ) ) );
		return $error_object;
	}
}
