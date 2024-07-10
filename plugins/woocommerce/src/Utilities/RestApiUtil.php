<?php

namespace Automattic\WooCommerce\Utilities;

/**
 * Utility methods related to the REST API.
 */
class RestApiUtil {

	/**
	 * Get data from a WooCommerce API endpoint.
	 * This method used to be part of the WooCommerce Legacy REST API.
	 *
	 * @since 9.0.0
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $params Params to pass with request.
	 * @return array|\WP_Error
	 */
	public function get_endpoint_data( $endpoint, $params = array() ) {
		$request = new \WP_REST_Request( 'GET', $endpoint );
		if ( $params ) {
			$request->set_query_params( $params );
		}
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$json     = wp_json_encode( $server->response_to_data( $response, false ) );
		return json_decode( $json, true );
	}
}
