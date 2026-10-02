<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

/**
 * Helpers to register test REST routes and dispatch them through the full REST lifecycle.
 *
 * For use in \WC_Unit_Test_Case subclasses, which provide create_rest_server_with_routes().
 */
trait StoreApiRouteTestTrait {

	/**
	 * Register a test REST route on the global server, splitting the path into a
	 * namespace and route so it can be dispatched by rest_do_request().
	 *
	 * Store API cart/checkout routes keep everything up to /cart or /checkout as
	 * the namespace; other routes use the leading segments as the namespace.
	 *
	 * @param string   $full_route Full route path, e.g. /wc/store/v1/cart.
	 * @param callable $callback   Route callback.
	 */
	private function register_test_route( string $full_route, callable $callback ): void {
		$path = ltrim( $full_route, '/' );

		if ( preg_match( '#^(.*?)/(cart|checkout)(?:/|$)#', $path, $m ) ) {
			$namespace = $m[1];
			$route     = '/' . substr( $path, strlen( $m[1] ) + 1 );
		} else {
			$offset    = (int) strrpos( $path, '/' );
			$namespace = substr( $path, 0, $offset );
			$route     = '/' . substr( $path, $offset + 1 );
		}

		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => $callback,
			)
		);
	}

	/**
	 * Create an isolated REST server with a single test route, then dispatch a
	 * GET request to it through rest_do_request() so the full dispatch lifecycle
	 * (rest_request_before_callbacks, callback, rest_request_after_callbacks) runs.
	 *
	 * @param string   $route    Full route path to register and dispatch.
	 * @param callable $callback Route callback.
	 */
	private function dispatch_test_route( string $route, callable $callback ): void {
		$this->create_rest_server_with_routes(
			array(
				function () use ( $route, $callback ): void {
					$this->register_test_route( $route, $callback );
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', $route ) );
	}
}
