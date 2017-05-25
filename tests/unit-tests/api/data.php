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
		$this->assertArrayHasKey( '/wc/v2/data/continents', $routes );
		$this->assertArrayHasKey( '/wc/v2/data/countries', $routes );
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
		$this->assertCount( 2, $index );
		$this->assertEquals( 'continents', $index[0]['slug'] );
		$this->assertEquals( 'countries', $index[1]['slug'] );
	}

	/**
	 * Test getting locations.
	 * @since 3.1.0
	 */
	public function test_get_locations() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/continents' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertGreaterThan( 1, count( $locations ) );
		$this->assertNotEmpty( $locations[0]['code'] );
		$this->assertNotEmpty( $locations[0]['name'] );
		$this->assertNotEmpty( $locations[0]['countries'] );
		$this->assertNotEmpty( $locations[0]['_links'] );
	}

	/**
	 * Test getting locations restricted to one continent.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_continent() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/continents/na' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertEquals( 'NA', $locations['code'] );
		$this->assertNotEmpty( $locations['name'] );
		$this->assertNotEmpty( $locations['countries'] );
		$this->assertNotEmpty( $locations['_links'] );
	}

	/**
	 * Test getting locations with no country specified
	 * @since 3.1.0
	 */
	public function test_get_locations_all_countries() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/countries' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertGreaterThan( 1, count( $locations ) );
		$this->assertNotEmpty( $locations[0]['code'] );
		$this->assertNotEmpty( $locations[0]['name'] );
		$this->assertArrayHasKey( 'states', $locations[0] );
		$this->assertNotEmpty( $locations[0]['_links'] );
	}

	/**
	 * Test getting locations restricted to one country.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_country() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/countries/us' ) );
		$locations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( is_array( $locations ) );
		$this->assertEquals( 'US', $locations['code'] );
		$this->assertNotEmpty( $locations['name'] );
		$this->assertCount( 54, $locations['states'] );
		$this->assertNotEmpty( $locations['_links'] );
	}

	/**
	 * Test getting locations from an invalid code.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_invalid_continent() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/continents/xx' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting locations from an invalid code.
	 * @since 3.1.0
	 */
	public function test_get_locations_from_invalid_country() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/countries/xx' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test getting locations without permissions.
	 * @since 3.1.0
	 */
	public function test_get_continents_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/continents' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting locations without permissions.
	 * @since 3.1.0
	 */
	public function test_get_countries_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/data/countries' ) );
		$this->assertEquals( 401, $response->get_status() );
	}
}
