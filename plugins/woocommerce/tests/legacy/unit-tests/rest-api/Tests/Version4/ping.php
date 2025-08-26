<?php
/**
 * Tests for the Ping REST API in Version 4.
 *
 * @package WooCommerce\Tests\API
 */

class WC_Tests_API_V4_Ping extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Ping_V4_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 * @since 4.0.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/ping', $routes );
	}

	/**
	 * Test getting ping response.
	 * @since 4.0.0
	 */
	public function test_get_ping() {
		// Test without authentication (should work since ping is public)
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/ping' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'pong', $data['message'] );
		$this->assertEquals( 'v4', $data['version'] );
		$this->assertArrayHasKey( 'timestamp', $data );
	}

	/**
	 * Test ping schema.
	 * @since 4.0.0
	 */
	public function test_ping_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v4/ping' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'message', $properties );
		$this->assertArrayHasKey( 'timestamp', $properties );
		$this->assertArrayHasKey( 'version', $properties );
	}

	/**
	 * Test that V4 is completely separate from V3.
	 * @since 4.0.0
	 */
	public function test_v4_independence() {
		// Verify that V4 controller is different from V3 controllers
		$v4_ping = new WC_REST_Ping_V4_Controller();
		$this->assertEquals( 'wc/v4', $v4_ping->get_namespace() );
		
		// Verify this is not inheriting from any versioned controller
		$reflection = new ReflectionClass( 'WC_REST_Ping_V4_Controller' );
		$parent = $reflection->getParentClass();
		
		// Should extend WC_REST_V4_Controller, not any versioned controller
		$this->assertEquals( 'WC_REST_V4_Controller', $parent->getName() );
		
		// Verify V4 base controller extends WordPress REST directly, not WC legacy
		$v4_base_reflection = new ReflectionClass( 'WC_REST_V4_Controller' );
		$v4_base_parent = $v4_base_reflection->getParentClass();
		$this->assertEquals( 'WP_REST_Controller', $v4_base_parent->getName() );
	}
}
