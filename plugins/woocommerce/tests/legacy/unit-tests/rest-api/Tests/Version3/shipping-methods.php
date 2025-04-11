<?php

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Tests for the Shipping Methods REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class Shipping_Methods extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Shipping_Methods_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/shipping_methods', $routes );
		$this->assertArrayHasKey( '/wc/v3/shipping_methods/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test getting all shipping methods.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_methods() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping_methods' ) );
		$methods  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$free_shipping = null;
		foreach ( $methods as $method ) {
			if ( 'free_shipping' === $method['id'] ) {
				$free_shipping = $method;
				break;
			}
		}
		$this->assertNotEmpty( $free_shipping );

		$this->assertEmpty(
			ArrayUtil::deep_assoc_array_diff(
				array(
					'id'          => 'free_shipping',
					'title'       => 'Free shipping',
					'description' => 'Free shipping is a special method which can be triggered with coupons and minimum spends.',
					'_links'      => array(
						'self'       => array(
							array(
								'href' => rest_url( '/wc/v3/shipping_methods/free_shipping' ),
							),
						),
						'collection' => array(
							array(
								'href' => rest_url( '/wc/v3/shipping_methods' ),
							),
						),
					),
				),
				$free_shipping
			)
		);
	}

	/**
	 * Tests to make sure shipping methods cannot viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_methods_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping_methods' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a single shipping method.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_method() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping_methods/local_pickup' ) );
		$method   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'id'          => 'local_pickup',
				'title'       => 'Local pickup',
				'description' => 'Allow customers to pick up orders themselves. By default, when using local pickup store base taxes will apply regardless of customer address.',
			),
			$method
		);
	}

	/**
	 * Tests getting a single shipping method without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_method_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping_methods/local_pickup' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a shipping method with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_shipping_method_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/shipping_methods/fake_method' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test the shipping method schema.
	 *
	 * @since 3.5.0
	 */
	public function test_shipping_method_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/shipping_methods' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'description', $properties );
	}
}
