<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Controller as OrdersController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderItemSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderCouponSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderFeeSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderTaxSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderShippingSchema;

/**
 * Orders Controller tests for V4 REST API.
 *
 * @group order-query-tests
 */
class WC_REST_Orders_V4_Controller_Tests extends WC_REST_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Endpoint instance.
	 *
	 * @var OrdersController
	 */
	private $endpoint;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Order schema instance.
	 *
	 * @var OrderSchema
	 */
	private $order_schema;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_rest_api_v4_feature();
	}

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			},
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public static function disable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		$this->enable_rest_api_v4_feature();
		parent::setUp();

		// Create schema instances with dependency injection.
		$order_item_schema     = new OrderItemSchema();
		$order_coupon_schema   = new OrderCouponSchema();
		$order_fee_schema      = new OrderFeeSchema();
		$order_tax_schema      = new OrderTaxSchema();
		$order_shipping_schema = new OrderShippingSchema();

		$this->order_schema = new OrderSchema();
		$this->order_schema->init( $order_item_schema, $order_coupon_schema, $order_fee_schema, $order_tax_schema, $order_shipping_schema );

		// Create utils instances.
		$collection_query  = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\CollectionQuery();
		$update_utils      = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\UpdateUtils();
		$action_controller = new \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\ActionController();

		$this->endpoint = new OrdersController();
		$this->endpoint->init( $this->order_schema, $collection_query, $update_utils, $action_controller );

		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Helper method to create a simple order.
	 *
	 * @param array $order_data Optional order data.
	 * @return WC_Order
	 */
	private function create_test_order( array $order_data = array() ): WC_Order {
		$default_data = array(
			'status'  => OrderStatus::PENDING,
			'billing' => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'email'      => 'john.doe@example.com',
				'phone'      => '555-1234',
				'address_1'  => '123 Main St',
				'city'       => 'Anytown',
				'state'      => 'CA',
				'postcode'   => '12345',
				'country'    => 'US',
			),
		);

		$order_data = wp_parse_args( $order_data, $default_data );

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		return wc_get_order( $data['id'] );
	}

	/**
	 * Helper method to validate response against schema.
	 *
	 * @param array $response_data Response data to validate.
	 * @param array $schema_properties Schema properties to check against.
	 */
	private function validate_response_against_schema( array $response_data, array $schema_properties ): void {
		foreach ( $schema_properties as $key => $schema ) {
			$this->assertArrayHasKey( $key, $response_data, "Response missing schema key: {$key}" );
		}
	}

	/**
	 * Test OrderSchema properties match response.
	 */
	public function test_order_schema_properties_match_response(): void {
		$order    = $this->create_test_order();
		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data     = $response->get_data();
		$schema_properties = $this->order_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $response_data, $schema_properties );
	}

	/**
	 * Test OrderItemSchema properties match response.
	 */
	public function test_order_item_schema_properties_match_response(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'line_items', $response_data );
		$this->assertNotEmpty( $response_data['line_items'] );

		$line_item         = $response_data['line_items'][0];
		$item_schema       = new OrderItemSchema();
		$schema_properties = $item_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $line_item, $schema_properties );

		// Verify values match what was sent.
		$this->assertEquals( $product->get_id(), $line_item['product_id'] );
		$this->assertEquals( 2, $line_item['quantity'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test OrderCouponSchema properties match response.
	 */
	public function test_order_coupon_schema_properties_match_response(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'test-coupon' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items'   => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'coupon_lines' => array(
					array(
						'code' => 'test-coupon',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'coupon_lines', $response_data );
		$this->assertNotEmpty( $response_data['coupon_lines'] );

		$coupon_line       = $response_data['coupon_lines'][0];
		$coupon_schema     = new OrderCouponSchema();
		$schema_properties = $coupon_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $coupon_line, $schema_properties );

		// Verify values match what was sent.
		$this->assertEquals( 'test-coupon', $coupon_line['code'] );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test OrderFeeSchema properties match response.
	 */
	public function test_order_fee_schema_properties_match_response(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'fee_lines'  => array(
					array(
						'name'  => 'Processing Fee',
						'total' => '5.00',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'fee_lines', $response_data );
		$this->assertNotEmpty( $response_data['fee_lines'] );

		$fee_line          = $response_data['fee_lines'][0];
		$fee_schema        = new OrderFeeSchema();
		$schema_properties = $fee_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $fee_line, $schema_properties );

		// Verify values match what was sent.
		$this->assertEquals( 'Processing Fee', $fee_line['name'] );
		$this->assertEquals( '5.00', $fee_line['total'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test OrderShippingSchema properties match response.
	 */
	public function test_order_shipping_schema_properties_match_response(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items'     => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'shipping_lines' => array(
					array(
						'method_title' => 'Standard Shipping',
						'method_id'    => 'flat_rate',
						'total'        => '10.00',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'shipping_lines', $response_data );
		$this->assertNotEmpty( $response_data['shipping_lines'] );

		$shipping_line     = $response_data['shipping_lines'][0];
		$shipping_schema   = new OrderShippingSchema();
		$schema_properties = $shipping_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $shipping_line, $schema_properties );

		// Verify values match what was sent.
		$this->assertEquals( 'Standard Shipping', $shipping_line['method_title'] );
		$this->assertEquals( 'flat_rate', $shipping_line['method_id'] );
		$this->assertEquals( '10.00', $shipping_line['total'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test OrderTaxSchema properties match response.
	 */
	public function test_order_tax_schema_properties_match_response(): void {
		// Enable taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create tax rate.
		$tax_rate = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '8.25',
				'tax_rate_name'     => 'CA Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'billing'    => array(
					'country' => 'US',
					'state'   => 'CA',
				),
			)
		);

		// Calculate taxes.
		$order->calculate_taxes();
		$order->calculate_totals();
			$order->save();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'tax_lines', $response_data );
		$this->assertNotEmpty( $response_data['tax_lines'] );

		$tax_line          = $response_data['tax_lines'][0];
		$tax_schema        = new OrderTaxSchema();
		$schema_properties = $tax_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $tax_line, $schema_properties );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test LIST endpoint returns orders with correct schema.
	 */
	public function test_orders_list_endpoint(): void {
		// Create test orders.
		$order1 = $this->create_test_order();
		$order2 = $this->create_test_order();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Validate first order against schema.
		$first_order       = $response_data[0];
		$schema_properties = $this->order_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $first_order, $schema_properties );

		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test GET endpoint returns order with correct schema.
	 */
	public function test_orders_get_endpoint(): void {
		$order = $this->create_test_order();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->order_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );

		$order->delete( true );
	}

	/**
	 * Test CREATE endpoint creates order with correct schema and values.
	 */
	public function test_orders_create_endpoint(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$order_data = array(
			'status'     => OrderStatus::PENDING,
			'billing'    => array(
				'first_name' => 'Jane',
				'last_name'  => 'Smith',
				'email'      => 'jane.smith@example.com',
				'phone'      => '555-5678',
				'address_1'  => '456 Oak Ave',
				'city'       => 'Springfield',
				'state'      => 'IL',
				'postcode'   => '62701',
				'country'    => 'US',
			),
			'line_items' => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 3,
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->order_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );

		// Verify order was created with correct values.
		$this->assertEquals( OrderStatus::PENDING, $response_data['status'] );
		$this->assertEquals( 'Jane', $response_data['billing']['first_name'] );
		$this->assertEquals( 'Smith', $response_data['billing']['last_name'] );
		$this->assertEquals( 'jane.smith@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-5678', $response_data['billing']['phone'] );
		$this->assertEquals( '456 Oak Ave', $response_data['billing']['address_1'] );
		$this->assertEquals( 'Springfield', $response_data['billing']['city'] );
		$this->assertEquals( 'IL', $response_data['billing']['state'] );
		$this->assertEquals( '62701', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify line items.
		$this->assertCount( 1, $response_data['line_items'] );
		$this->assertEquals( $product->get_id(), $response_data['line_items'][0]['product_id'] );
		$this->assertEquals( 3, $response_data['line_items'][0]['quantity'] );

		$product->delete( true );
		$order = wc_get_order( $response_data['id'] );
		$order->delete( true );
	}

	/**
	 * Test UPDATE endpoint updates order with correct schema.
	 */
	public function test_orders_update_endpoint(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$update_data = array(
			'status'     => OrderStatus::PROCESSING,
			'billing'    => array(
				'first_name' => 'Updated',
				'last_name'  => 'Name',
				'email'      => 'updated@example.com',
				'phone'      => '555-9999',
				'address_1'  => '789 New St',
				'city'       => 'New City',
				'state'      => 'NY',
				'postcode'   => '10001',
				'country'    => 'US',
			),
			'line_items' => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 2,
				),
			),
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->order_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );

		// Verify order was updated with correct values.
		$this->assertEquals( OrderStatus::PROCESSING, $response_data['status'] );
		$this->assertEquals( 'Updated', $response_data['billing']['first_name'] );
		$this->assertEquals( 'Name', $response_data['billing']['last_name'] );
		$this->assertEquals( 'updated@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-9999', $response_data['billing']['phone'] );
		$this->assertEquals( '789 New St', $response_data['billing']['address_1'] );
		$this->assertEquals( 'New City', $response_data['billing']['city'] );
		$this->assertEquals( 'NY', $response_data['billing']['state'] );
		$this->assertEquals( '10001', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify line items were updated correctly.
		$this->assertCount( 1, $response_data['line_items'] );
		$this->assertEquals( $product->get_id(), $response_data['line_items'][0]['product_id'] );
		$this->assertEquals( 2, $response_data['line_items'][0]['quantity'] );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test comprehensive value validation for all line types.
	 */
	public function test_comprehensive_value_validation(): void {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon( 'test-coupon' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$order_data = array(
			'status'         => OrderStatus::PENDING,
			'billing'        => array(
				'first_name' => 'Test',
				'last_name'  => 'User',
				'email'      => 'test@example.com',
				'phone'      => '555-0000',
				'address_1'  => '123 Test St',
				'city'       => 'Test City',
				'state'      => 'TS',
				'postcode'   => '12345',
				'country'    => 'US',
			),
			'line_items'     => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 2,
				),
			),
			'shipping_lines' => array(
				array(
					'method_title' => 'Test Shipping',
					'method_id'    => 'test_method',
					'total'        => '12.50',
				),
			),
			'fee_lines'      => array(
				array(
					'name'  => 'Test Fee',
					'total' => '3.75',
				),
			),
			'coupon_lines'   => array(
				array(
					'code' => 'test-coupon',
				),
			),
		);

		// Create order.
		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		// Verify all values match what was sent.
		$this->assertEquals( OrderStatus::PENDING, $response_data['status'] );
		// Verify billing address.
		$this->assertEquals( 'Test', $response_data['billing']['first_name'] );
		$this->assertEquals( 'User', $response_data['billing']['last_name'] );
		$this->assertEquals( 'test@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-0000', $response_data['billing']['phone'] );
		$this->assertEquals( '123 Test St', $response_data['billing']['address_1'] );
		$this->assertEquals( 'Test City', $response_data['billing']['city'] );
		$this->assertEquals( 'TS', $response_data['billing']['state'] );
		$this->assertEquals( '12345', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify line items.
		$this->assertCount( 1, $response_data['line_items'] );
		$this->assertEquals( $product->get_id(), $response_data['line_items'][0]['product_id'] );
		$this->assertEquals( 2, $response_data['line_items'][0]['quantity'] );

		// Verify shipping lines.
		$this->assertCount( 1, $response_data['shipping_lines'] );
		$this->assertEquals( 'Test Shipping', $response_data['shipping_lines'][0]['method_title'] );
		$this->assertEquals( 'test_method', $response_data['shipping_lines'][0]['method_id'] );
		$this->assertEquals( '12.50', $response_data['shipping_lines'][0]['total'] );

		// Verify fee lines.
		$this->assertCount( 1, $response_data['fee_lines'] );
		$this->assertEquals( 'Test Fee', $response_data['fee_lines'][0]['name'] );
		$this->assertEquals( '3.75', $response_data['fee_lines'][0]['total'] );

		// Verify coupon lines.
		$this->assertCount( 1, $response_data['coupon_lines'] );
		$this->assertEquals( 'test-coupon', $response_data['coupon_lines'][0]['code'] );

		// Clean up.
		$coupon->delete( true );
		$product->delete( true );
		$order = wc_get_order( $response_data['id'] );
		$order->delete( true );
	}

	/**
	 * Test UPDATE endpoint with line items, shipping, fees, and coupons.
	 */
	public function test_orders_update_with_all_line_types(): void {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon( 'test-coupon' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$order = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$update_data = array(
			'line_items'     => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 2,
				),
			),
			'shipping_lines' => array(
				array(
					'method_title' => 'Express Shipping',
					'method_id'    => 'express',
					'total'        => '15.00',
				),
			),
			'fee_lines'      => array(
				array(
					'name'  => 'Processing Fee',
					'total' => '3.00',
				),
			),
			'coupon_lines'   => array(
				array(
					'code' => 'test-coupon',
				),
			),
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Verify all line types were updated correctly.
		$this->assertCount( 1, $response_data['line_items'] );
		$this->assertEquals( 2, $response_data['line_items'][0]['quantity'] );

		$this->assertCount( 1, $response_data['shipping_lines'] );
		$this->assertEquals( 'Express Shipping', $response_data['shipping_lines'][0]['method_title'] );
		$this->assertEquals( 'express', $response_data['shipping_lines'][0]['method_id'] );
		$this->assertEquals( '15.00', $response_data['shipping_lines'][0]['total'] );

		$this->assertCount( 1, $response_data['fee_lines'] );
		$this->assertEquals( 'Processing Fee', $response_data['fee_lines'][0]['name'] );
		$this->assertEquals( '3.00', $response_data['fee_lines'][0]['total'] );

		$this->assertCount( 1, $response_data['coupon_lines'] );
		$this->assertEquals( 'test-coupon', $response_data['coupon_lines'][0]['code'] );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test edge case: invalid customer ID.
	 */
	public function test_invalid_customer_id(): void {
		$customer = WC_Helper_Customer::create_customer( 'test', 'password', 'test@example.com' );

		// Simulate multisite where customer doesn't belong to blog.
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

		$order_data = array(
			'customer_id' => $customer->get_id(),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_customer_id', $response->get_data()['code'] );

		$customer->delete( true );
	}

	/**
	 * Test edge case: created_via parameter handling.
	 */
	public function test_created_via_parameter(): void {
		$order_data = array(
			'created_via' => 'test-source',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertEquals( 'test-source', $response_data['created_via'] );

		$order = wc_get_order( $response_data['id'] );
		$order->delete( true );
	}

	/**
	 * Test edge case: empty created_via defaults to rest-api.
	 */
	public function test_empty_created_via_defaults_to_rest_api(): void {
		$order_data = array(
			'created_via' => '',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertEquals( 'rest-api', $response_data['created_via'] );

		$order = wc_get_order( $response_data['id'] );
		$order->delete( true );
	}

	/**
	 * Test edge case: created_via cannot be updated.
	 */
	public function test_created_via_cannot_be_updated(): void {
		$order                = $this->create_test_order();
		$original_created_via = $order->get_created_via();

		$update_data = array(
			'created_via' => 'updated-source',
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Created via should remain unchanged.
		$this->assertEquals( $original_created_via, $response_data['created_via'] );

		$order->delete( true );
	}

	/**
	 * Test edge case: created_via filtering when COT is enabled.
	 */
	public function test_created_via_filtering_with_cot_enabled(): void {
		$original_cot_setting = get_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION );

		// Skip test if COT cannot be safely enabled/disabled.
		try {
			update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		} catch ( Exception $e ) {
			$this->markTestSkipped( 'Custom Orders Table cannot be enabled: ' . $e->getMessage() );
		}

		$order1 = $this->create_test_order();
		$order1->set_created_via( 'checkout' );
		$order1->save();

		$order2 = $this->create_test_order();
		$order2->set_created_via( 'admin' );
		$order2->save();

		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'created_via', array( 'checkout' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertCount( 1, $response_data );
		$this->assertEquals( 'checkout', $response_data[0]['created_via'] );

		// Clean up orders first, then restore setting.
		$order1->delete( true );
		$order2->delete( true );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $original_cot_setting );
	}

	/**
	 * Test DELETE endpoint removes order.
	 */
	public function test_orders_delete_endpoint(): void {
		$order = $this->create_test_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$order_id = $order->get_id();

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/orders/' . $order_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Check that the response includes order data from the order (before deletion).
		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $response_data['id'], $order_id );
		$this->assertEquals( OrderStatus::COMPLETED, $response_data['status'] );

		// Check the order was actually deleted (trashed).
		$order = wc_get_order( $order_id );
		$this->assertEquals( OrderStatus::TRASH, $order->get_status( 'edit' ) );

		// Force deletion, skip trash.
		$request = new WP_REST_Request( 'DELETE', '/wc/v4/orders/' . $order_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status() );
	}

	/**
	 * Test _fields parameter filters response correctly.
	 */
	public function test_fields_parameter_filtering(): void {
		$order = $this->create_test_order();

		// Test single field.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$request->set_param( '_fields', 'id,status' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertArrayHasKey( 'status', $response_data );
		$this->assertArrayNotHasKey( 'billing', $response_data );
		$this->assertArrayNotHasKey( 'line_items', $response_data );

		// Test multiple fields.
		$request->set_param( '_fields', 'id,status,billing' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertArrayHasKey( 'status', $response_data );
		$this->assertArrayHasKey( 'billing', $response_data );
		$this->assertArrayNotHasKey( 'line_items', $response_data );

		$order->delete( true );
	}

	/**
	 * Test date filtering with before and after parameters.
	 */
	public function test_date_filtering(): void {
		// Set up specific time ranges for testing.
		$time_past   = time() - ( 2 * DAY_IN_SECONDS ); // 2 days ago.
		$time_recent = time() - HOUR_IN_SECONDS; // 1 hour ago.
		$time_future = time() + DAY_IN_SECONDS; // 1 day in future.

		// Create orders with specific dates.
		$order1 = $this->create_test_order();
		$order1->set_date_created( $time_past );
		$order1->save();

		$order2 = $this->create_test_order();
		$order2->set_date_created( $time_recent );
		$order2->save();

		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );

		// No date params should return all orders.
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Test 'after' parameter - should return orders created after the past time.
		$request->set_param( 'after', gmdate( DateTime::ATOM, $time_past ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertGreaterThanOrEqual( 1, count( $response_data ) );

		// Test 'before' parameter - should return orders created before the future time.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );
		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_future ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertGreaterThanOrEqual( 1, count( $response_data ) );

		// Test both 'after' and 'before' - should return orders in the range.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );
		$request->set_param( 'after', gmdate( DateTime::ATOM, $time_past ) );
		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_future ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertGreaterThanOrEqual( 1, count( $response_data ) );

		// Test 'after' with future time - should return no orders.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );
		$request->set_param( 'after', gmdate( DateTime::ATOM, $time_future ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 0, $response_data );

		// Test 'before' with past time - should return no orders.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );
		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_past ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 0, $response_data );

		// Test narrow range that should return orders between past and recent times.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'dates_are_gmt', 1 );
		$request->set_param( 'after', gmdate( DateTime::ATOM, $time_past - HOUR_IN_SECONDS ) );
		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_recent + HOUR_IN_SECONDS ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertGreaterThanOrEqual( 1, count( $response_data ) );

		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test status filtering.
	 */
	public function test_status_filtering(): void {
		$order1 = $this->create_test_order();
		$order1->set_status( OrderStatus::PENDING );
		$order1->save();

		$order2 = $this->create_test_order();
		$order2->set_status( OrderStatus::PROCESSING );
		$order2->save();

		// Test filtering by pending status - should find order1 but not order2.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'status', OrderStatus::PENDING );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order ) {
			$this->assertEquals( OrderStatus::PENDING, $order['status'], 'All returned orders should have pending status' );
			if ( $order['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with pending status' );
		$this->assertFalse( $found_order2, 'Should not find order with processing status when filtering by pending' );

		// Test filtering by processing status - should find order2 but not order1.
		$request->set_param( 'status', OrderStatus::PROCESSING );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order ) {
			$this->assertEquals( OrderStatus::PROCESSING, $order['status'], 'All returned orders should have processing status' );
			if ( $order['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertFalse( $found_order1, 'Should not find order with pending status when filtering by processing' );
		$this->assertTrue( $found_order2, 'Should find order with processing status' );

		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test customer filtering.
	 */
	public function test_customer_filtering(): void {
		$customer = WC_Helper_Customer::create_customer( 'test', 'password', 'test@example.com' );

		$order1 = $this->create_test_order();
		$order1->set_customer_id( $customer->get_id() );
		$order1->save();

		$order2 = $this->create_test_order();
		$order2->set_customer_id( 0 ); // Guest order.
		$order2->save();

		// Test filtering by customer ID - should find order1 but not order2.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'customer', $customer->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order ) {
			$this->assertEquals( $customer->get_id(), $order['customer_id'], 'All returned orders should have the correct customer ID' );
			if ( $order['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with matching customer ID' );
		$this->assertFalse( $found_order2, 'Should not find guest order when filtering by customer ID' );

		// Test filtering by guest orders (customer ID 0) - should find order2 but not order1.
		$request->set_param( 'customer', 0 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order ) {
			$this->assertEquals( 0, $order['customer_id'], 'All returned orders should be guest orders' );
			if ( $order['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertFalse( $found_order1, 'Should not find order with customer ID when filtering by guest orders' );
		$this->assertTrue( $found_order2, 'Should find guest order when filtering by customer ID 0' );

		$customer->delete( true );
		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test order by parameters.
	 */
	public function test_order_by(): void {
		$order1 = $this->create_test_order();
		$order2 = $this->create_test_order();

		// Test ordering by date (default).
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'orderby', 'date' );
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should have at least 2 orders.
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Test ordering by ID.
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test order by total functionality.
	 */
	public function test_order_by_total(): void {
		// Create orders with different totals.
		$order_totals = array( 100.00, 50.00, 250.50, 75.25, 500.00 );
		$orders       = array();
		foreach ( $order_totals as $order_total ) {
			$order = $this->create_test_order();
			$order->set_total( $order_total );
			$order->save();
			$orders[] = $order;
		}

		// Test ascending order.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'orderby', 'total' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertGreaterThanOrEqual( 5, count( $response_data ) );

		// Verify ascending order by checking totals.
		$totals_asc = array();
		foreach ( $response_data as $order_data ) {
			$totals_asc[] = (float) $order_data['total'];
		}

		// Check that totals are in ascending order.
		$sorted_totals_asc = $totals_asc;
		sort( $sorted_totals_asc );
		$this->assertEquals( $sorted_totals_asc, $totals_asc, 'Orders should be sorted by total in ascending order' );

		// Test descending order.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertGreaterThanOrEqual( 5, count( $response_data ) );

		// Verify descending order by checking totals.
		$totals_desc = array();
		foreach ( $response_data as $order_data ) {
			$totals_desc[] = (float) $order_data['total'];
		}

		// Check that totals are in descending order.
		$sorted_totals_desc = $totals_desc;
		rsort( $sorted_totals_desc );
		$this->assertEquals( $sorted_totals_desc, $totals_desc, 'Orders should be sorted by total in descending order' );

		// Clean up.
		foreach ( $orders as $order ) {
			$order->delete( true );
		}
	}

	/**
	 * Test total filtering with operators. Only basic tests are needed here because operators are tested in the data stores.
	 *
	 * @see WC_Order_Data_Store_CPT_Test
	 * @see OrdersTableQueryTests
	 */
	public function test_total_filtering(): void {
		// Create orders with different totals.
		$order_totals_to_test = array( 100.00, 100.00, 250.50, 500.75, 1000.00 );
		$orders               = array();
		foreach ( $order_totals_to_test as $order_total ) {
			$order = $this->create_test_order();
			$order->set_total( $order_total );
			$order->save();
			$orders[] = $order;
		}

		// Unsupported operator should return an error.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_query_params(
			array(
				'total'          => 250.50,
				'total_operator' => 'not supported',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Between operator should return an error if value is not an array.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_query_params(
			array(
				'total'          => 250.50,
				'total_operator' => 'between',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Between operator should return an error if value is not an array of 2 numbers.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_query_params(
			array(
				'total'          => array( 250.50 ),
				'total_operator' => 'not supported',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Test simple equality filtering.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_query_params(
			array(
				'total'          => 250.50,
				'total_operator' => 'is',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		// Test greater than operator.
		$request->set_query_params(
			array(
				'total'          => '200',
				'total_operator' => 'greaterThan',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 3, $response->get_data() );

		// Test between operator.
		$request->set_query_params(
			array(
				'total'          => array( 200.00, 300.00 ),
				'total_operator' => 'between',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		$request->set_query_params(
			array(
				'total'          => '200,300',
				'total_operator' => 'between',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );

		// Clean up.
		foreach ( $orders as $order ) {
			$order->delete( true );
		}
	}

	/**
	 * Test search functionality.
	 */
	public function test_search(): void {
		// Create orders with different searchable content.
		$order1 = $this->create_test_order(
			array(
				'billing' => array(
					'first_name' => 'SearchTest',
					'last_name'  => 'User',
					'email'      => 'searchtest@example.com',
				),
			)
		);

		$order2 = $this->create_test_order(
			array(
				'billing' => array(
					'first_name' => 'DifferentName',
					'last_name'  => 'DifferentUser',
					'email'      => 'different@example.com',
				),
			)
		);

		// Test searching by first name - should find order1 but not order2.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'search', 'SearchTest' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should find order1.
		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order_data['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with matching first name' );
		$this->assertFalse( $found_order2, 'Should not find order with non-matching first name' );

		// Test searching by email - should find order1 but not order2.
		$request->set_param( 'search', 'searchtest@example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1 = false;
		$found_order2 = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order_data['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with matching email' );
		$this->assertFalse( $found_order2, 'Should not find order with non-matching email' );

		// Test searching for non-existent term - should find no orders.
		$request->set_param( 'search', 'NonExistentTerm123' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_any_order = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order1->get_id() || $order_data['id'] === $order2->get_id() ) {
				$found_any_order = true;
				break;
			}
		}
		$this->assertFalse( $found_any_order, 'Should not find any orders for non-existent search term' );

		$order1->delete( true );
		$order2->delete( true );
	}

	/**
	 * Test fulfillment status filtering.
	 */
	public function test_fulfillment_status_filtering(): void {
		// Create orders with different fulfillment statuses.
		$order1 = $this->create_test_order();
		$order1->add_meta_data( '_fulfillment_status', 'fulfilled' );
		$order1->save();

		$order2 = $this->create_test_order();
		$order2->add_meta_data( '_fulfillment_status', 'partially_fulfilled' );
		$order2->save();

		$order3 = $this->create_test_order();
		$order3->add_meta_data( '_fulfillment_status', 'unfulfilled' );
		$order3->save();

		$order4 = $this->create_test_order();
		// Order4 has no fulfillment status meta (no_fulfillments).

		// Test filtering by 'fulfilled' status.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'fulfillment_status', 'fulfilled' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1       = false;
		$found_other_orders = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( in_array( $order_data['id'], array( $order2->get_id(), $order3->get_id(), $order4->get_id() ), true ) ) {
				$found_other_orders = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with fulfilled status' );
		$this->assertFalse( $found_other_orders, 'Should not find orders with other fulfillment statuses' );

		// Test filtering by 'partially_fulfilled' status.
		$request->set_param( 'fulfillment_status', 'partially_fulfilled' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order2       = false;
		$found_other_orders = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order2->get_id() ) {
				$found_order2 = true;
			}
			if ( in_array( $order_data['id'], array( $order1->get_id(), $order3->get_id(), $order4->get_id() ), true ) ) {
				$found_other_orders = true;
			}
		}
		$this->assertTrue( $found_order2, 'Should find order with partially_fulfilled status' );
		$this->assertFalse( $found_other_orders, 'Should not find orders with other fulfillment statuses' );

		// Test filtering by 'unfulfilled' status.
		$request->set_param( 'fulfillment_status', 'unfulfilled' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order3       = false;
		$found_other_orders = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order3->get_id() ) {
				$found_order3 = true;
			}
			if ( in_array( $order_data['id'], array( $order1->get_id(), $order2->get_id(), $order4->get_id() ), true ) ) {
				$found_other_orders = true;
			}
		}
		$this->assertTrue( $found_order3, 'Should find order with unfulfilled status' );
		$this->assertFalse( $found_other_orders, 'Should not find orders with other fulfillment statuses' );

		// Test filtering by 'no_fulfillments' status.
		$request->set_param( 'fulfillment_status', 'no_fulfillments' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order4       = false;
		$found_other_orders = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order4->get_id() ) {
				$found_order4 = true;
			}
			if ( in_array( $order_data['id'], array( $order1->get_id(), $order2->get_id(), $order3->get_id() ), true ) ) {
				$found_other_orders = true;
			}
		}
		$this->assertTrue( $found_order4, 'Should find order with no fulfillments' );
		$this->assertFalse( $found_other_orders, 'Should not find orders with fulfillment statuses' );

		// Test filtering by multiple fulfillment statuses.
		$request->set_param( 'fulfillment_status', array( 'fulfilled', 'unfulfilled' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_order1       = false;
		$found_order3       = false;
		$found_other_orders = false;
		foreach ( $response_data as $order_data ) {
			if ( $order_data['id'] === $order1->get_id() ) {
				$found_order1 = true;
			}
			if ( $order_data['id'] === $order3->get_id() ) {
				$found_order3 = true;
			}
			if ( in_array( $order_data['id'], array( $order2->get_id(), $order4->get_id() ), true ) ) {
				$found_other_orders = true;
			}
		}
		$this->assertTrue( $found_order1, 'Should find order with fulfilled status' );
		$this->assertTrue( $found_order3, 'Should find order with unfulfilled status' );
		$this->assertFalse( $found_other_orders, 'Should not find orders with other fulfillment statuses' );

		// Test filtering by invalid fulfillment status.
		$request->set_param( 'fulfillment_status', 'invalid_status' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		// Clean up.
		$order1->delete( true );
		$order2->delete( true );
		$order3->delete( true );
		$order4->delete( true );
	}

	/**
	 * Test UPDATE endpoint with payment_complete action.
	 */
	public function test_orders_update_with_payment_complete_action(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// Verify order is initially pending.
		$this->assertEquals( OrderStatus::PENDING, $order->get_status() );
		$this->assertEmpty( $order->get_date_paid() );

		$update_data = array(
			'payment_complete' => true,
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Verify order status changed to processing (payment complete).
		$this->assertEquals( OrderStatus::PROCESSING, $response_data['status'] );

		// Verify order was marked as paid.
		$updated_order = wc_get_order( $order->get_id() );
		$this->assertNotEmpty( $updated_order->get_date_paid() );
		$this->assertEquals( OrderStatus::PROCESSING, $updated_order->get_status() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test UPDATE endpoint with payment_complete action and transaction_id.
	 */
	public function test_orders_update_with_payment_complete_action_and_transaction_id(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$update_data = array(
			'payment_complete' => true,
			'transaction_id'   => 'test-transaction-123',
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify transaction ID was set.
		$updated_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'test-transaction-123', $updated_order->get_transaction_id() );

		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test UPDATE endpoint with reset_download_permissions action.
	 */
	public function test_orders_update_with_reset_download_permissions_action(): void {
		// Create a downloadable product.
		$product  = WC_Helper_Product::create_downloadable_product(
			array(
				array(
					'name' => 'Book 1',
					'file' => 'https://always.trusted/123.pdf',
				),
				array(
					'name' => 'Book 2',
					'file' => 'https://new.supplier/456.pdf',
				),
			)
		);
		$customer = WC_Helper_Customer::create_customer( 'test', 'password', 'test@example.com' );
		$order    = $this->create_test_order(
			array(
				'customer_id' => $customer->get_id(),
				'status'      => OrderStatus::COMPLETED,
				'line_items'  => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// Delete the download permissions to simulate a scenario where they need regeneration.
		$data_store = \WC_Data_Store::load( 'customer-download' );
		$data_store->delete_by_user_id( $customer->get_id() );
		$initial_permissions = $data_store->get_downloads_for_customer( $customer->get_id() );
		$this->assertEmpty( $initial_permissions );

		$update_data = array(
			'reset_download_permissions' => true,
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/orders/' . $order->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify download permissions were reset.
		$reset_permissions = $data_store->get_downloads_for_customer( $customer->get_id() );
		$this->assertNotEmpty( $reset_permissions );

		$product->delete( true );
		$order->delete( true );
		$customer->delete( true );
	}

	/**
	 * Test refund_total and refund_tax fields with refunds.
	 */
	public function test_refund_total_and_refund_tax_fields(): void {
		// Enable taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create tax rate.
		$tax_rate = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '8.25',
				'tax_rate_name'     => 'CA Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'billing'    => array(
					'country' => 'US',
					'state'   => 'CA',
				),
			)
		);

		// Calculate taxes.
		$order->calculate_taxes();
		$order->calculate_totals();
		$order->save();

		// Create a refund for the order.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'Test refund',
			)
		);

		$this->assertNotInstanceOf( 'WP_Error', $refund );

		// Test that refund_total and refund_tax are included when requested.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() );
		$request->set_param( '_fields', 'id,refund_total,refund_tax' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'refund_total', $response_data );
		$this->assertArrayHasKey( 'refund_tax', $response_data );
		$this->assertEquals( '10.00', $response_data['refund_total'] );
		$this->assertIsString( $response_data['refund_tax'] );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test pagination functionality with posts_per_page=2 and 4 test orders.
	 */
	public function test_pagination(): void {
		// Create 4 test orders.
		$orders = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$order    = $this->create_test_order(
				array(
					'billing' => array(
						'first_name' => "Test{$i}",
						'last_name'  => 'User',
						'email'      => "test{$i}@example.com",
					),
				)
			);
			$orders[] = $order;
		}

		// Test first page (page=1, per_page=2) - should return 2 orders.
		$request = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 2, $response_data, 'First page should return exactly 2 orders' );

		// Test second page (page=2, per_page=2) - should return 2 orders.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 2, $response_data, 'Second page should return exactly 2 orders' );

		// Test third page (page=3, per_page=2) - should return 0 orders.
		$request->set_param( 'page', 3 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 0, $response_data, 'Third page should return 0 orders' );

		// Test that no duplicate order IDs are returned across pages.
		$all_order_ids = array();
		for ( $page = 1; $page <= 2; $page++ ) {
			$request->set_param( 'page', $page );
			$response      = $this->server->dispatch( $request );
			$response_data = $response->get_data();

			foreach ( $response_data as $order_data ) {
				$all_order_ids[] = $order_data['id'];
			}
		}

		// Should have exactly 4 unique order IDs (no duplicates).
		$unique_order_ids = array_unique( $all_order_ids );
		$this->assertCount( 4, $unique_order_ids, 'Should have exactly 4 unique orders across all pages with no duplicates' );
		$this->assertCount( 4, $all_order_ids, 'Total order IDs should equal unique order IDs (no duplicates)' );

		// Clean up.
		foreach ( $orders as $order ) {
			$order->delete( true );
		}
	}
}
