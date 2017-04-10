<?php
/**
 * Tests for the orders REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */
class WC_Tests_API_Orders extends WC_REST_Unit_Test_Case {

	/**
	 * Array of order to track
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Orders_Controller();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test route registration.
	 * @since 3.0.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/orders', $routes );
		$this->assertArrayHasKey( '/wc/v2/orders/batch', $routes );
		$this->assertArrayHasKey( '/wc/v2/orders/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Cleanup.
	 */
	public function stoppit_and_tidyup() {
		foreach ( $this->orders as $order ) {
			wp_delete_post( $order->get_id(), true );
		}
		$this->orders = array();
	}

	/**
	 * Test getting all orders.
	 * @since 3.0.0
	 */
	public function test_get_items() {
		wp_set_current_user( $this->user );

		// Create 10 orders.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->orders[] = WC_Helper_Order::create_order( $this->user );
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders' ) );
		$orders   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $orders ) );
		$this->stoppit_and_tidyup();
	}

	/**
	 * Tests to make sure orders cannot be viewed without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );
		$this->orders[] = WC_Helper_Order::create_order();
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders' ) );
		$this->assertEquals( 401, $response->get_status() );
		$this->stoppit_and_tidyup();
	}

	/**
	 * Tests getting a single order.
	 * @since 3.0.0
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user );
		$order          = WC_Helper_Order::create_order();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() ) );
		$data           = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $data['id'] );
		$this->stoppit_and_tidyup();
	}

	/**
	 * Tests getting a single order without the correct permissions.
	 * @since 3.0.0
	 */
	public function test_get_item_without_permission() {
		wp_set_current_user( 0 );
		$order          = WC_Helper_Order::create_order();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
		$this->stoppit_and_tidyup();
	}

	/**
	 * Tests getting an order with an invalid ID.
	 * @since 3.0.0
	 */
	public function test_get_item_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/99999999' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating an order.
	 * @since 3.0.0
	 */
	public function test_create_order() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array(
			'payment_method' => 'bacs',
			'payment_method_title' => 'Direct Bank Transfer',
			'set_paid' => true,
			'billing' => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
				'email'      => 'john.doe@example.com',
				'phone'      => '(555) 555-5555',
			),
			'shipping' => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
			),
			'line_items' => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 2,
				),
			),
			'shipping_lines' => array(
				array(
					'method_id'    => 'flat_rate',
					'method_title' => 'Flat rate',
					'total'        => 10,
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), $data['payment_method_title'] );
		$this->assertEquals( $order->get_billing_first_name(), $data['billing']['first_name'] );
		$this->assertEquals( $order->get_billing_last_name(), $data['billing']['last_name'] );
		$this->assertEquals( '', $data['billing']['company'] );
		$this->assertEquals( $order->get_billing_address_1(), $data['billing']['address_1'] );
		$this->assertEquals( $order->get_billing_address_2(), $data['billing']['address_2'] );
		$this->assertEquals( $order->get_billing_city(), $data['billing']['city'] );
		$this->assertEquals( $order->get_billing_state(), $data['billing']['state'] );
		$this->assertEquals( $order->get_billing_postcode(), $data['billing']['postcode'] );
		$this->assertEquals( $order->get_billing_country(), $data['billing']['country'] );
		$this->assertEquals( $order->get_billing_email(), $data['billing']['email'] );
		$this->assertEquals( $order->get_billing_phone(), $data['billing']['phone'] );
		$this->assertEquals( $order->get_shipping_first_name(), $data['shipping']['first_name'] );
		$this->assertEquals( $order->get_shipping_last_name(), $data['shipping']['last_name'] );
		$this->assertEquals( '', $data['shipping']['company'] );
		$this->assertEquals( $order->get_shipping_address_1(), $data['shipping']['address_1'] );
		$this->assertEquals( $order->get_shipping_address_2(), $data['shipping']['address_2'] );
		$this->assertEquals( $order->get_shipping_city(), $data['shipping']['city'] );
		$this->assertEquals( $order->get_shipping_state(), $data['shipping']['state'] );
		$this->assertEquals( $order->get_shipping_postcode(), $data['shipping']['postcode'] );
		$this->assertEquals( $order->get_shipping_country(), $data['shipping']['country'] );
		$this->assertEquals( 1, count( $data['line_items'] ) );
		$this->assertEquals( 1, count( $data['shipping_lines'] ) );

		wp_delete_post( $product->get_id(), true );
		wp_delete_post( $data['id'], true );
	}

	/**
	 * Tests creating an order without required fields.
	 * @since 3.0.0
	 */
	public function test_create_order_invalid_fields() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		// non-existant customer
		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array(
			'payment_method'       => 'bacs',
			'payment_method_title' => 'Direct Bank Transfer',
			'set_paid'             => true,
			'customer_id'          => 99999,
			'billing'              => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
				'email'      => 'john.doe@example.com',
				'phone'      => '(555) 555-5555',
			),
			'shipping' => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
			),
			'line_items' => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 2,
				),
			),
			'shipping_lines' => array(
				array(
					'method_id'    => 'flat_rate',
					'method_title' => 'Flat rate',
					'total'        => 10,
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );
		wp_delete_post( $product->get_id(), true );
	}

	/**
	 * Tests updating an order.
	 * @since 3.0.0
	 */
	public function test_update_order() {
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/' . $order->get_id() );
		$request->set_body_params( array(
			'payment_method' => 'test-update',
			'billing' => array(
				'first_name' => 'Fish',
				'last_name'  => 'Face',
			),
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'test-update', $data['payment_method'] );
		$this->assertEquals( 'Fish', $data['billing']['first_name'] );
		$this->assertEquals( 'Face', $data['billing']['last_name'] );

		wp_delete_post( $order->get_id(), true );
	}

	/**
	 * Tests updating an order without the correct permissions.
	 * @since 3.0.0
	 */
	public function test_update_order_without_permission() {
		wp_set_current_user( 0 );
		$order = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/' . $order->get_id() );
		$request->set_body_params( array(
			'payment_method' => 'test-update',
			'billing' => array(
				'first_name' => 'Fish',
				'last_name'  => 'Face',
			),
		) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests that updating an order with an invalid id fails.
	 * @since 3.0.0
	 */
	public function test_update_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/999999' );
		$request->set_body_params( array(
			'payment_method' => 'test-update',
			'billing' => array(
				'first_name' => 'Fish',
				'last_name'  => 'Face',
			),
		) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test deleting an order.
	 * @since 3.0.0
	 */
	public function test_delete_order() {
		wp_set_current_user( $this->user );
		$order    = WC_Helper_Order::create_order();
		$request  = new WP_REST_Request( 'DELETE', '/wc/v2/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, get_post( $order->get_id() ) );
	}

	/**
	 * Test deleting an order without permission/creds.
	 * @since 3.0.0
	 */
	public function test_delete_order_without_permission() {
		wp_set_current_user( 0 );
		$order    = WC_Helper_Order::create_order();
		$request  = new WP_REST_Request( 'DELETE', '/wc/v2/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
		wp_delete_post( $order->get_id(), true );
	}

	/**
	 * Test deleting an order with an invalid id.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v2/orders/9999999' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 */
	public function test_orders_batch() {
		wp_set_current_user( $this->user );

		$order1 = WC_Helper_Order::create_order();
		$order2 = WC_Helper_Order::create_order();
		$order3 = WC_Helper_Order::create_order();

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/batch' );
		$request->set_body_params( array(
			'update' => array(
				array(
					'id'             => $order1->get_id(),
					'payment_method' => 'updated',
				),
			),
			'delete' => array(
				$order2->get_id(),
				$order3->get_id(),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'updated', $data['update'][0]['payment_method'] );
		$this->assertEquals( $order2->get_id(), $data['delete'][0]['id'] );
		$this->assertEquals( $order3->get_id(), $data['delete'][1]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 1, count( $data ) );

		wp_delete_post( $order1->get_id(), true );
		wp_delete_post( $order2->get_id(), true );
		wp_delete_post( $order3->get_id(), true );
	}

	/**
	 * Test the order schema.
	 * @since 3.0.0
	 */
	public function test_order_schema() {
		wp_set_current_user( $this->user );
		$order      = WC_Helper_Order::create_order();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/orders/' . $order->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 42, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		wp_delete_post( $order->get_id(), true );
	}
}
