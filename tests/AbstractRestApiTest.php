<?php
/**
 * REST API Endpoint Test base class.
 *
 * @package WooCommerce/RestApi/Tests
 */

namespace WooCommerce\RestApi\Tests;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Unit_Test_Case;

/**
 * Abstract Rest API Test Class
 *
 * @extends  WC_REST_Unit_Test_Case
 */
abstract class AbstractRestApiTest extends WC_REST_Unit_Test_Case {

	/**
	 * The endpoint schema.
	 *
	 * @var array
	 */
	protected $properties = [];

	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Setup test class.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$actual_routes   = $this->server->get_routes();
		$expected_routes = $this->routes;

		foreach ( $expected_routes as $expected_route ) {
			$this->assertArrayHasKey( $expected_route, $actual_routes );
		}
	}

	/**
	 * Validate that the returned API schema matches what is expected.
	 *
	 * @return void
	 */
	public function test_schema_properties() {
		$this->user_request();

		$request    = new \WP_REST_Request( 'OPTIONS', $this->routes[0] );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( count( $this->properties ), count( $properties ) );

		foreach ( $this->properties as $property ) {
			$this->assertArrayHasKey( $property, $properties );
		}
	}

	/**
	 * Set current user to an authenticated user.
	 */
	protected function user_request() {
		wp_set_current_user( $this->user );
	}

	/**
	 * Set current user to an unauthenticated user.
	 */
	protected function guest_request() {
		wp_set_current_user( 0 );
	}

	/**
	 * Perform a request and return the status and returned data.
	 *
	 * @param string  $endpoint Endpoint to hit.
	 * @param string  $type Type of request e.g GET or POST.
	 * @param array   $params Request body.
	 * @param boolean $authenticated Do request as user or guest.
	 * @return object
	 */
	protected function do_request( $endpoint, $type = 'GET', $params = array(), $authenticated = true ) {
		$authenticated ? $this->user_request() : $this->guest_request();

		$request = new \WP_REST_Request( $type, $endpoint );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		return (object) array(
			'status' => $response->get_status(),
			'data'   => $response->get_data(),
		);
	}
}
