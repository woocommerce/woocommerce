<?php

/**
 * class WC_REST_Orders_Controller_Tests.
 * Orders Controller tests for V3 REST API.
 */
class WC_REST_Orders_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * A customer user ID.
	 *
	 * @var int|null
	 */
	protected static $customer = null;

	/**
	 * An array of test orders.
	 *
	 * @var WC_Order[]
	 */
	protected static $orders = array();

	/**
	 * An array of test products.
	 *
	 * @var WC_Product_Simple[]
	 */
	protected static $products = array();

	/**
	 * Timestamp before test orders are created.
	 *
	 * @var int
	 */
	protected static $time_before_orders = 0;

	/**
	 * Timestamp after test orders are created.
	 *
	 * @var int
	 */
	protected static $time_after_orders = 0;

	/**
	 * Create orders for tests.
	 *
	 * @param WP_UnitTest_Factory $factory Factory class for creating WP objects.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$customer = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		self::$time_before_orders = time();

		$order1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( self::$customer );
		$order1->add_meta_data( 'test1', 'test1', true );
		$order1->add_meta_data( 'test2', 'test2', true );
		$order1->save();

		$order2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( self::$customer );
		$order2->add_meta_data( 'test1', 'test1', true );
		$order2->add_meta_data( 'test2', 'test2', true );
		$order2->save();

		self::$orders = array( $order1, $order2 );

		self::$time_after_orders = time() + HOUR_IN_SECONDS;

		self::$products[] = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
	}

	/**
	 * Clean up orders after tests.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		foreach ( self::$orders as $order ) {
			$order->delete( true );
		}

		foreach ( self::$products as $product ) {
			$product->delete( true );
		}

		wp_delete_user( self::$customer );
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Orders_Controller();
		wp_set_current_user( self::$customer );
	}

	/**
	 * Get all expected fields.
	 */
	public function get_expected_response_fields() {
		return array(
			'id',
			'parent_id',
			'number',
			'order_key',
			'created_via',
			'version',
			'status',
			'currency',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'discount_total',
			'discount_tax',
			'shipping_total',
			'shipping_tax',
			'cart_tax',
			'total',
			'total_tax',
			'prices_include_tax',
			'customer_id',
			'customer_ip_address',
			'customer_user_agent',
			'customer_note',
			'billing',
			'shipping',
			'payment_method',
			'payment_method_title',
			'transaction_id',
			'date_paid',
			'date_paid_gmt',
			'date_completed',
			'date_completed_gmt',
			'cart_hash',
			'meta_data',
			'line_items',
			'tax_lines',
			'shipping_lines',
			'fee_lines',
			'coupon_lines',
			'currency_symbol',
			'refunds',
			'payment_url',
			'is_editable',
			'needs_payment',
			'needs_processing',
		);
	}

	/**
	 * Test that all expected response fields are present.
	 * Note: This has fields hardcoded intentionally instead of fetching from schema to test for any bugs in schema result. Add new fields manually when added to schema.
	 */
	public function test_orders_api_get_all_fields() {
		$expected_response_fields = $this->get_expected_response_fields();

		$order    = reset( self::$orders );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );

		$this->assertEquals( 200, $response->get_status() );

		$response_fields = array_keys( $response->get_data() );

		$this->assertEmpty( array_diff( $expected_response_fields, $response_fields ), 'These fields were expected but not present in API response: ' . print_r( array_diff( $expected_response_fields, $response_fields ), true ) );

		$this->assertEmpty( array_diff( $response_fields, $expected_response_fields ), 'These fields were not expected in the API response: ' . print_r( array_diff( $response_fields, $expected_response_fields ), true ) );
	}

	/**
	 * Test that all fields are returned when requested one by one.
	 */
	public function test_orders_get_each_field_one_by_one() {
		$expected_response_fields = $this->get_expected_response_fields();
		$order                    = reset( self::$orders );

		foreach ( $expected_response_fields as $field ) {
			$request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() );
			$request->set_param( '_fields', $field );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$response_fields = array_keys( $response->get_data() );

			$this->assertContains( $field, $response_fields, "Field $field was expected but not present in order API response." );
		}
	}

	/**
	 * Tests getting all orders with the REST API.
	 *
	 * @return void
	 */
	public function test_orders_get_all(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/v3/orders' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
	}

	/**
	 * Tests filtering with the 'before' and 'after' params.
	 *
	 * @return void
	 */
	public function test_orders_date_filtering(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/v3/orders' );
		$request->set_param( 'dates_are_gmt', 1 );

		// No date params should return all orders.
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );

		// There are no orders before `$time_before_orders`.
		$request->set_param( 'before', gmdate( DateTime::ATOM, self::$time_before_orders ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data() );

		// All orders are before `$time_after_orders`.
		$request->set_param( 'before', gmdate( DateTime::ATOM, self::$time_after_orders ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
	}

	/**
	 * Tests creating an order.
	 */
	public function test_orders_create(): void {
		$product                  = reset( self::$products );
		$order_params             = array(
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
			'line_items'           => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 3,
				),
			),
		);
		$order_params['shipping'] = $order_params['billing'];

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params( $order_params );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'processing', $data['status'] );

		wp_cache_flush();

		// Fetch the order and compare some data.
		$order = wc_get_order( $data['id'] );
		$this->assertNotEmpty( $order );

		$this->assertEquals( (float) ( $product->get_price() * 3 ), (float) $order->get_total() );
		$this->assertEquals( $order_params['payment_method'], $order->get_payment_method( 'edit' ) );

		foreach ( array_keys( $order_params['billing'] ) as $address_key ) {
			$this->assertEquals( $order_params['billing'][ $address_key ], $order->{"get_billing_{$address_key}"}( 'edit' ) );
		}

		$order->delete( true ); // Clean up.
	}

	/**
	 * Tests deleting an order.
	 */
	public function test_orders_delete(): void {
		$order = new \WC_Order();
		$order->set_status( 'completed' );
		$order->save();

		$request  = new \WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Check that the response includes order data from the order (before deletion).
		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( $data['id'], $order->get_id() );
		$this->assertEquals( 'completed', $data['status'] );

		wp_cache_flush();

		// Check the order was actually deleted.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'trash', $order->get_status( 'edit' ) );

		$order->delete( true ); // Clean up.
	}

	/**
	 * Test that the `include_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_include_meta() {
		$expected_meta_key = 'test1';

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta = reset( $order['meta_data'] );
			$this->assertEquals( $expected_meta_key, $meta->get_data()['key'] );
		}
	}

	/**
	 * Test that the `include_meta` param is skipped when empty.
	 */
	public function test_collection_param_include_meta_empty() {
		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', '' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 2, count( $order['meta_data'] ) );
		}
	}

	/**
	 * Test that the `exclude_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_exclude_meta() {
		$expected_meta_key = 'test2';

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta = reset( $order['meta_data'] );
			$this->assertEquals( $expected_meta_key, $meta->get_data()['key'] );
		}
	}

	/**
	 * Test that the `exclude_meta` param is skipped when empty.
	 */
	public function test_collection_param_exclude_meta_empty() {
		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'exclude_meta', '' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 2, count( $order['meta_data'] ) );
		}
	}

	/**
	 * Test that the `include_meta` param overrides the `exclude_meta` param.
	 */
	public function test_collection_param_include_meta_override() {
		$expected_meta_key = 'test1';

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', 'test1' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta = reset( $order['meta_data'] );
			$this->assertEquals( $expected_meta_key, $meta->get_data()['key'] );
		}
	}
}
