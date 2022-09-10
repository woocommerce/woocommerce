<?php

/**
 * class WC_REST_Order_V2_Controller_Test.
 * Orders controller test.
 */
class WC_REST_Order_V2_Controller_Test extends WC_REST_Unit_Test_case {
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

		$order1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( self::$customer );
		$order1->add_meta_data( 'test1', 'test1', true );
		$order1->add_meta_data( 'test2', 'test2', true );
		$order1->save();

		$order2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( self::$customer );
		$order2->add_meta_data( 'test1', 'test1', true );
		$order2->add_meta_data( 'test2', 'test2', true );
		$order2->save();

		self::$orders = array( $order1, $order2 );
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

		wp_delete_user( self::$customer );
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Orders_V2_Controller();
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
	public function test_orders_api_get_all_fields_v2() {
		$expected_response_fields = $this->get_expected_response_fields();

		$order    = reset( self::$orders );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() ) );

		$this->assertEquals( 200, $response->get_status() );

		$response_fields = array_keys( $response->get_data() );

		$this->assertEmpty( array_diff( $expected_response_fields, $response_fields ), 'These fields were expected but not present in API response: ' . print_r( array_diff( $expected_response_fields, $response_fields ), true ) );

		$this->assertEmpty( array_diff( $response_fields, $expected_response_fields ), 'These fields were not expected in the API V2 response: ' . print_r( array_diff( $response_fields, $expected_response_fields ), true ) );
	}

	/**
	 * Test that all fields are returned when requested one by one.
	 */
	public function test_orders_get_each_field_one_by_one_v2() {
		$expected_response_fields = $this->get_expected_response_fields();
		$order                    = reset( self::$orders );

		foreach ( $expected_response_fields as $field ) {
			$request = new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() );
			$request->set_param( '_fields', $field );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$response_fields = array_keys( $response->get_data() );

			$this->assertContains( $field, $response_fields, "Field $field was expected but not present in order API V2 response." );
		}
	}

	/**
	 * Test that `prepare_object_for_response` method works.
	 */
	public function test_prepare_object_for_response() {
		$order    = reset( self::$orders );
		$response = ( new WC_REST_Orders_V2_Controller() )->prepare_object_for_response( $order, new WP_REST_Request() );
		$this->assertArrayHasKey( 'id', $response->data );
		$this->assertEquals( $order->get_id(), $response->data['id'] );
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
