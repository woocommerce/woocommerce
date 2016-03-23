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
		$this->assertArrayHasKey( '/wc/v1/settings', $routes );
		// @todo test others
	}

	/**
	 * Test getting all groups.
	 * @since 2.7.0
	 */
	public function test_get_groups() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $data ) );

		$this->check_get_group_response( $data[0], array(
			'id'          => 'test',
			'label'       => 'Test Extension',
			'parent_id'   => '',
			'description' => 'My awesome test settings.',
		) );

		$this->check_get_group_response( $data[2], array(
			'id'          => 'coupon-data',
			'label'       => 'Coupon Data',
			'parent_id'   => '',
			'description' => '',
		) );
	}

	/**
	 * Test /settings without valid permissions/creds.
	 * @since 2.7.0
	 */
	public function test_get_groups_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test /settings/ correctly filters out bad values.
	 * Handles required fields and bogus fields.
	 * @since 2.7.0
	 */
	public function test_get_groups_correctly_filters_values() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings' ) );
		$data = $response->get_data();

		$this->assertEquals( 'test', $data[0]['id'] );
		$this->assertArrayNotHasKey( 'bad', $data[0] );
	}

	/**
	 * Test /settings schema.
	 * @since 2.7.0
	 */
	public function test_get_group_schema() {
		$request = new \WP_REST_Request( 'OPTIONS', '/wc/v1/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 4, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
	}

	/**
	 * Test getting a single group.
	 * @since 2.7.0
	 */
	public function test_get_group() {
		wp_set_current_user( $this->user );

		// test getting a location that does not exist
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/not-real' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting the 'invalid' location
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/invalid' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid location
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/coupon-data' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->check_get_group_response( $data, array(
			'id'          => 'coupon-data',
			'label'       => 'Coupon Data',
			'parent_id'   => '',
			'description' => '',
		) );

		// @todo make sure settings are set correctly
	}

	/**
	 * Test getting a single group without permission.
	 * @since 2.7.0
	 */
	public function test_get_group_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/coupon-data' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Ensure valid location data response.
	 * @since 2.7.0
	 * @param array $response
	 * @param array $expected
	 */
	protected function check_get_group_response( $response, $expected ) {
		$this->assertEquals( $expected['id'], $response['id'] );
		$this->assertEquals( $expected['parent_id'], $response['parent_id'] );
		$this->assertEquals( $expected['label'], $response['label'] );
		$this->assertEquals( $expected['description'], $response['description'] );
	}

}
