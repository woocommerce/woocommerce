<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;

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

	/**
	 * Wrap a callback in a Store API cart route, so the cart pricing context treats it like a real cart or checkout route.
	 *
	 * @param callable $callback Route callback.
	 * @return callable Callback on a cart route object.
	 */
	private function as_cart_route( callable $callback ): callable {
		$route = new class( $callback ) extends AbstractCartRoute {
			/**
			 * Route callback.
			 *
			 * @var callable
			 */
			private $callback;

			/**
			 * Store the callback without the Store API dependencies a real route needs.
			 *
			 * @param callable $callback Route callback.
			 */
			public function __construct( callable $callback ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Skips the parent constructor on purpose.
				$this->callback = $callback;
			}

			/**
			 * Get the path of this route.
			 *
			 * @return string
			 */
			public function get_path() {
				return '/test-cart-route';
			}

			/**
			 * Get the route arguments.
			 *
			 * @return array
			 */
			public function get_args() {
				return array();
			}

			/**
			 * Run the wrapped callback.
			 *
			 * @param \WP_REST_Request $request Request object.
			 * @return mixed
			 */
			public function handle( \WP_REST_Request $request ) {
				return ( $this->callback )( $request );
			}
		};

		return array( $route, 'handle' );
	}
}
