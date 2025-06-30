<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\OrderStatusRestController;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Class OrderStatusRestControllerTest
 *
 * @package Automattic\WooCommerce\Tests\Internal\Orders
 */
class OrderStatusRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * @var OrderStatusRestController
	 */
	private $controller;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new OrderStatusRestController();
		$this->controller->register_routes();
	}

	/**
	 * Test getting order statuses endpoint.
	 */
	public function test_get_order_statuses() {
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/statuses' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Check response status.
		$this->assertEquals( 200, $response->get_status() );

		// Check response structure.
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		// Check first status item structure.
		$first_status = $data[0];
		$this->assertArrayHasKey( 'slug', $first_status );
		$this->assertArrayHasKey( 'name', $first_status );

		// Verify default WooCommerce statuses are present.
		$status_slugs   = array_column( $data, 'slug' );
		$expected_slugs = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ]; //phpcs:ignore

		foreach ( $expected_slugs as $slug ) {
			$this->assertContains( $slug, $status_slugs, "Status '$slug' should be present in response" );
		}
	}

	/**
	 * Test schema structure
	 */
	public function test_get_item_schema() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/statuses' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'schema', $data );
		$properties = $data['schema']['properties'];

		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'name', $properties );

		// Check slug property.
		$this->assertEquals( 'string', $properties['slug']['type'] );
		$this->assertTrue( $properties['slug']['readonly'] );

		// Check name property.
		$this->assertEquals( 'string', $properties['name']['type'] );
		$this->assertTrue( $properties['name']['readonly'] );
	}

	/**
	 * Test permission check
	 */
	public function test_get_items_permission() {
		// Test as guest user.
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/statuses' );
		$response = $this->server->dispatch( $request );

		// Should be accessible without authentication.
		$this->assertEquals( 200, $response->get_status() );
	}
}
