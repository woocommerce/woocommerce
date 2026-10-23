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
	 * @testdox Shipping method routes are read-only.
	 */
	public function test_read_only_route_contract(): void {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/shipping_methods' ) );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );
	}

	/**
	 * Core shipping methods and optional instance costs.
	 *
	 * @return array
	 */
	public function core_shipping_method_provider(): array {
		return array(
			'flat rate'     => array( 'flat_rate', 'Flat rate', '10' ),
			'free shipping' => array( 'free_shipping', 'Free shipping', null ),
		);
	}

	/**
	 * @testdox A core shipping method can be added to a zone with its settings, read back, and removed.
	 *
	 * @dataProvider core_shipping_method_provider
	 *
	 * @param string      $method_id    Shipping method ID.
	 * @param string      $method_title Shipping method title.
	 * @param string|null $cost         Optional instance cost.
	 */
	public function test_core_zone_method_create_lifecycle( $method_id, $method_title, $cost ): void {
		wp_set_current_user( $this->user );

		$zone = new WC_Shipping_Zone( null );
		$zone->set_zone_name( 'Zone for ' . $method_title );
		$zone->save();

		$body = array(
			'method_id' => $method_id,
			'enabled'   => true,
		);
		if ( null !== $cost ) {
			$body['settings'] = array( 'cost' => $cost );
		}

		$collection_path = '/wc/v3/shipping/zones/' . $zone->get_id() . '/methods';
		$request         = new WP_REST_Request( 'POST', $collection_path );
		$request->set_body_params( $body );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$instance_id = $data['instance_id'];
		$this->assertIsInt( $instance_id );
		$this->assertSame( $instance_id, $data['id'] );
		$this->assertSame( $method_id, $data['method_id'] );
		$this->assertSame( $method_title, $data['title'] );
		$this->assertTrue( $data['enabled'] );
		if ( null === $cost ) {
			$this->assertArrayNotHasKey( 'cost', $data['settings'] );
		} else {
			$this->assertSame( $cost, $data['settings']['cost']['value'] );
		}

		$item_path = $collection_path . '/' . $instance_id;
		$this->assertSame( rest_url( $item_path ), $data['_links']['self'][0]['href'] );
		$this->assertSame( rest_url( $collection_path ), $data['_links']['collection'][0]['href'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $item_path ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $method_id, $response->get_data()['method_id'] );
		$this->assertTrue( $response->get_data()['enabled'] );
		if ( null !== $cost ) {
			$this->assertSame( $cost, $response->get_data()['settings']['cost']['value'] );
		}

		$delete_request = new WP_REST_Request( 'DELETE', $item_path );
		$delete_request->set_param( 'force', true );
		$response = $this->server->dispatch( $delete_request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $instance_id, $response->get_data()['instance_id'] );
		$this->assertSame( 404, $this->server->dispatch( new WP_REST_Request( 'GET', $item_path ) )->get_status() );
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
