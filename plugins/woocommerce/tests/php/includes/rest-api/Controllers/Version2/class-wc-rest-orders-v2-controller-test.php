<?php

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * class WC_REST_Order_V2_Controller_Test.
 * Orders controller test.
 */
class WC_REST_Order_V2_Controller_Test extends WC_REST_Unit_Test_case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Orders_V2_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
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

		$order    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( $this->user );
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
		$order                    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( $this->user );

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
		$order = WC_Helper_Order::create_order();
		$order->save();
		$response = ( new WC_REST_Orders_V2_Controller() )->prepare_object_for_response( $order, new WP_REST_Request() );
		$this->assertArrayHasKey( 'id', $response->data );
		$this->assertEquals( $order->get_id(), $response->data['id'] );
	}

	/**
	 * Test that `order_item_display_meta` keeps a variation attribute whose value only appears in the
	 * parent product name.
	 */
	public function test_order_item_display_meta_keeps_attribute_matching_the_parent_name() {
		// Three attributes keep the attribute list out of the variation title, so it is just "Vienna Black"
		// and the "black" colour must not be treated as already shown.
		list( $product, $variation ) = WC_Helper_Product::create_variation_product_with_global_attributes(
			'Vienna Black',
			array(
				'pa_size'   => 'huge',
				'pa_number' => '1',
				'pa_colour' => 'black',
			),
			array(
				'size'   => array( 'small', 'huge' ),
				'number' => array( '0', '1' ),
				'colour' => array( 'black', 'white' ),
			)
		);

		try {
			$order = new WC_Order();
			$order->add_product( $variation, 1 );
			$order->save();

			$request = new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() );
			$request->set_param( 'order_item_display_meta', 'true' );
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status() );

			$response_data = $response->get_data();
			$line_item     = current( $response_data['line_items'] );

			$this->assertSame( 'Vienna Black', $line_item['name'] );
			$this->assertSame(
				array( 'pa_size', 'pa_number', 'pa_colour' ),
				wp_list_pluck( $line_item['meta_data'], 'key' ),
				'Every selected attribute is returned when the item name shows none of them.'
			);
		} finally {
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * Test that the `include_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_include_meta() {
		// Create 3 orders.
		for ( $i = 1; $i <= 3; $i ++ ) {
			$order = new \WC_Order();
			$order->add_meta_data( 'test1', 'test1', true );
			$order->add_meta_data( 'test2', 'test2', true );
			$order->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 3, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param is skipped when empty.
	 */
	public function test_collection_param_include_meta_empty() {
		// Create 3 orders.
		for ( $i = 1; $i <= 3; $i ++ ) {
			$order = new \WC_Order();
			$order->add_meta_data( 'test1', 'test1', true );
			$order->add_meta_data( 'test2', 'test2', true );
			$order->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', '' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 3, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
			$this->assertContains( 'test2', $meta_keys );
		}
	}

	/**
	 * Test that the `exclude_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_exclude_meta() {
		// Create 3 orders.
		for ( $i = 1; $i <= 3; $i ++ ) {
			$order = new \WC_Order();
			$order->add_meta_data( 'test1', 'test1', true );
			$order->add_meta_data( 'test2', 'test2', true );
			$order->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 3, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test2', $meta_keys );
			$this->assertNotContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param overrides the `exclude_meta` param.
	 */
	public function test_collection_param_include_meta_override() {
		// Create 3 orders.
		for ( $i = 1; $i <= 3; $i ++ ) {
			$order = new \WC_Order();
			$order->add_meta_data( 'test1', 'test1', true );
			$order->add_meta_data( 'test2', 'test2', true );
			$order->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders' );
		$request->set_param( 'include_meta', 'test1' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 3, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the meta_data property contains an array, and not an object, after being filtered.
	 */
	public function test_collection_param_include_meta_returns_array() {
		$order = new \WC_Order();
		$order->add_meta_data( 'test1', 'test1', true );
		$order->add_meta_data( 'test2', 'test2', true );
		$order->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/orders' );
		$request->set_param( 'include_meta', 'test2' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data       = $this->server->response_to_data( $response, false );
		$encoded_data_string = wp_json_encode( $response_data );
		$decoded_data_object = json_decode( $encoded_data_string, false ); // Ensure object instead of associative array.

		$this->assertIsArray( $decoded_data_object[0]->meta_data );
	}

	/**
	 * Describes the behavior of order creation (and updates) when the provided customer ID is valid
	 * as well as when it is invalid (ie, the customer does not belong to the current blog).
	 *
	 * @return void
	 */
	public function test_valid_and_invalid_customer_ids(): void {
		$customer_a = WC_Helper_Customer::create_customer( 'bob', 'staysafe', 'bob@rest-orders-controller.email' );
		$customer_b = WC_Helper_Customer::create_customer( 'bill', 'trustno1', 'bill@rest-orders-controller.email' );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array( 'customer_id' => $customer_a->get_id() ) );

		$response = $this->server->dispatch( $request );
		$order_id = $response->get_data()['id'];
		$this->assertEquals( 201, $response->get_status(), 'The order was created.' );
		$this->assertEquals( $customer_a->get_id(), $response->get_data()['customer_id'], 'The order is associated with the expected customer' );

		// Simulate a multisite network in which $customer_b is not a member of the blog.
		$legacy_proxy_mock = wc_get_container()->get( LegacyProxy::class );
		$legacy_proxy_mock->register_function_mocks(
			array(
				'is_multisite'           => function () {
					return true;
				},
				'is_user_member_of_blog' => function () {
					return false;
				},
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array( 'customer_id' => $customer_b->get_id() ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status(), 'The order was not created, as the specified customer does not belong to the blog.' );
		$this->assertEquals( 'woocommerce_rest_invalid_customer_id', $response->get_data()['code'], 'The returned error indicates the customer ID was invalid.' );

		// Repeat the last test, except by performing an order update (instead of order creation).
		$request = new WP_REST_Request( 'PUT', '/wc/v2/orders/' . $order_id );
		$request->set_body_params( array( 'customer_id' => $customer_b->get_id() ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status(), 'The order was not updated, as the specified customer does not belong to the blog.' );
		$this->assertEquals( 'woocommerce_rest_invalid_customer_id', $response->get_data()['code'], 'The returned error indicates the customer ID was invalid.' );
	}

	/**
	 * The /wc/v2/orders route registers its own schema args, so this confirms the reserved-meta-key
	 * guard is wired onto the live v2 endpoint, not just the shared prepare methods.
	 *
	 * @testdox PUT /wc/v2/orders/<id> rejects a serialized value under the reserved meta key of every line type.
	 * @dataProvider provide_reserved_meta_key_line_types
	 *
	 * @param string   $line_type    Request key: `line_items`, `fee_lines` or `shipping_lines`.
	 * @param callable $create_item  Builds the order item that $line_type maps to.
	 */
	public function test_v2_update_rejects_serialized_taxes_line_meta_key( string $line_type, callable $create_item ): void {
		$order = new WC_Order();
		$item  = $create_item();
		$order->add_item( $item );
		$order->save();

		$response = $this->dispatch_serialized_taxes_update( $order->get_id(), $line_type, $item->get_id() );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_order_item_meta_key', $data['data']['details'][ $line_type ]['code'] );
	}

	/**
	 * Every request key that maps to an order item type accepting meta data.
	 *
	 * @return array
	 */
	public function provide_reserved_meta_key_line_types(): array {
		return array(
			'line items'     => array(
				'line_items',
				function () {
					$item = new WC_Order_Item_Product();
					$item->set_product( WC_Helper_Product::create_simple_product() );
					$item->set_quantity( 1 );
					$item->set_total( '10.00' );
					return $item;
				},
			),
			'fee lines'      => array(
				'fee_lines',
				function () {
					$item = new WC_Order_Item_Fee();
					$item->set_name( 'Test fee' );
					$item->set_total( '5.00' );
					return $item;
				},
			),
			'shipping lines' => array(
				'shipping_lines',
				function () {
					$item = new WC_Order_Item_Shipping();
					$item->set_method_id( 'flat_rate' );
					$item->set_method_title( 'Flat rate' );
					$item->set_total( '10.00' );
					return $item;
				},
			),
		);
	}

	/**
	 * Dispatch a PUT that posts a serialized value under the reserved `_taxes` meta key of one order item.
	 *
	 * @param int    $order_id  Order to update.
	 * @param string $line_type Request key: `line_items`, `fee_lines` or `shipping_lines`.
	 * @param int    $item_id   Order item ID to target.
	 * @return WP_REST_Response
	 */
	private function dispatch_serialized_taxes_update( int $order_id, string $line_type, int $item_id ) {
		$request = new WP_REST_Request( 'PUT', '/wc/v2/orders/' . $order_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					$line_type => array(
						array(
							'id'        => $item_id,
							'meta_data' => array(
								array(
									'key'   => '_taxes',
									'value' => 'O:8:"stdClass":0:{}',
								),
							),
						),
					),
				)
			)
		);

		return $this->server->dispatch( $request );
	}

	/**
	 * Create an order that has counted the given coupons.
	 *
	 * @param string[] $codes Coupon codes to apply.
	 * @return WC_Order
	 */
	private function create_order_with_counted_coupons( array $codes ): WC_Order {
		$order = OrderHelper::create_order();
		foreach ( $codes as $code ) {
			if ( ! wc_get_coupon_id_by_code( $code ) ) {
				CouponHelper::create_coupon( $code );
			}
			$order->apply_coupon( $code );
		}
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Get the usage count of a coupon, freshly loaded.
	 *
	 * @param string $code Coupon code.
	 * @return int
	 */
	private function get_coupon_usage( string $code ): int {
		return ( new WC_Coupon( $code ) )->get_usage_count();
	}

	/**
	 * Dispatch a PUT to the order.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $params   Body params.
	 * @return WP_REST_Response
	 */
	private function put_order( int $order_id, array $params ): WP_REST_Response {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'PUT', '/wc/v2/orders/' . $order_id );
		$request->set_body_params( $params );

		return $this->server->dispatch( $request );
	}

	/**
	 * Get the ID of the coupon line holding the given code.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $code  Coupon code.
	 * @return int
	 */
	private function get_coupon_item_id( WC_Order $order, string $code ): int {
		foreach ( $order->get_items( 'coupon' ) as $item ) {
			if ( $item->get_code() === $code ) {
				return $item->get_id();
			}
		}
		return 0;
	}

	/**
	 * @testdox Adding a coupon to an order that counted its coupons counts the new coupon once.
	 */
	public function test_update_order_adding_coupon_counts_usage_on_recorded_order() {
		$order = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );
		CouponHelper::create_coupon( 'v2-usage-b' );

		$response = $this->put_order( $order->get_id(), array( 'coupon_lines' => array( array( 'code' => 'v2-usage-b' ) ) ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-b' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}

	/**
	 * @testdox Replacing a coupon on an order that counted its coupons moves the usage to the new coupon.
	 */
	public function test_update_order_replacing_coupon_moves_usage_on_recorded_order() {
		$order = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );
		CouponHelper::create_coupon( 'v2-usage-b' );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array(
					array(
						'id'   => $this->get_coupon_item_id( $order, 'v2-usage-a' ),
						'code' => null,
					),
					array( 'code' => 'v2-usage-b' ),
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $this->get_coupon_usage( 'v2-usage-a' ) );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-b' ) );
	}

	/**
	 * @testdox Removing the last coupon from an order that counted it lowers the usage and clears the record.
	 */
	public function test_update_order_removing_last_coupon_clears_usage_and_flag() {
		$order = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array(
					array(
						'id'   => $this->get_coupon_item_id( $order, 'v2-usage-a' ),
						'code' => null,
					),
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $this->get_coupon_usage( 'v2-usage-a' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertFalse( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}

	/**
	 * @testdox Sending the coupons an order already counts changes no usage.
	 */
	public function test_update_order_with_same_coupons_keeps_usage_counts() {
		$order = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array(
					array(
						'id'   => $this->get_coupon_item_id( $order, 'v2-usage-a' ),
						'code' => 'V2-USAGE-A',
					),
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}

	/**
	 * @testdox Adding a coupon and moving the order to processing in one request counts the coupon once.
	 */
	public function test_update_order_adding_coupon_and_setting_status_counts_once() {
		$order = OrderHelper::create_order();
		CouponHelper::create_coupon( 'v2-usage-a' );
		$this->assertFalse( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array( array( 'code' => 'v2-usage-a' ) ),
				'status'       => OrderStatus::PROCESSING,
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
	}

	/**
	 * @testdox Removing a coupon and cancelling the order in one request leaves the removed coupon uncounted.
	 */
	public function test_update_order_removing_coupon_and_cancelling_releases_usage() {
		$order = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array(
					array(
						'id'   => $this->get_coupon_item_id( $order, 'v2-usage-a' ),
						'code' => null,
					),
				),
				'status'       => OrderStatus::CANCELLED,
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $this->get_coupon_usage( 'v2-usage-a' ) );
	}

	/**
	 * @testdox Adding a coupon to a completed order that had none counts the coupon without a status change.
	 */
	public function test_update_order_adding_coupon_to_unrecorded_completed_order_counts_usage() {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		CouponHelper::create_coupon( 'v2-usage-a' );
		$this->assertFalse( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );

		$response = $this->put_order( $order->get_id(), array( 'coupon_lines' => array( array( 'code' => 'v2-usage-a' ) ) ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}

	/**
	 * @testdox Creating an order with coupon lines and a status counts the coupon once.
	 */
	public function test_create_order_with_coupon_lines_and_status_counts_once() {
		wp_set_current_user( $this->user );
		CouponHelper::create_coupon( 'v2-usage-a' );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params(
			array(
				'status'       => OrderStatus::PROCESSING,
				'coupon_lines' => array( array( 'code' => 'v2-usage-a' ) ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
	}

	/**
	 * @testdox Removing a coupon while changing the billing email releases the usage held under the old email.
	 */
	public function test_update_order_removing_coupon_and_changing_email_releases_old_identity() {
		$order = OrderHelper::create_order( 0 );
		CouponHelper::create_coupon( 'v2-usage-a' );
		$order->set_billing_email( 'old@example.com' );
		$order->apply_coupon( 'v2-usage-a' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$coupon_id = wc_get_coupon_id_by_code( 'v2-usage-a' );
		$this->assertContains( 'old@example.com', get_post_meta( $coupon_id, '_used_by', false ) );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'billing'      => array( 'email' => 'new@example.com' ),
				'coupon_lines' => array(
					array(
						'id'   => $this->get_coupon_item_id( wc_get_order( $order->get_id() ), 'v2-usage-a' ),
						'code' => null,
					),
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $this->get_coupon_usage( 'v2-usage-a' ) );
		$this->assertNotContains( 'old@example.com', get_post_meta( $coupon_id, '_used_by', false ) );
	}

	/**
	 * @testdox Changing the billing email while keeping a counted coupon moves its usage to the new email.
	 */
	public function test_update_order_changing_email_keeps_coupon_and_moves_usage_identity() {
		$order = OrderHelper::create_order( 0 );
		CouponHelper::create_coupon( 'v2-usage-a' );
		$order->set_billing_email( 'old@example.com' );
		$order->apply_coupon( 'v2-usage-a' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$coupon_id = wc_get_coupon_id_by_code( 'v2-usage-a' );
		$this->assertContains( 'old@example.com', get_post_meta( $coupon_id, '_used_by', false ) );
		$item_id = $this->get_coupon_item_id( wc_get_order( $order->get_id() ), 'v2-usage-a' );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'billing'      => array( 'email' => 'new@example.com' ),
				'coupon_lines' => array( array( 'id' => $item_id ) ),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
		$used_by = get_post_meta( $coupon_id, '_used_by', false );
		$this->assertContains( 'new@example.com', $used_by );
		$this->assertNotContains( 'old@example.com', $used_by );

		$response = $this->put_order(
			$order->get_id(),
			array(
				'coupon_lines' => array(
					array(
						'id'   => $item_id,
						'code' => null,
					),
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $this->get_coupon_usage( 'v2-usage-a' ) );
		$this->assertEmpty( get_post_meta( $coupon_id, '_used_by', false ) );
	}

	/**
	 * @testdox A failed batch item does not leak its coupon codes into the next item.
	 */
	public function test_orders_batch_failed_item_does_not_leak_coupon_codes() {
		wp_set_current_user( $this->user );
		$first  = $this->create_order_with_counted_coupons( array( 'v2-usage-a' ) );
		$second = $this->create_order_with_counted_coupons( array( 'v2-usage-b' ) );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'           => $first->get_id(),
						'coupon_lines' => array(
							array(
								'id'   => 99999999,
								'code' => 'v2-usage-x',
							),
						),
					),
					array(
						'id'            => $second->get_id(),
						'customer_note' => 'Batch note',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'error', $data['update'][0] );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-a' ) );
		$this->assertEquals( 1, $this->get_coupon_usage( 'v2-usage-b' ) );
		$second = wc_get_order( $second->get_id() );
		$this->assertTrue( $second->get_data_store()->get_recorded_coupon_usage_counts( $second ) );
	}
}
