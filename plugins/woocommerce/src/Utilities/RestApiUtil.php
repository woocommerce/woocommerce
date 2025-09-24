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


	/**
	 * This is the internal function that implements the logic of wc_rest_lazy_load_namespace(). Its interface
	 * and behavior is not guaranteed.  It solely exists so that $callback_filter_id does not need to be part of the
	 * public interface to `wc_rest_lazy_load_namespace()`. Do not call it directly.
	 *
	 * @param string   $route_namespace    The namespace to check.
	 * @param callable $callback           The callback to execute if the namespace should be loaded.
	 * @param string   $rest_route         (Optional) The REST route to check against.
	 * @param string   $callback_filter_id (Internal) Used to prevent recursive filter registration.
	 *
	 * @return void
	 *
	 * @see      \wc_rest_lazy_load_namespace()
	 * @internal Do not call this function directly. Use `\wc_rest_lazy_load_namespace()`.  Backward compatibility is not guaranteed.
	 */
	public function lazy_load_namespace( string $route_namespace, callable $callback, string $rest_route = '', string $callback_filter_id = '' ) {
		if ( '' === $rest_route ) {
			$rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
		}

		if ( '' !== $rest_route ) {
			$rest_route      = trailingslashit( ltrim( $rest_route, '/' ) );
			$route_namespace = trailingslashit( $route_namespace );
			if ( '/' === $rest_route || str_starts_with( $rest_route, $route_namespace ) ) {
				// Load all namespaces for root requests (/wp-json/) to maintain API discovery functionality.
				if ( '' !== $callback_filter_id ) {
					// Remove the current filter prior to the callback, to prevent recursive callback issues.
					// This is crucial for APIs like wc-analytics that may callback to their own namespace when loading.
					remove_filter( 'rest_pre_dispatch', $callback_filter_id, 0 );
				}

				call_user_func( $callback );

				return;
			}
		}

		// Register a filter to check again on rest_pre_dispatch for dynamic loading.
		if ( '' === $callback_filter_id ) {
			$callback_filter    = function ( $filter_result, $server, $request ) use ( $route_namespace, $callback, &$callback_filter_id ) {
				if ( is_callable( array( $request, 'get_route' ) ) ) {
					$this->lazy_load_namespace( $route_namespace, $callback, $request->get_route(), $callback_filter_id );
				}

				return $filter_result;
			};
			$callback_filter_id = _wp_filter_build_unique_id( 'rest_pre_dispatch', $callback_filter, 0 );
			// This runs on priority 0 so that the namespace is loaded before `rest_handle_options_request()` is run (priority 10).
			add_filter( 'rest_pre_dispatch', $callback_filter, 0, 3 );
		}
	}
}
