<?php
/**
 * Tests for the lazy REST server used by WC_REST_Unit_Test_Case.
 *
 * @package WooCommerce\Tests\Framework
 */

declare( strict_types = 1 );

/**
 * WC_Lazy_REST_Server_Test class.
 */
class WC_Lazy_REST_Server_Test extends WC_REST_Unit_Test_Case {

	/**
	 * @testdox get_routes() returns the complete route table after a scoped namespace dispatch.
	 */
	public function test_get_routes_returns_full_route_table_after_scoped_dispatch() {
		$this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products' ) );

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v2/products', $routes );
		$this->assertArrayHasKey( '/wc/v3/products', $routes );
		$this->assertArrayHasKey( '/wc/v1/products', $routes );
	}

	/**
	 * @testdox get_routes() leaves a server holding only manually registered routes untouched.
	 */
	public function test_get_routes_preserves_manual_route_registration() {
		self::do_isolated_rest_api_init(
			array(
				static function () {
					register_rest_route(
						'wc-lazy-test/v1',
						'/ping',
						array(
							'methods'             => 'GET',
							'callback'            => '__return_empty_array',
							'permission_callback' => '__return_true',
						)
					);
				},
			)
		);

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc-lazy-test/v1/ping', $routes );
		$this->assertArrayNotHasKey( '/wc/v3/products', $routes );
	}
}
