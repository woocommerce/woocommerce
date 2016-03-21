<?php
namespace WooCommerce\Tests\API;

/**
 * Settings API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class Settings extends \WC_Unit_Test_Case {

	protected $server;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
		$this->endpoint = new \WC_Rest_Settings_Controller();
		\WC_Helper_Settings::register();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test route registration.
	 * @since 2.7.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v1/settings/locations', $routes );
	}

	/**
	 * Test getting all locations.
	 * @since 2.7.0
	 */
	public function test_get_locations() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $data ) );

		$this->check_get_location_response( $data[0], array(
			'id'          => 'test',
			'type'        => 'page',
			'label'       => 'Test Extension',
			'description' => 'My awesome test settings.',
		) );

		$this->check_get_location_response( $data[2], array(
			'id'          => 'coupon-data',
			'type'        => 'metabox',
			'label'       => 'Coupon Data',
			'description' => '',
		) );
	}

	/**
	 * Test /settings/locations without valid permissions/creds.
	 * @since 2.7.0
	 */
	public function test_get_locations_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test /settings/locations correctly filters out bad values.
	 * Handles required fields and bogus fields.
	 * @since 2.7.0
	 */
	public function test_get_locations_correctly_filters_values() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' ) );
		$data = $response->get_data();

		$this->assertEquals( 'test', $data[0]['id'] );
		$this->assertArrayNotHasKey( 'bad', $data[0] );
	}

	/**
	 * Test /settings/locations with type.
	 * @since 2.7.0
	 */
	public function test_get_locations_with_type() {
		wp_set_current_user( $this->user );

		$request = new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' );
		$request->set_param( 'type', 'not-a-real-type' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 3, count( $data ) ); // all results

		$request = new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' );
		$request->set_param( 'type', 'page' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) );
	}

	/**
	 * Test /settings/locations schema.
	 * @since 2.7.0
	 */
	public function test_get_location_schema() {
		$request = new \WP_REST_Request( 'OPTIONS', '/wc/v1/settings/locations' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 4, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
	}

	/**
	 * Test getting a single location item.
	 * @since 2.7.0
	 */
	public function test_get_location() {
		wp_set_current_user( $this->user );

		// test getting a location that does not exist
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/not-real' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting the 'invalid' location
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/invalid' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid location
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/coupon-data' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->check_get_location_response( $data, array(
			'id'          => 'coupon-data',
			'type'        => 'metabox',
			'label'       => 'Coupon Data',
			'description' => '',
		) );
	}

	/**
	 * Test getting a single location item.
	 * @since 2.7.0
	 */
	public function test_get_location_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/coupon-data' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test settings groups (for pages)
	 * @since 2.7.0
	 */
	public function test_settings_groups() {
		wp_set_current_user( $this->user );

		// test getting a non page location
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/coupon-data' ) );
		$data = $response->get_data();
		$this->assertArrayNotHasKey( 'groups', $data );

		// test getting a page with no groups
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/test-2' ) );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertEmpty( $data['groups'] );

		// test getting a page with groups
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/locations/test' ) );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertEquals( 2, count( $data['groups'] ) );
	}

	/**
	 * Ensure valid location data response.
	 * @since 2.7.0
	 * @param array $response
	 * @param array $expected
	 */
	protected function check_get_location_response( $response, $expected ) {
		$this->assertEquals( $expected['id'], $response['id'] );
		$this->assertEquals( $expected['type'], $response['type'] );
		$this->assertEquals( $expected['label'], $response['label'] );
		$this->assertEquals( $expected['description'], $response['description'] );
	}

}
