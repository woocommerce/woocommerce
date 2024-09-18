<?php

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Shipping Zones API Tests
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Shipping_Zones extends WC_REST_Unit_Test_Case {

	protected $server;

	protected $endpoint;

	protected $user;

	protected $zones;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Shipping_Zones_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$this->zones    = array();
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
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/shipping/zones', $routes );
		$this->assertArrayHasKey( '/wc/v3/shipping/zones/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/shipping/zones/(?P<id>[\d]+)/locations', $routes );
		$this->assertArrayHasKey( '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/methods', $routes );
		$this->assertArrayHasKey( '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/methods/(?P<instance_id>[\d]+)', $routes );
	}

	/**
	 * Test getting all Shipping Zones.
	 *
	 * @since 3.5.0
	 */
	public function test_get_zones() {
		wp_set_current_user( $this->user );

		// "Rest of the World" zone exists by default
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'     => $data[0]['id'],
					'name'   => 'Locations not covered by your other zones',
					'order'  => 0,
					'_links' => array(
						'self'        => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data[0]['id'] ),
							),
						),
						'collection'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones' ),
							),
						),
						'describedby' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data[0]['id'] . '/locations' ),
							),
						),
					),
				),
				$data[0]
			)
		);

		// Create a zone and make sure it's in the response
		$this->create_shipping_zone( 'Zone 1' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 2 );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'     => $data[1]['id'],
					'name'   => 'Zone 1',
					'order'  => 0,
					'_links' => array(
						'self'        => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data[1]['id'] ),
							),
						),
						'collection'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones' ),
							),
						),
						'describedby' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data[1]['id'] . '/locations' ),
							),
						),
					),
				),
				$data[1]
			)
		);
	}

	/**
	 * Test /shipping/zones without valid permissions/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_zones_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test /shipping/zones while Shipping is disabled in WooCommerce.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_zones_disabled_shipping() {
		wp_set_current_user( $this->user );

		add_filter( 'wc_shipping_enabled', '__return_false' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones' ) );
		$this->assertEquals( 404, $response->get_status() );

		remove_filter( 'wc_shipping_enabled', '__return_false' );
	}

	/**
	 * Test Shipping Zone schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_zone_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/shipping/zones' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertTrue( $properties['id']['readonly'] );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'order', $properties );
	}

	/**
	 * Test Shipping Zone create endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_create_shipping_zone() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones' );
		$request->set_body_params(
			array(
				'name'  => 'Test Zone',
				'order' => 1,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'     => $data['id'],
					'name'   => 'Test Zone',
					'order'  => 1,
					'_links' => array(
						'self'        => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data['id'] ),
							),
						),
						'collection'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones' ),
							),
						),
						'describedby' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $data['id'] . '/locations' ),
							),
						),
					),
				),
				$data
			)
		);
	}

	/**
	 * Test Shipping Zone create endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_create_shipping_zone_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones' );
		$request->set_body_params(
			array(
				'name'  => 'Test Zone',
				'order' => 1,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test Shipping Zone update endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_update_shipping_zone() {
		wp_set_current_user( $this->user );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/shipping/zones/' . $zone->get_id() );
		$request->set_body_params(
			array(
				'name'  => 'Zone Test',
				'order' => 2,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'     => $zone->get_id(),
					'name'   => 'Zone Test',
					'order'  => 2,
					'_links' => array(
						'self'        => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
							),
						),
						'collection'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones' ),
							),
						),
						'describedby' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
					),
				),
				$data
			)
		);
	}

	/**
	 * Test Shipping Zone update endpoint with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_update_shipping_zone_invalid_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/shipping/zones/555555' );
		$request->set_body_params(
			array(
				'name'  => 'Zone Test',
				'order' => 2,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test Shipping Zone delete endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_shipping_zone() {
		wp_set_current_user( $this->user );
		$zone = $this->create_shipping_zone( 'Zone 1' );

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/shipping/zones/' . $zone->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test Shipping Zone delete endpoint without permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_shipping_zone_without_permission() {
		wp_set_current_user( 0 );
		$zone = $this->create_shipping_zone( 'Zone 1' );

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/shipping/zones/' . $zone->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test Shipping Zone delete endpoint with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_shipping_zone_invalid_id() {
		wp_set_current_user( $this->user );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/shipping/zones/555555' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single Shipping Zone.
	 *
	 * @since 3.5.0
	 */
	public function test_get_single_shipping_zone() {
		wp_set_current_user( $this->user );

		$zone     = $this->create_shipping_zone( 'Test Zone' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'     => $zone->get_id(),
					'name'   => 'Test Zone',
					'order'  => 0,
					'_links' => array(
						'self'        => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
							),
						),
						'collection'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones' ),
							),
						),
						'describedby' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
					),
				),
				$data
			)
		);
	}

	/**
	 * Test getting a single Shipping Zone with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_single_shipping_zone_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting Shipping Zone Locations.
	 *
	 * @since 3.5.0
	 */
	public function test_get_locations() {
		wp_set_current_user( $this->user );

		// Create a zone
		$zone = $this->create_shipping_zone(
			'Zone 1',
			0,
			array(
				array(
					'code' => 'US',
					'type' => 'country',
				),
			)
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertEquals(
			array(
				array(
					'code'   => 'US',
					'type'   => 'country',
					'_links' => array(
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
						'describes'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
							),
						),
					),
				),
			),
			$data
		);
	}

	/**
	 * Test getting Shipping Zone Locations with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_locations_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/1/locations' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test Shipping Zone Locations update endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_update_locations() {
		wp_set_current_user( $this->user );

		$zone = $this->create_shipping_zone( 'Test Zone' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body(
			json_encode(
				array(
					array(
						'code' => 'UK',
						'type' => 'country',
					),
					array(
						'code' => 'US', // test that locations missing "type" treated as country.
					),
					array(
						'code' => 'SW1A0AA',
						'type' => 'postcode',
					),
					array(
						'type' => 'continent', // test that locations missing "code" aren't saved
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
		$this->assertEquals(
			array(
				array(
					'code'   => 'UK',
					'type'   => 'country',
					'_links' => array(
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
						'describes'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
							),
						),
					),
				),
				array(
					'code'   => 'US',
					'type'   => 'country',
					'_links' => array(
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
						'describes'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
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
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/locations' ),
							),
						),
						'describes'  => array(
							array(
								'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
							),
						),
					),
				),
			),
			$data
		);
	}

	/**
	 * Test updating Shipping Zone Locations with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_update_locations_invalid_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'PUT', '/wc/v3/shipping/zones/1/locations' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting all Shipping Zone Methods and getting a single Shipping Zone Method.
	 *
	 * @since 3.5.0
	 */
	public function test_get_methods() {
		wp_set_current_user( $this->user );

		// Create a shipping method and make sure it's in the response
		$zone        = $this->create_shipping_zone( 'Zone 1' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$methods     = $zone->get_shipping_methods();
		$method      = $methods[ $instance_id ];

		$settings = array();
		$method->init_instance_settings();
		foreach ( $method->get_instance_form_fields() as $id => $field ) {
			$data = array(
				'id'          => $id,
				'label'       => $field['title'],
				'description' => ( empty( $field['description'] ) ? '' : $field['description'] ),
				'type'        => $field['type'],
				'value'       => $method->instance_settings[ $id ],
				'default'     => ( empty( $field['default'] ) ? '' : $field['default'] ),
				'tip'         => ( empty( $field['description'] ) ? '' : $field['description'] ),
				'placeholder' => ( empty( $field['placeholder'] ) ? '' : $field['placeholder'] ),
			);
			if ( ! empty( $field['options'] ) ) {
				$data['options'] = $field['options'];
			}
			$settings[ $id ] = $data;
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods' ) );
		$data     = $response->get_data();
		$expected = array(
			'id'                 => $instance_id,
			'instance_id'        => $instance_id,
			'title'              => $method->instance_settings['title'],
			'order'              => $method->method_order,
			'enabled'            => ( 'yes' === $method->enabled ),
			'method_id'          => $method->id,
			'method_title'       => $method->method_title,
			'method_description' => $method->method_description,
			'settings'           => $settings,
			'_links'             => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods' ),
					),
				),
				'describes'  => array(
					array(
						'href' => rest_url( '/wc/v3/shipping/zones/' . $zone->get_id() ),
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $data ), 1 );
		$this->assertEmpty( ArrayUtil::deep_assoc_array_diff( $expected, $data[0] ) );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( ArrayUtil::deep_assoc_array_diff( $expected, $data ) );
	}

	/**
	 * Test getting all Shipping Zone Methods with a bad zone ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_methods_invalid_zone_id() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/1/methods' ) );

		$this->assertEquals( 404, $response->get_status() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/1/methods/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting a single Shipping Zone Method with a bad ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_methods_invalid_method_id() {
		wp_set_current_user( $this->user );

		$zone     = $this->create_shipping_zone( 'Zone 1' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/1' ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating a Shipping Zone Method.
	 *
	 * @since 3.5.0
	 */
	public function test_update_methods() {
		wp_set_current_user( $this->user );

		$zone        = $this->create_shipping_zone( 'Zone 1' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$methods     = $zone->get_shipping_methods();
		$method      = $methods[ $instance_id ];

		// Test defaults
		$request  = new WP_REST_Request( 'GET', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'title', $data['settings'] );
		$this->assertEquals( 'Flat rate', $data['settings']['title']['value'] );
		$this->assertArrayHasKey( 'tax_status', $data['settings'] );
		$this->assertEquals( 'taxable', $data['settings']['tax_status']['value'] );
		$this->assertArrayHasKey( 'cost', $data['settings'] );
		$this->assertEquals( '0', $data['settings']['cost']['value'] );

		// Update a single value
		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$request->set_body_params(
			array(
				'settings' => array(
					'cost' => 5,
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'title', $data['settings'] );
		$this->assertEquals( 'Flat rate', $data['settings']['title']['value'] );
		$this->assertArrayHasKey( 'tax_status', $data['settings'] );
		$this->assertEquals( 'taxable', $data['settings']['tax_status']['value'] );
		$this->assertArrayHasKey( 'cost', $data['settings'] );
		$this->assertEquals( '5', $data['settings']['cost']['value'] );

		// Test multiple settings
		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$request->set_body_params(
			array(
				'settings' => array(
					'cost'       => 10,
					'tax_status' => 'none',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'title', $data['settings'] );
		$this->assertEquals( 'Flat rate', $data['settings']['title']['value'] );
		$this->assertArrayHasKey( 'tax_status', $data['settings'] );
		$this->assertEquals( 'none', $data['settings']['tax_status']['value'] );
		$this->assertArrayHasKey( 'cost', $data['settings'] );
		$this->assertEquals( '10', $data['settings']['cost']['value'] );

		// Test bogus
		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$request->set_body_params(
			array(
				'settings' => array(
					'cost'       => 10,
					'tax_status' => 'this_is_not_a_valid_option',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Test other parameters
		$this->assertTrue( $data['enabled'] );
		$this->assertEquals( 1, $data['order'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$request->set_body_params(
			array(
				'enabled' => false,
				'order'   => 2,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertFalse( $data['enabled'] );
		$this->assertEquals( 2, $data['order'] );
		$this->assertArrayHasKey( 'cost', $data['settings'] );
		$this->assertEquals( '10', $data['settings']['cost']['value'] );
	}

	/**
	 * Test creating a Shipping Zone Method.
	 *
	 * @since 3.5.0
	 */
	public function test_create_method() {
		wp_set_current_user( $this->user );
		$zone    = $this->create_shipping_zone( 'Zone 1' );
		$request = new WP_REST_Request( 'POST', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods' );
		$request->set_body_params(
			array(
				'method_id' => 'flat_rate',
				'enabled'   => false,
				'order'     => 2,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertFalse( $data['enabled'] );
		$this->assertEquals( 2, $data['order'] );
		$this->assertArrayHasKey( 'cost', $data['settings'] );
		$this->assertEquals( '0', $data['settings']['cost']['value'] );
	}

	/**
	 * Test deleting a Shipping Zone Method.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_method() {
		wp_set_current_user( $this->user );
		$zone        = $this->create_shipping_zone( 'Zone 1' );
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$methods     = $zone->get_shipping_methods();
		$method      = $methods[ $instance_id ];
		$request     = new WP_REST_Request( 'DELETE', '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods/' . $instance_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}
}
