<?php
/**
 * Tests for Data API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.1.0
 */

class Data_API extends WC_REST_Unit_Test_Case {
	/**
	 * Setup our test server and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.1.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/data', $routes );
		$this->assertArrayHasKey( '/wc/v2/data/locations', $routes );
	}

	/**
	 * Test getting the data index.
	 * @since 3.1.0
	 */
	public function test_get_index() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data' ) );
		$index = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $index );
		$this->assertEquals( 'locations', $index[0]['slug'] );
	}

	/**
	 * Test getting locations.
	 * @since 3.1.0
	 */
	public function test_get_locations() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/locations' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertGreaterThan( 1, count( $locations ) );
		$this->assertNotEmpty( $locations[0]['code'] );
		$this->assertNotEmpty( $locations[0]['name'] );
		$this->assertNotEmpty( $locations[0]['locations'] );
	}

	/**
	 * Test getting locations restricted to one continent.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_continent() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/locations/na' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertEquals( 'NA', $locations['code'] );
		$this->assertNotEmpty( $locations['name'] );
		$this->assertNotEmpty( $locations['locations'] );
	}

	/**
	 * Test getting locations restricted to one country.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_country() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/locations/us' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertEquals( 'US', $locations['code'] );
		$this->assertNotEmpty( $locations['name'] );
		$this->assertCount( 54, $locations['locations'] );
	}

	/**
	 * Test getting locations from an invalid code.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_invalid_continent() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/locations/xx' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting locations without permissions.
	 * @since 3.1.0
	 */
	public function test_get_locations_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/locations' ) );
		$this->assertEquals( 401, $response->get_status() );
	}
}
