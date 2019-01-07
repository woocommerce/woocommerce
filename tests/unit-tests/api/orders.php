<?php
/**
 * Tests for the orders REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
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
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/orders', $routes );
		$this->assertArrayHasKey( '/wc/v3/orders/batch', $routes );
		$this->assertArrayHasKey( '/wc/v3/orders/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting all orders.
	 * @since 3.5.0
	 */
	public function test_get_items() {
		wp_set_current_user( $this->user );

		// Create 10 orders.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->orders[] = WC_Helper_Order::create_order( $this->user );
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders' ) );
		$orders   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $orders ) );
	}

	/**
	 * Tests to make sure orders cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );
		$this->orders[] = WC_Helper_Order::create_order();
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a single order.
	 * @since 3.5.0
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$order->add_meta_data( 'key', 'value' );
		$order->add_meta_data( 'key2', 'value2' );
		$order->save();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$data           = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $data['id'] );

		// Test meta data is set.
		$this->assertEquals( 'key', $data['meta_data'][0]->key );
		$this->assertEquals( 'value', $data['meta_data'][0]->value );
		$this->assertEquals( 'key2', $data['meta_data'][1]->key );
		$this->assertEquals( 'value2', $data['meta_data'][1]->value );
	}

	/**
	 * Tests getting a single order without the correct permissions.
	 * @since 3.5.0
	 */
	public function test_get_item_without_permission() {
		wp_set_current_user( 0 );
		$order          = WC_Helper_Order::create_order();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting an order with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_get_item_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/99999999' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests getting an order with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_get_item_refund_id() {
		wp_set_current_user( $this->user );
		$order    = WC_Helper_Order::create_order();
		$refund   = wc_create_refund( array(
			'order_id' => $order->get_id(),
		) );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $refund->get_id() ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating an order.
	 * @since 3.5.0
	 */
	public function test_create_order() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => 'Direct Bank Transfer',
				'set_paid'             => true,
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
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '10',
					),
				),
			)
		);
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
	}

	/**
	 * Test the sanitization of the payment_method_title field through the API.
	 *
	 * @since 3.5.2
	 */
	public function test_create_update_order_payment_method_title_sanitize() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		// Test when creating order.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => '<h1>Sanitize this <script>alert(1);</script></h1>',
				'set_paid'             => true,
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
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '10',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this' );

		// Test when updating order.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $data['id'] );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => '<h1>Sanitize this too <script>alert(1);</script></h1>'
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this too' );
	}

	/**
	 * Tests creating an order without required fields.
	 * @since 3.5.0
	 */
	public function test_create_order_invalid_fields() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		// non-existent customer
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
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
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => 10,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Tests updating an order.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order() {
		wp_set_current_user( $this->user );
		$order   = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'test-update', $data['payment_method'] );
		$this->assertEquals( 'Fish', $data['billing']['first_name'] );
		$this->assertEquals( 'Face', $data['billing']['last_name'] );
	}

	/**
	 * Tests updating an order and removing items.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_items() {
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'       => 'Some Fee',
				'tax_status' => 'taxable',
				'total'      => '100',
				'tax_class'  => '',
			)
		);
		$order->add_item( $fee );
		$order->save();

		$request  = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$fee_data = current( $order->get_items( 'fee' ) );

		$request->set_body_params(
			array(
				'fee_lines' => array(
					array(
						'id'   => $fee_data->get_id(),
						'name' => null,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( empty( $data['fee_lines'] ) );
	}

	/**
	 * Tests updating an order and adding a coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_add_coupons() {
		wp_set_current_user( $this->user );

		$order      = WC_Helper_Order::create_order();
		$order_item = current( $order->get_items() );
		$coupon     = WC_Helper_Coupon::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'line_items'   => array(
					array(
						'id'         => $order_item->get_id(),
						'product_id' => $order_item->get_product_id(),
						'subtotal'   => '35.00',
					),
				),
				'coupon_lines' => array(
					array(
						'code' => 'fake-coupon',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data['coupon_lines'] );
		$this->assertEquals( '45.00', $data['total'] );
	}

	/**
	 * Tests updating an order and removing a coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_coupons() {
		wp_set_current_user( $this->user );
		$order      = WC_Helper_Order::create_order();
		$order_item = current( $order->get_items() );
		$coupon     = WC_Helper_Coupon::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$order->apply_coupon( $coupon );
		$order->save();

		// Check that the coupon is applied.
		$this->assertEquals( '45.00', $order->get_total() );

		$request     = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$coupon_data = current( $order->get_items( 'coupon' ) );

		$request->set_body_params(
			array(
				'coupon_lines' => array(
					array(
						'id'   => $coupon_data->get_id(),
						'code' => null,
					),
				),
				'line_items'   => array(
					array(
						'id'         => $order_item->get_id(),
						'product_id' => $order_item->get_product_id(),
						'total'      => '40.00',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( empty( $data['coupon_lines'] ) );
		$this->assertEquals( '50.00', $data['total'] );
	}

	/**
	 * Tests updating an order with an invalid coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_invalid_coupon() {
		wp_set_current_user( $this->user );
		$order   = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );

		$request->set_body_params(
			array(
				'coupon_lines' => array(
					array(
						'code' => 'NON_EXISTING_COUPON',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_coupon', $data['code'] );
		$this->assertEquals( 'Coupon "non_existing_coupon" does not exist!', $data['message'] );
	}

	/**
	 * Tests updating an order without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_without_permission() {
		wp_set_current_user( 0 );
		$order   = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests that updating an order with an invalid id fails.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/999999' );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test deleting an order.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order() {
		wp_set_current_user( $this->user );
		$order   = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, get_post( $order->get_id() ) );
	}

	/**
	 * Test deleting an order without permission/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order_without_permission() {
		wp_set_current_user( 0 );
		$order   = WC_Helper_Order::create_order();
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting an order with an invalid id.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/9999999' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_orders_batch() {
		wp_set_current_user( $this->user );

		$order1 = WC_Helper_Order::create_order();
		$order2 = WC_Helper_Order::create_order();
		$order3 = WC_Helper_Order::create_order();

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/batch' );
		$request->set_body_params(
			array(
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
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'updated', $data['update'][0]['payment_method'] );
		$this->assertEquals( $order2->get_id(), $data['delete'][0]['id'] );
		$this->assertEquals( $order3->get_id(), $data['delete'][1]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
	}

	/**
	 * Test the order schema.
	 *
	 * @since 3.5.0
	 */
	public function test_order_schema() {
		wp_set_current_user( $this->user );
		$order      = WC_Helper_Order::create_order();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 42, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
	}
}
