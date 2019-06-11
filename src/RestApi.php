<?php
/**
 * Initialize this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce;

defined( 'ABSPATH' ) || exit;

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require __DIR__ . '/../vendor/autoload.php';
} else {
	return;
}

use WooCommerce\Utilities\SingletonTrait;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class RestApi {
	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $endpoints = [];

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $namespace_class ) {
			$controllers = $namespace_class::get_controllers();

			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->endpoints[ $namespace ][ $controller_name ] = new $controller_class();
				$this->endpoints[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		return apply_filters(
			'woocommerce_rest_api_get_rest_namespaces',
			[
				'wc/v1' => 'WC_REST_Controllers_V1',
				'wc/v2' => 'WC_REST_Controllers_V2',
				'wc/v3' => 'WC_REST_Controllers_V3',
				'wc/v4' => '\WooCommerce\RestApi\Version4\Controllers',
			]
		);
	}

	/**
	 * Get data from a WooCommerce API endpoint.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $params Params to passwith request.
	 * @return array|WP_Error
	 */
	public function get_endpoint_data( $endpoint, $params = array() ) {
		$request = new \WP_REST_Request( 'GET', $endpoint );

		if ( $params ) {
			$request->set_query_params( $params );
		}

		$response = \rest_do_request( $request );
		$server   = \rest_get_server();
		$json     = wp_json_encode( $server->response_to_data( $response, false ) );

		return json_decode( $json, true );
	}
}
