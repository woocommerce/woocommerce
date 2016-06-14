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
	 * Helper method to create a Shipping Zone.
	 *
	 * @param string $name Zone name.
	 * @param int    $order Optional. Zone sort order.
	 * @return WC_Shipping_Zone
	 */
	protected function create_shipping_zone( $name, $order = 0 ) {
		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->save();

		$this->zones[] = $zone;

		return $zone;
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

	/**
	 * Test getting all Shipping Zones.
	 * @since 2.7.0
	 */
	public function test_get_zones() {
		wp_set_current_user( $this->user );

		// No zones by default
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 0 );

		// Create a zone and make sure it's in the response
		$this->create_shipping_zone( 'Zone 1' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertEquals( array(
			'id'     => 1,
			'name'   => 'Zone 1',
			'order'  => 0,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/1' ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones' ),
					),
				),
			),
		), $data[0] );
	}

	/**
	 * Test /shipping/zones without valid permissions/creds.
	 * @since 2.7.0
	 */
	public function test_get_shipping_zones_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test /shipping/zones while Shipping is disabled in WooCommerce.
	 * @since 2.7.0
	 */
	public function test_get_shipping_zones_disabled_shipping() {
		wp_set_current_user( $this->user );

		add_filter( 'wc_shipping_enabled', '__return_false' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$this->assertEquals( 404, $response->get_status() );

		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * Test Shipping Zone schema.
	 * @since 2.7.0
	 */
	public function test_get_shipping_zone_schema() {
		$request = new WP_REST_Request( 'OPTIONS', '/wc/v1/shipping/zones' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertTrue( $properties['id']['readonly'] );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertTrue( $properties['name']['required'] );
		$this->assertArrayHasKey( 'order', $properties );
		$this->assertFalse( $properties['order']['required'] );
	}

	/**
	 * Test Shipping Zone create endpoint.
	 * @since 2.7.0
	 */
	public function test_create_shipping_zone() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v1/shipping/zones' );
		$request->set_body_params( array(
			'name'  => 'Test Zone',
			'order' => 1,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(
			'id'     => $data['id'],
			'name'   => 'Test Zone',
			'order'  => 1,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $data['id'] ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones' ),
					),
				),
			),
		), $data );
	}
}