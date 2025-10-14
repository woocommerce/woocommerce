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
	 * Conditionally loads a REST API namespace based on the current route to improve performance.
	 *
	 * This function implements lazy loading for WooCommerce REST API namespaces to prevent loading
	 * all controllers on every request. It checks if the current REST route matches the namespace
	 * in order for that namespace to be loaded. If the namespace does not match the current rest
	 * route, a callback will be registered to possibly load the namespace again on `rest_pre_dispatch`;
	 * this is done to allow the namespace to be loaded on the fly during `rest_do_request()` calls.
	 *
	 * @param string   $route_namespace The namespace to check.
	 * @param callable $callback        The callback to execute if the namespace should be loaded.
	 *
	 * @return void
	 *
	 * @internal Do not call this function directly. Backward compatibility is not guaranteed.
	 */
	public function lazy_load_namespace( string $route_namespace, callable $callback ) {
		/**
		 * Filter whether to lazy load the namespace.  When set to false, the namespace will be loaded immediately during initialization.
		 *
		 * @param bool   $should_lazy_load_namespace Whether to lazy load the namespace instead of loading immediately.
		 * @param string $route_namespace            The namespace.
		 *
		 * @since 10.3.0
		 */
		$should_lazy_load_namespace = apply_filters( 'woocommerce_rest_should_lazy_load_namespace', true, $route_namespace );
		if ( $should_lazy_load_namespace ) {
			$this->attach_lazy_loaded_namespace( $route_namespace, $callback );
		} else {
			call_user_func( $callback );
		}
	}

	/**
	 * This is the internal function that implements the logic of self::lazy_load_namespace(). Its interface
	 * and behavior is not guaranteed.  It solely exists so that $callback_filter_id does not need to be part of the
	 * public interface to `self::lazy_load_namespace()`. Do not call it directly.
	 *
	 * @param string   $route_namespace    The namespace to check.
	 * @param callable $callback           The callback to execute if the namespace should be loaded.
	 * @param string   $rest_route         (Optional) The REST route to check against.
	 * @param string   $callback_filter_id (Internal) Used to prevent recursive filter registration.
	 *
	 * @return void
	 *
	 * @see      self::lazy_load_namespace()
	 * @internal Do not call this function directly. Backward compatibility is not guaranteed.
	 */
	public function attach_lazy_loaded_namespace( string $route_namespace, callable $callback, string $rest_route = '', string $callback_filter_id = '' ) {
		if ( '' === $rest_route && isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) ) {
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
					$this->attach_lazy_loaded_namespace( $route_namespace, $callback, $request->get_route(), $callback_filter_id );
				}

				return $filter_result;
			};
			$callback_filter_id = _wp_filter_build_unique_id( 'rest_pre_dispatch', $callback_filter, 0 );
			/**
			 * The `rest_handle_options_request()` function only works correctly if all REST API routes are registered. To ensure
			 * our routes are available in time, we must load the namespace before `rest_handle_options_request()` runs
			 * (which happens at priority 10).
			 */
			add_filter( 'rest_pre_dispatch', $callback_filter, 0, 3 );

		}
	}
}
