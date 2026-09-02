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
	 * Filter callback currently enabling the rest-api-v4 feature, if any.
	 *
	 * @var callable|null
	 */
	private $enable_v4_feature_callback = null;

	/**
	 * Clean up the feature filter.
	 */
	public function tearDown(): void {
		if ( null !== $this->enable_v4_feature_callback ) {
			remove_filter( 'woocommerce_admin_features', $this->enable_v4_feature_callback );
			$this->enable_v4_feature_callback = null;
		}
		parent::tearDown();
	}

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

	/**
	 * @testdox Full initialization registers controllers through the production registry, feature gates, and DI wiring.
	 */
	public function test_full_initialization_wires_controllers_through_production_registry() {
		$this->enable_rest_api_v4_feature();

		$this->server->initialize_all_routes();
		$routes = $this->server->get_routes();

		// Controllers whose behavior suites run against scoped servers stay
		// covered here through their real production registration paths.
		$this->assertArrayHasKey( '/wc-admin/mobile-app/qr-login-token', $routes );
		$this->assertArrayHasKey( '/wc-admin/mobile-app/qr-login-exchange', $routes );
		$this->assertArrayHasKey( '/wc-admin/mobile-app/qr-login-status', $routes );
		$this->assertArrayHasKey( '/wc/v4/shipping-zones', $routes );
	}

	/**
	 * @testdox The wc/v4 namespace stays gated behind the rest-api-v4 feature.
	 */
	public function test_v4_namespace_stays_gated_without_feature() {
		$this->server->initialize_all_routes();
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/products', $routes );
		$this->assertArrayNotHasKey( '/wc/v4/shipping-zones', $routes );
	}

	/**
	 * Enable the REST API v4 feature for the current test.
	 */
	private function enable_rest_api_v4_feature(): void {
		$this->enable_v4_feature_callback = static function ( $features ) {
			$features[] = 'rest-api-v4';
			return $features;
		};
		add_filter( 'woocommerce_admin_features', $this->enable_v4_feature_callback );
	}
}
