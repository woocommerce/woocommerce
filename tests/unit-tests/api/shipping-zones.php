<?php

/**
 * Shipping Zones API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class WC_Tests_API_Shipping_Zones extends WC_REST_Unit_Test_Case {

	protected $server;

	protected $endpoint;

	protected $user;

	protected $zones;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Shipping_Zones_Controller();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		$this->zones = array();
	}

	/**
	 * Delete zones.
	 */
	public function tearDown() {
		parent::tearDown();
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
	protected function create_shipping_zone( $name, $order = 0, $locations = array() ) {
		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( $name );
		$zone->set_zone_order( $order );
		$zone->set_locations( $locations );
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
		$this->assertArrayHasKey( '/wc/v1/shipping/zones/(?P<id>[\d-]+)/locations', $routes );
		$this->assertArrayHasKey( '/wc/v1/shipping/zones/(?P<zone_id>[\d-]+)/methods', $routes );
		$this->assertArrayHasKey( '/wc/v1/shipping/zones/(?P<zone_id>[\d-]+)/methods/(?P<instance_id>[\d-]+)', $routes );
	}

	/**
	 * Test getting all Shipping Zones.
	 * @since 2.7.0
	 */
	public function test_get_zones() {
		wp_set_current_user( $this->user );

		// "Rest of the World" zone exists by default
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertContains( array(
			'id'     => 0,
			'name'   => 'Rest of the World',
			'order'  => 0,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/0' ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones' ),
					),
				),
				'describedby' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/0/locations' ),
					),
				),
			),
		), $data );

		// Create a zone and make sure it's in the response
		$this->create_shipping_zone( 'Zone 1' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 2 );
		$this->assertContains( array(
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
				'describedby' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/1/locations' ),
					),
				),
			),
		), $data );
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
				'describedby' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $data['id'] . '/locations' ),
					),
				),
			),
		), $data );
	}

	/**
	 * Test Shipping Zone update endpoint.
	 * @since 2.7.0
	 */
	public function test_update_shipping_zone() {
		wp_set_current_user( $this->user );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/shipping/zones/' . $zone->get_id() );
		$request->set_body_params( array(
			'name'  => 'Zone Test',
			'order' => 2,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(
			'id'     => $zone->get_id(),
			'name'   => 'Zone Test',
			'order'  => 2,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones' ),
					),
				),
				'describedby' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ),
					),
				),
			),
		), $data );
	}

	/**
	 * Test Shipping Zone update endpoint with a bad zone ID.
	 * @since 2.7.0
	 */
	public function test_update_shipping_zone_invalid_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/shipping/zones/1' );
		$request->set_body_params( array(
			'name'  => 'Zone Test',
			'order' => 2,
		) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single Shipping Zone.
	 * @since 2.7.0
	 */
	public function test_get_single_shipping_zone() {
		wp_set_current_user( $this->user );

		$zone     = $this->create_shipping_zone( 'Test Zone' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/' . $zone->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(
			'id'     => $zone->get_id(),
			'name'   => 'Test Zone',
			'order'  => 0,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones' ),
					),
				),
				'describedby' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ),
					),
				),
			),
		), $data );
	}

	/**
	 * Test getting a single Shipping Zone with a bad zone ID.
	 * @since 2.7.0
	 */
	public function test_get_single_shipping_zone_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting Shipping Zone Locations.
	 * @since 2.7.0
	 */
	public function test_get_locations() {
		wp_set_current_user( $this->user );

		// Create a zone
		$zone = $this->create_shipping_zone( 'Zone 1', 0, array(
			array(
				'code' => 'US',
				'type' => 'country',
			),
		) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertEquals( array(
			array(
				'code'   => 'US',
				'type'   => 'country',
				'_links' => array(
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ),
						),
					),
					'describes' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
						),
					),
				),
			),
		), $data );
	}

	/**
	 * Test getting Shipping Zone Locations with a bad zone ID.
	 * @since 2.7.0
	 */
	public function test_get_locations_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/1/locations' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test Shipping Zone Locations update endpoint.
	 * @since 2.7.0
	 */
	public function test_update_locations() {
		wp_set_current_user( $this->user );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body( json_encode( array(
			array(
				'code' => 'UK',
				'type' => 'country',
			),
			array(
				'code' => 'US', // test that locations missing "type" aren't saved
			),
			array(
				'code' => 'SW1A0AA',
				'type' => 'postcode',
			),
			array(
				'type' => 'continent', // test that locations missing "code" aren't saved
			),
		) ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( count( $data ), 2 );
		$this->assertEquals( array(
			array(
				'code'   => 'UK',
				'type'   => 'country',
				'_links' => array(
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ),
						),
					),
					'describes' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
						),
					),
				),
			),
			array(
				'code'   => 'SW1A0AA',
				'type'   => 'postcode',
				'_links' => array(
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/locations' ),
						),
					),
					'describes' => array(
						array(
							'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
						),
					),
				),
			),
		), $data );
	}

	/**
	 * Test updating Shipping Zone Locations with a bad zone ID.
	 * @since 2.7.0
	 */
	public function test_update_locations_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'PUT', '/wc/v1/shipping/zones/1/locations' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting all Shipping Zone Methods and getting a single Shipping Zone Method.
	 * @since 2.7.0
	 */
	public function test_get_methods() {
		wp_set_current_user( $this->user );

		// Create a shipping method and make sure it's in the response
		$zone        = $this->create_shipping_zone( 'Zone 1' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$methods     = $zone->get_shipping_methods();
		$method      = $methods[ $instance_id ];

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/' . $zone->get_id() . '/methods' ) );
		$data     = $response->get_data();
		$expected = array(
			'instance_id'        => $instance_id,
			'title'              => $method->instance_settings['title'],
			'order'              => $method->method_order,
			'enabled'            => ( 'yes' === $method->enabled ),
			'method_id'          => $method->id,
			'method_title'       => $method->method_title,
			'method_description' => $method->method_description,
			'_links'             => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() . '/methods' ),
					),
				),
				'describes' => array(
					array(
						'href' => rest_url( '/wc/v1/shipping/zones/' . $zone->get_id() ),
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertContains( $expected, $data );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected, $data );
	}

	/**
	 * Test getting all Shipping Zone Methods with a bad zone ID.
	 * @since 2.7.0
	 */
	public function test_get_methods_invalid_zone_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/1/methods' ) );

		$this->assertEquals( 404, $response->get_status() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/1/methods/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single Shipping Zone Method with a bad ID.
	 * @since 2.7.0
	 */
	public function test_get_methods_invalid_method_id() {
		wp_set_current_user( $this->user );

		$zone     = $this->create_shipping_zone( 'Zone 1' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/shipping/zones/' . $zone->get_id() . '/methods/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}
}
