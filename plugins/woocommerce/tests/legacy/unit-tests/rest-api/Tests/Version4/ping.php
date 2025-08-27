<?php
/**
 * Tests for the Ping REST API in Version 4.
 *
 * @package WooCommerce\Tests\API
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Ping REST API testing class.
 *
 * @package WooCommerce\Tests\API
 */
class WC_Tests_API_V4_Ping extends WC_REST_Unit_Test_Case {
	/**
	 * The ping controller endpoint.
	 *
	 * @var WC_REST_Ping_V4_Controller
	 */
	protected $endpoint;

	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	protected $user;

	/**
	 * Original rest_api_v4 feature option value.
	 *
	 * @var string
	 */
	private $original_rest_api_v4_option;

	/**
	 * Setup our test server, endpoints, and user info.
	 *
	 * @since 4.0.0
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable the rest_api_v4 feature for testing.
		update_option( 'woocommerce_feature_rest_api_v4_enabled', 'yes' );

		$this->endpoint = new WC_REST_Ping_V4_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Tear down the test environment.
	 *
	 * @since 4.0.0
	 */
	public function tearDown(): void {
		// Disable the rest_api_v4 feature after testing.
		update_option( 'woocommerce_feature_rest_api_v4_enabled', 'no' );

		unset( $this->endpoint, $this->user );
		parent::tearDown();
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
		// Test without authentication (should work since ping is public).
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/ping' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'pong', $data['message'] );
		$this->assertEquals( 'v4', $data['version'] );
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'message', $properties );
		$this->assertArrayHasKey( 'version', $properties );
	}
}
