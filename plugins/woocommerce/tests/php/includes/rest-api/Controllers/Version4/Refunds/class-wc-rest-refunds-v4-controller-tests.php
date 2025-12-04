<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Controller as RefundsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\CollectionQuery;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils;

/**
 * Refunds Controller tests for V4 REST API.
 *
 * @group refund-query-tests
 */
class WC_REST_Refunds_V4_Controller_Tests extends WC_REST_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Endpoint instance.
	 *
	 * @var RefundsController
	 */
	private $endpoint;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Refund schema instance.
	 *
	 * @var RefundSchema
	 */
	private $refund_schema;

	/**
	 * Collection of created orders for cleanup.
	 *
	 * @var array
	 */
	private $created_orders = array();

	/**
	 * Collection of created refunds for cleanup.
	 *
	 * @var array
	 */
	private $created_refunds = array();

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		// Clean up created refunds.
		foreach ( $this->created_refunds as $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( $refund ) {
				$refund->delete( true );
			}
		}
		$this->created_refunds = array();

		// Clean up created orders.
		foreach ( $this->created_orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->delete( true );
			}
		}
		$this->created_orders = array();

		// Clean up tax data.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );

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
		$this->refund_schema = new RefundSchema();

		// Create utils instances.
		$collection_query = new CollectionQuery();
		$data_utils       = new DataUtils();

		$this->endpoint = new RefundsController();
		$this->endpoint->init( $this->refund_schema, $collection_query, $data_utils );

		$this->user_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_email' => 'test@example.com',
				'user_pass'  => 'password',
				'role'       => 'administrator',
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
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$default_data = array(
			'status'     => OrderStatus::COMPLETED,
			'billing'    => array(
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
			'line_items' => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 1,
				),
			),
		);

		$order_data = wp_parse_args( $order_data, $default_data );

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		$order                  = wc_get_order( $data['id'] );
		$this->created_orders[] = $order->get_id();

		$product->delete( true );

		return $order;
	}

	/**
	 * Helper method to create a test refund.
	 *
	 * @param WC_Order $order Order to refund.
	 * @param array    $refund_data Optional refund data.
	 * @return WC_Order_Refund
	 */
	private function create_test_refund( WC_Order $order, array $refund_data = array() ): WC_Order_Refund {
		$default_data = array(
			'amount'     => 5.00,
			'reason'     => 'Test refund',
			'line_items' => array(),
		);

		$refund_data = wp_parse_args( $refund_data, $default_data );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array_merge(
				$refund_data,
				array( 'order_id' => $order->get_id() )
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		$refund                  = wc_get_order( $data['id'] );
		$this->created_refunds[] = $refund->get_id();

		return $refund;
	}

	/**
	 * Helper method to validate response against schema.
	 *
	 * @param array $response_data Response data to validate.
	 * @param array $schema_properties Schema properties to check against.
	 */
	private function validate_response_against_schema( array $response_data, array $schema_properties ): void {
		foreach ( $schema_properties as $property => $schema ) {
			$this->assertArrayHasKey( $property, $response_data, "Response should contain property: {$property}" );
		}
	}

	/**
	 * Test GET /wc/v4/refunds endpoint returns collection of refunds.
	 */
	public function test_refunds_list_endpoint(): void {
		// Create test orders and refunds.
		$order1  = $this->create_test_order();
		$order2  = $this->create_test_order();
		$refund1 = $this->create_test_refund( $order1 );
		$refund2 = $this->create_test_refund( $order2 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/refunds' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Validate first refund against schema.
		$first_refund      = $response_data[0];
		$schema_properties = $this->refund_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $first_refund, $schema_properties );
	}

	/**
	 * Test GET /wc/v4/refunds/{id} endpoint returns single refund.
	 */
	public function test_refunds_get_endpoint(): void {
		$order  = $this->create_test_order();
		$refund = $this->create_test_refund( $order );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/refunds/' . $refund->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertEquals( $refund->get_id(), $response_data['id'] );

		// Validate response against schema.
		$schema_properties = $this->refund_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );
	}

	/**
	 * Test POST /wc/v4/refunds endpoint creates refund.
	 */
	public function test_refunds_create_endpoint(): void {
		$order       = $this->create_test_order();
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 5.00,
			'reason'     => 'Customer requested refund',
			'line_items' => array(),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		if ( $response->get_status() !== 201 ) {
			$response_data = $response->get_data();
			$this->fail( 'Expected 201, got ' . $response->get_status() . '. Response: ' . print_r( $response_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );
		$this->assertEquals( '5.00', $response_data['amount'] );
		$this->assertEquals( 'Customer requested refund', $response_data['reason'] );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Validate response against schema.
		$schema_properties = $this->refund_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );
	}

	/**
	 * Test DELETE /wc/v4/refunds/{id} endpoint deletes refund (hard delete only).
	 */
	public function test_refunds_delete_endpoint(): void {
		$order     = $this->create_test_order();
		$refund    = $this->create_test_refund( $order );
		$refund_id = $refund->get_id();

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/refunds/' . $refund_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 204, $response->get_status() );

		// Check the refund was actually deleted (hard delete).
		$deleted_refund = wc_get_order( $refund_id );
		$this->assertFalse( $deleted_refund );
	}

	/**
	 * Test pagination works correctly for refunds collection.
	 */
	public function test_refunds_pagination(): void {
		// Create 4 test orders and refunds.
		$refunds = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$order     = $this->create_test_order(
				array(
					'billing' => array(
						'first_name' => "Test{$i}",
						'last_name'  => 'User',
						'email'      => "test{$i}@example.com",
					),
				)
			);
			$refund    = $this->create_test_refund( $order );
			$refunds[] = $refund;
		}

		// Test first page (page=1, per_page=2) - should return 2 refunds.
		$request = new WP_REST_Request( 'GET', '/wc/v4/refunds' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 2, $response_data, 'First page should return exactly 2 refunds' );

		// Test second page (page=2, per_page=2) - should return 2 refunds.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 2, $response_data, 'Second page should return exactly 2 refunds' );

		// Test third page (page=3, per_page=2) - should return 0 refunds.
		$request->set_param( 'page', 3 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();
		$this->assertCount( 0, $response_data, 'Third page should return 0 refunds' );
	}

	/**
	 * Test order_id filter works correctly for refunds collection.
	 */
	public function test_refunds_order_id_filter(): void {
		// Create two orders with refunds.
		$order1  = $this->create_test_order();
		$order2  = $this->create_test_order();
		$refund1 = $this->create_test_refund( $order1 );
		$refund2 = $this->create_test_refund( $order2 );

		// Test filtering by order_id.
		$request = new WP_REST_Request( 'GET', '/wc/v4/refunds' );
		$request->set_param( 'order_id', $order1->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertCount( 1, $response_data, 'Should return exactly 1 refund for the specified order' );
		$this->assertEquals( $order1->get_id(), $response_data[0]['order_id'] );

		// Test filtering by different order_id.
		$request->set_param( 'order_id', $order2->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertCount( 1, $response_data, 'Should return exactly 1 refund for the second order' );
		$this->assertEquals( $order2->get_id(), $response_data[0]['order_id'] );
	}

	/**
	 * Test refund creation with line items.
	 */
	public function test_refunds_create_with_line_items(): void {
		// Create order with product.
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

		// Get the line item ID.
		$line_items = $order->get_items();
		$line_item  = reset( $line_items );

		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 5.00,
			'reason'     => 'Partial refund for damaged item',
			'line_items' => array(
				array(
					'line_item_id' => $line_item->get_id(),
					'quantity'     => 1,
					'refund_total' => 5.00,
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );
		$this->assertEquals( '5.00', $response_data['amount'] );
		$this->assertArrayHasKey( 'line_items', $response_data );
		$this->assertCount( 1, $response_data['line_items'] );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation with automatic tax extraction (multiple non-compound rates).
	 */
	public function test_refunds_create_with_automatic_tax_extraction(): void {
		// Create two non-compound tax rates to test proportional splitting.
		$tax_rate_id_1 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '23.0000',
				'tax_rate_name'     => 'Tax 1',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_id_2 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'Tax 2',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		// Create order with product and taxes.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		// Tax 1 (23%): 23.00, Tax 2 (5%): 5.00, Total: 128.00.
		$item->set_taxes(
			array(
				'total'    => array(
					$tax_rate_id_1 => 23.00,
					$tax_rate_id_2 => 5.00,
				),
				'subtotal' => array(
					$tax_rate_id_1 => 23.00,
					$tax_rate_id_2 => 5.00,
				),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item_1 = new WC_Order_Item_Tax();
		$tax_item_1->set_rate( $tax_rate_id_1 );
		$tax_item_1->set_tax_total( 23.00 );
		$tax_item_1->save();
		$order->add_item( $tax_item_1 );

		$tax_item_2 = new WC_Order_Item_Tax();
		$tax_item_2->set_rate( $tax_rate_id_2 );
		$tax_item_2->set_tax_total( 5.00 );
		$tax_item_2->save();
		$order->add_item( $tax_item_2 );

		$order->set_billing_country( 'US' );
		$order->set_total( 128.00 );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create refund with just refund_total (should extract and split tax automatically).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 128.00,
			'reason'     => 'Testing automatic tax extraction with multiple rates',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 128.00, // Includes 23.00 + 5.00 tax.
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), 'Refund should be created successfully' );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );

		// Total refund amount should include extracted taxes.
		$this->assertEquals( '128.00', $response_data['amount'], 'Refund amount should include both taxes' );

		// Verify taxes were extracted and split proportionally on the refund line item.
		$refund           = wc_get_order( $response_data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );

		// Line item total should exclude tax (negative value for refund).
		$this->assertEquals( -100.00, $refund_line_item->get_total(), 'Line item total should be -100.00 (excluding tax)' );

		// Line item taxes should contain both extracted taxes split proportionally.
		$refund_taxes = $refund_line_item->get_taxes();
		$this->assertArrayHasKey( 'total', $refund_taxes, 'Line item should have taxes array' );
		$this->assertArrayHasKey( $tax_rate_id_1, $refund_taxes['total'], 'Line item should have tax for rate ID 1' );
		$this->assertArrayHasKey( $tax_rate_id_2, $refund_taxes['total'], 'Line item should have tax for rate ID 2' );
		$this->assertEquals( -23.00, (float) $refund_taxes['total'][ $tax_rate_id_1 ], 'Extracted tax 1 should be -23.00' );
		$this->assertEquals( -5.00, (float) $refund_taxes['total'][ $tax_rate_id_2 ], 'Extracted tax 2 should be -5.00' );

		// Verify refund has tax items for both rates.
		$refund_tax_items = $refund->get_items( 'tax' );
		$this->assertCount( 2, $refund_tax_items, 'Refund should have 2 tax items' );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation with automatic tax extraction using compound taxes.
	 */
	public function test_refunds_create_with_compound_tax_extraction(): void {
		// Create a regular tax rate (10%).
		$tax_rate_id_1 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'State Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create a compound tax rate (5%) - applies on top of base + tax_rate_id_1.
		$tax_rate_id_2 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'Compound Tax',
				'tax_rate_priority' => '2',
				'tax_rate_compound' => '1',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		// Create order with product and compound taxes.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);

		// Manually calculate compound taxes:
		// Base: 100.00
		// Tax 1 (10%): 10.00
		// Tax 2 (5% compound on 110.00): 5.50
		// Total: 115.50.
		$item->set_taxes(
			array(
				'total'    => array(
					$tax_rate_id_1 => 10.00,
					$tax_rate_id_2 => 5.50,
				),
				'subtotal' => array(
					$tax_rate_id_1 => 10.00,
					$tax_rate_id_2 => 5.50,
				),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item_1 = new WC_Order_Item_Tax();
		$tax_item_1->set_rate( $tax_rate_id_1 );
		$tax_item_1->set_tax_total( 10.00 );
		$tax_item_1->set_compound( false );
		$tax_item_1->save();
		$order->add_item( $tax_item_1 );

		$tax_item_2 = new WC_Order_Item_Tax();
		$tax_item_2->set_rate( $tax_rate_id_2 );
		$tax_item_2->set_tax_total( 5.50 );
		$tax_item_2->set_compound( true );
		$tax_item_2->save();
		$order->add_item( $tax_item_2 );

		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CA' );
		$order->set_total( 115.50 );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create refund with just refund_total (should extract compound taxes automatically).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 115.50,
			'reason'     => 'Testing automatic compound tax extraction',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 115.50, // Includes 10.00 + 5.50 compound tax.
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), 'Refund should be created successfully' );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );

		// Total refund amount should include extracted compound taxes.
		$this->assertEquals( '115.50', $response_data['amount'], 'Refund amount should include compound taxes' );

		// Verify compound taxes were extracted and recorded on the refund line item.
		$refund           = wc_get_order( $response_data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );

		// Line item total should exclude tax (negative value for refund).
		$this->assertEquals( -100.00, $refund_line_item->get_total(), 'Line item total should be -100.00 (excluding tax)' );

		// Line item taxes should contain the extracted compound taxes.
		$refund_taxes = $refund_line_item->get_taxes();
		$this->assertArrayHasKey( 'total', $refund_taxes, 'Line item should have taxes array' );
		$this->assertArrayHasKey( $tax_rate_id_1, $refund_taxes['total'], 'Line item should have tax for rate ID 1' );
		$this->assertArrayHasKey( $tax_rate_id_2, $refund_taxes['total'], 'Line item should have tax for compound rate ID 2' );
		$this->assertEquals( -10.00, (float) $refund_taxes['total'][ $tax_rate_id_1 ], 'Extracted tax 1 should be -10.00' );
		$this->assertEquals( -5.50, (float) $refund_taxes['total'][ $tax_rate_id_2 ], 'Extracted compound tax 2 should be -5.50' );

		// Verify refund has tax items for both rates.
		$refund_tax_items = $refund->get_items( 'tax' );
		$this->assertCount( 2, $refund_tax_items, 'Refund should have 2 tax items (regular and compound)' );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation with explicit tax array (legacy format).
	 */
	public function test_refunds_create_with_explicit_tax_array(): void {
		// Create two tax rates.
		$tax_rate_id_1 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '23.0000',
				'tax_rate_name'     => 'Tax 1',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_id_2 = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'Tax 2',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		// Create order with product and taxes.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		// Tax 1 (23%): 11.50, Tax 2 (5%): 2.50, Total: 64.00.
		$item->set_taxes(
			array(
				'total'    => array(
					$tax_rate_id_1 => 11.50,
					$tax_rate_id_2 => 2.50,
				),
				'subtotal' => array(
					$tax_rate_id_1 => 11.50,
					$tax_rate_id_2 => 2.50,
				),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item_1 = new WC_Order_Item_Tax();
		$tax_item_1->set_rate( $tax_rate_id_1 );
		$tax_item_1->set_tax_total( 11.50 );
		$tax_item_1->save();
		$order->add_item( $tax_item_1 );

		$tax_item_2 = new WC_Order_Item_Tax();
		$tax_item_2->set_rate( $tax_rate_id_2 );
		$tax_item_2->set_tax_total( 2.50 );
		$tax_item_2->save();
		$order->add_item( $tax_item_2 );

		$order->set_billing_country( 'US' );
		$order->set_total( 64.00 );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create partial refund with explicit refund_tax array (legacy backward compatibility).
		// Refunding 30.00 out of 50.00 subtotal (30.00 + 6.90 + 1.50 = 38.40).
		// Don't specify amount - let it auto-calculate from line items.
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'reason'     => 'Testing explicit tax array (legacy format)',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 30.00, // Excluding tax.
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id_1,
							'refund_total' => 6.90, // 23% of 30.00.
						),
						array(
							'id'           => $tax_rate_id_2,
							'refund_total' => 1.50, // 5% of 30.00.
						),
					),
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), 'Refund should be created successfully with explicit tax' );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );

		// Total refund amount should include the explicit taxes.
		$this->assertEquals( '38.40', $response_data['amount'], 'Refund amount should include explicit taxes' );

		// Verify explicit taxes were recorded on the refund line item.
		$refund           = wc_get_order( $response_data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );

		// Line item total should exclude tax.
		$this->assertEquals( -30.00, $refund_line_item->get_total(), 'Line item total should be -30.00 (excluding tax)' );

		// Line item taxes should contain the explicit tax values.
		$refund_taxes = $refund_line_item->get_taxes();
		$this->assertEquals( -6.90, (float) $refund_taxes['total'][ $tax_rate_id_1 ], 'Explicit tax 1 should be -6.90' );
		$this->assertEquals( -1.50, (float) $refund_taxes['total'][ $tax_rate_id_2 ], 'Explicit tax 2 should be -1.50' );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation fails when refund_total exceeds line item total.
	 */
	public function test_refunds_create_validation_error_exceeds_total(): void {
		// Create a tax rate.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create order with product and taxes.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => 10.00 ),
				'subtotal' => array( $tax_rate_id => 10.00 ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( 10.00 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_billing_country( 'US' );
		$order->set_total( 110.00 );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Try to create refund with refund_total exceeding line item total (should fail).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 500.00,
			'reason'     => 'Should fail - exceeding total',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 500.00, // Exceeds 110.00 (item total with tax).
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		// Should return 400 Bad Request.
		$this->assertEquals( 400, $response->get_status(), 'Refund should fail with 400 status' );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'code', $response_data );
		$this->assertEquals( 'invalid_refund_amount', $response_data['code'] );
		$this->assertStringContainsString( 'cannot be greater than the line item total including tax', $response_data['message'] );

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation fails when amount is less than line items total (under-refunding).
	 */
	public function test_refunds_create_validation_error_under_refunding(): void {
		// Enable tax calculations.
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create a tax rate.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create order with product and taxes.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => 10.00 ),
				'subtotal' => array( $tax_rate_id => 10.00 ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( 10.00 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_billing_country( 'US' );
		$order->set_total( 110.00 );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Try to create refund with amount LESS than line items total (should fail).
		// Line items: 110.00, but amount: 50.00 (under-refunding).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'amount'     => 50.00,
			'reason'     => 'Should fail - under-refunding',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 110.00, // Line items total is 110.00.
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		// Should return 400 Bad Request.
		$this->assertEquals( 400, $response->get_status(), 'Refund should fail with 400 status' );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'code', $response_data );
		$this->assertEquals( 'invalid_refund_amount', $response_data['code'] );
		$this->assertStringContainsString( 'cannot be less than the total of line items', $response_data['message'] );
		$this->assertStringContainsString( '110.00', $response_data['message'], 'Error should show calculated total' );

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * Test refund creation with API refund and restock options.
	 */
	public function test_refunds_create_with_api_options(): void {
		$order       = $this->create_test_order();
		$refund_data = array(
			'order_id'    => $order->get_id(),
			'amount'      => 5.00,
			'reason'      => 'API refund test',
			'api_refund'  => false,
			'api_restock' => true,
			'line_items'  => array(),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		if ( $response->get_status() !== 201 ) {
			$response_data = $response->get_data();
			$this->fail( 'Expected 201, got ' . $response->get_status() . '. Response: ' . print_r( $response_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );
		$this->assertEquals( '5.00', $response_data['amount'] );
		$this->assertEquals( 'API refund test', $response_data['reason'] );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];
	}
}
