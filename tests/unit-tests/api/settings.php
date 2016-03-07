<?php
namespace WooCommerce\Tests\API;

/**
 * Settings API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class Settings extends \WP_Test_REST_Controller_Testcase {

	public function setUp() {
		parent::setUp();
		$this->endpoint = new \WC_Rest_Settings_Controller();
		\WC_Helper_Settings::register();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
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
		$this->assertEquals( 2, count( $data ) );

		$this->check_get_location_response( $data[0], array(
			'id'          => 'test',
			'type'        => 'page',
			'label'       => 'Test Extension',
			'description' => 'My awesome test settings.',
		) );

		$this->check_get_location_response( $data[1], array(
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
		$this->assertEquals( 2, count( $data ) ); // all results

		$request = new \WP_REST_Request( 'GET', '/wc/v1/settings/locations' );
		$request->set_param( 'type', 'page' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $data ) );

		$this->check_get_location_response( $data[0], array(
			'id'          => 'test',
			'type'        => 'page',
			'label'       => 'Test Extension',
			'description' => 'My awesome test settings.',
		) );
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

	public function test_get_items() { }
	public function test_get_item() { }
	public function test_context_param() { }
	public function test_create_item() { }
	public function test_update_item() { }
	public function test_delete_item() { }
	public function test_prepare_item() { }
	public function test_get_item_schema() { }

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
