<?php

/**
 * Shipping Zones API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class WC_Tests_API_Shipping_Zones extends WC_Unit_Test_Case {

	protected $server;

	protected $endpoint;

	protected $user;

	protected $zones;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
		$this->endpoint = new WC_REST_Shipping_Zones_Controller();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		$this->zones = array();
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
		foreach( $this->zones as $zone ) {
			$zone->delete();
		}
	}

	/**
	 * Test route registration.
	 * @since 2.7.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v1/shipping/zones', $routes );
		$this->assertArrayHasKey( '/wc/v1/shipping/zones/(?P<id>[\d-]+)', $routes );
	}
}