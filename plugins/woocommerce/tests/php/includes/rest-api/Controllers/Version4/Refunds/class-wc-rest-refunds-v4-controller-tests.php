<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Controller as RefundsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema;
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
	use MetaDataAssertionTrait;

	/**
	 * Endpoint instance.
	 *
	 * @var RefundsController
	 */
	private $endpoint;

	/**
	 * Administrator ID used to authenticate requests.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Product ID used by generic order fixtures.
	 *
	 * @var int
	 */
	private static $product_id;

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
	 * Create immutable class fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create(
			array(
				'user_login' => 'test_admin',
				'user_email' => 'test@example.com',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);

		self::enable_direct_product_attribute_lookup_updates();
		try {
			self::$product_id = WC_Helper_Product::create_simple_product()->get_id();
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Delete WooCommerce class fixtures through their data stores.
	 */
	public static function wpTearDownAfterClass(): void {
		self::enable_direct_product_attribute_lookup_updates();
		try {
			$product = wc_get_product( self::$product_id );
			if ( $product ) {
				$product->delete( true );
			}
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		$this->created_refunds = array();
		$this->created_orders  = array();

		self::disable_direct_product_attribute_lookup_updates();
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
		self::enable_direct_product_attribute_lookup_updates();

		// Create schema instances with dependency injection.
		$this->refund_schema = new RefundSchema();
		$preview_schema      = new RefundPreviewSchema();

		// Create utils instances.
		$collection_query = new CollectionQuery();
		$data_utils       = new DataUtils();

		$this->endpoint = new RefundsController();
		$this->endpoint->init( $this->refund_schema, $preview_schema, $collection_query, $data_utils );

		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * Helper method to create a simple order.
	 *
	 * @param array $order_data Optional order data.
	 * @return WC_Order
	 */
	private function create_test_order( array $order_data = array() ): WC_Order {
		$default_data = array(
			'status'  => OrderStatus::COMPLETED,
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

		if ( ! array_key_exists( 'line_items', $order_data ) ) {
			$default_data['line_items'] = array(
				array(
					'product_id' => self::$product_id,
					'quantity'   => 1,
				),
			);
		}

		$order_data = wp_parse_args( $order_data, $default_data );

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params( $order_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		$order                  = wc_get_order( $data['id'] );
		$this->created_orders[] = $order->get_id();

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
			'total'      => 5.00,
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
			'total'      => 5.00,
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
		$this->assertEquals( '5.00', $response_data['total'] );
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
			'total'      => 5.00,
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
		$this->assertEquals( '5.00', $response_data['total'] );
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create refund with just refund_total (should extract and split tax automatically).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'total'      => 128.00,
			'reason'     => 'Testing automatic tax extraction with multiple rates',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					// Includes 23.00 + 5.00 tax.
					'refund_total' => 128.00,
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
		$this->assertEquals( '128.00', $response_data['total'], 'Refund amount should include both taxes' );

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create refund with just refund_total (should extract compound taxes automatically).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'total'      => 115.50,
			'reason'     => 'Testing automatic compound tax extraction',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					// Includes 10.00 + 5.50 compound tax.
					'refund_total' => 115.50,
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
		$this->assertEquals( '115.50', $response_data['total'], 'Refund amount should include compound taxes' );

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create partial refund with explicit refund_tax array (legacy backward compatibility).
		// Refunding 30.00 out of 50.00 subtotal (30.00 + 6.90 + 1.50 = 38.40).
		// Don't specify amount - let it auto-calculate from line items.
		// refund_total values exclude tax; refund_tax entries are 23% and 5% of 30.00.
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'reason'     => 'Testing explicit tax array (legacy format)',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 30.00,
					'refund_tax'   => array(
						array(
							'id'           => $tax_rate_id_1,
							'refund_total' => 6.90,
						),
						array(
							'id'           => $tax_rate_id_2,
							'refund_total' => 1.50,
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
		$this->assertEquals( '38.40', $response_data['total'], 'Refund amount should include explicit taxes' );

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
	 * @testdox Refund creation accepts a tax-only explicit tax array.
	 */
	public function test_refunds_create_with_tax_only_explicit_tax_array(): void {
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 0.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 10.00,
							),
						),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), 'Tax-only explicit refunds should be accepted.' );
		$response_data = $response->get_data();
		$this->assertEquals( '10.00', $response_data['total'], 'Refund amount should include the explicit tax.' );

		$refund           = wc_get_order( $response_data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals( 0.00, (float) $refund_line_item->get_total(), 'Line item total should stay zero for a tax-only refund.' );
		$this->assertEquals( -10.00, (float) $refund_taxes['total'][ $tax_rate_id ], 'Explicit tax should be stored on the refund line.' );

		$this->created_refunds[] = $response_data['id'];
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Try to create refund with refund_total exceeding line item total (should fail).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'total'      => 500.00,
			'reason'     => 'Should fail - exceeding total',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					// Exceeds 110.00 (item total with tax) to trigger the over-refund check.
					'refund_total' => 500.00,
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		// A refund_total exceeding the line total is a well-formed but unprocessable
		// request, so it returns 422 with the same code the preview endpoint uses.
		$this->assertEquals( 422, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'code', $response_data );
		$this->assertEquals( 'refund_total_exceeds_line', $response_data['code'] );
		$this->assertStringContainsString( 'cannot exceed the line item total including tax', $response_data['message'] );

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * @testdox Refund creation rejects a request that lists the same line item more than once.
	 */
	public function test_refunds_create_duplicate_line_item_returns_error(): void {
		$order   = $this->create_test_order();
		$items   = $order->get_items( 'line_item' );
		$item    = reset( $items );
		$item_id = $item->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'refund_total' => 5.00,
					),
					array(
						'line_item_id' => $item_id,
						'refund_total' => 5.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'duplicate_line_item', $data['code'], 'Create must use the same duplicate_line_item code as the preview path.' );
		$this->assertStringContainsString( 'only once', $data['message'] );
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Try to create refund with amount LESS than line items total (should fail).
		// Line items: 110.00, but amount: 50.00 (under-refunding).
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'total'      => 50.00,
			'reason'     => 'Should fail - under-refunding',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					// Line items total is 110.00.
					'refund_total' => 110.00,
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
	 * @testdox The legacy amount field is rejected explicitly with unsupported_amount_field instead of being silently dropped.
	 *
	 * The field was renamed to total. Without this guard the REST layer would
	 * silently drop the unknown amount param and the controller would fall back
	 * to the full line-item total, refunding more than the client requested
	 * (e.g. amount: 50 with line items totaling 110 would create a 110.00 refund).
	 * An explicit null must be rejected the same way: get_param() cannot tell
	 * {"amount": null} apart from an absent field, so the guard uses has_param().
	 */
	public function test_refunds_create_rejects_legacy_amount_field(): void {
		$order      = $this->create_test_order();
		$line_items = $order->get_items( 'line_item' );
		$line_item  = reset( $line_items );

		foreach ( array( 5.00, null ) as $legacy_amount ) {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'amount'     => $legacy_amount,
					'line_items' => array(
						array(
							'line_item_id' => $line_item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 400, $response->get_status(), 'Legacy amount field must be rejected, not silently ignored' );
			$response_data = $response->get_data();
			$this->assertEquals( 'unsupported_amount_field', $response_data['code'] );
			$this->assertStringContainsString( 'total', $response_data['message'] );
		}

		// No refund may have been created.
		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 0, $order->get_refunds(), 'Rejected requests must not create a refund' );
	}

	/**
	 * @testdox An explicitly supplied zero total is rejected instead of falling back to the calculated amount.
	 *
	 * The total schema default previously made an explicit 0 indistinguishable
	 * from an omitted total: empty( 0 ) routed the request onto the calculated
	 * line-item amount and skipped the under-refund check, so a request meaning
	 * "refund nothing" refunded the full computed amount. String forms such as
	 * "0.00" are truthy and slipped past the old check the same way.
	 */
	public function test_refunds_create_rejects_explicit_zero_total(): void {
		$order      = $this->create_test_order();
		$line_items = $order->get_items( 'line_item' );
		$line_item  = reset( $line_items );

		foreach ( array( 0, '0.00' ) as $zero_total ) {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'total'      => $zero_total,
					'line_items' => array(
						array(
							'line_item_id' => $line_item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 400, $response->get_status(), 'An explicit zero total must be rejected, not treated as omitted' );
			$this->assertEquals( 'invalid_refund_amount', $response->get_data()['code'] );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 0, $order->get_refunds(), 'Rejected requests must not create a refund' );
	}

	/**
	 * Test refund creation with API refund and restock options.
	 */
	public function test_refunds_create_with_api_options(): void {
		$order       = $this->create_test_order();
		$refund_data = array(
			'order_id'    => $order->get_id(),
			'total'       => 5.00,
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
		$this->assertEquals( '5.00', $response_data['total'] );
		$this->assertEquals( 'API refund test', $response_data['reason'] );

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];
	}

	/**
	 * Test refund creation with automatic tax extraction handles rounding correctly.
	 *
	 * This tests a specific scenario where multiple tax rates with decimals can cause
	 * a 1 cent rounding error when extracting taxes from an inclusive total.
	 *
	 * Scenario: $50.00 item with California-style tax rates:
	 * - County Tax: 1% = $0.50
	 * - Special Tax: 3.25% = $1.625 → $1.63 (rounded)
	 * - State Sales Tax: 6.25% = $3.125 → $3.13 (rounded)
	 * - Total tax: $5.26
	 * - Total with tax: $55.26
	 *
	 * The bug: When extracting taxes from $55.26 using calc_inclusive_tax(),
	 * the internal precision (6DP) extraction gives different results than
	 * the 2DP rounded values that were used to build the original total.
	 * This causes the base to be calculated as $50.01 instead of $50.00.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/XXXXX
	 */
	public function test_refunds_create_with_automatic_tax_extraction_rounding_precision(): void {
		// Create three non-compound tax rates matching California-style setup.
		// Priority 1 = all applied to base price (not compound).
		$tax_rate_county = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '1.0000',
				'tax_rate_name'     => 'County Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_special = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '3.2500',
				'tax_rate_name'     => 'Special Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		$tax_rate_state = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '6.2500',
				'tax_rate_name'     => 'State Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '3',
				'tax_rate_class'    => '',
			)
		);

		// Create order with $50.00 product.
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

		// Set taxes as they would be calculated by WooCommerce (forward calculation).
		// County: 50 × 0.01 = 0.50.
		// Special: 50 × 0.0325 = 1.625 → stored/displayed as 1.63.
		// State: 50 × 0.0625 = 3.125 → stored/displayed as 3.13.
		$item->set_taxes(
			array(
				'total'    => array(
					$tax_rate_county  => '0.50',
					$tax_rate_special => '1.63',
					$tax_rate_state   => '3.13',
				),
				'subtotal' => array(
					$tax_rate_county  => '0.50',
					$tax_rate_special => '1.63',
					$tax_rate_state   => '3.13',
				),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Add tax items to the order.
		$tax_item_county = new WC_Order_Item_Tax();
		$tax_item_county->set_rate( $tax_rate_county );
		$tax_item_county->set_tax_total( 0.50 );
		$tax_item_county->save();
		$order->add_item( $tax_item_county );

		$tax_item_special = new WC_Order_Item_Tax();
		$tax_item_special->set_rate( $tax_rate_special );
		$tax_item_special->set_tax_total( 1.63 );
		$tax_item_special->save();
		$order->add_item( $tax_item_special );

		$tax_item_state = new WC_Order_Item_Tax();
		$tax_item_state->set_rate( $tax_rate_state );
		$tax_item_state->set_tax_total( 3.13 );
		$tax_item_state->save();
		$order->add_item( $tax_item_state );

		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CA' );
		$order->set_total( 55.26 );
		// 50.00 + 0.50 + 1.63 + 3.13.
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();

		// Create full refund with just refund_total (should extract taxes automatically).
		// The v4 API should extract taxes from the inclusive amount and get the correct base.
		$refund_data = array(
			'order_id'   => $order->get_id(),
			'total'      => 55.26,
			'reason'     => 'Testing rounding precision with multiple tax rates',
			'line_items' => array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					// Includes all taxes.
					'refund_total' => 55.26,
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params( $refund_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), 'Refund should be created successfully' );
		$response_data = $response->get_data();

		// Verify the refund was created.
		$this->assertIsArray( $response_data );
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $order->get_id(), $response_data['order_id'] );

		// Total refund amount should be 55.26.
		$this->assertEquals( '55.26', $response_data['total'], 'Refund amount should be 55.26' );

		// Get the actual refund object to check line item details.
		$refund           = wc_get_order( $response_data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );

		// CRITICAL: Line item total should be exactly -50.00, not -50.01.
		// This is the core of the rounding bug being tested.
		$this->assertEquals(
			-50.00,
			(float) $refund_line_item->get_total(),
			'Line item total should be exactly -50.00 (not -50.01 due to rounding error)'
		);

		// Verify extracted taxes match the original order taxes.
		$refund_taxes = $refund_line_item->get_taxes();
		$this->assertEquals(
			-0.50,
			(float) $refund_taxes['total'][ $tax_rate_county ],
			'County Tax refund should be -0.50'
		);
		$this->assertEquals(
			-1.63,
			(float) $refund_taxes['total'][ $tax_rate_special ],
			'Special Tax refund should be -1.63'
		);
		$this->assertEquals(
			-3.13,
			(float) $refund_taxes['total'][ $tax_rate_state ],
			'State Sales Tax refund should be -3.13'
		);

		// Verify the math adds up: base + all taxes = total refund amount.
		$calculated_total = abs( (float) $refund_line_item->get_total() )
			+ abs( (float) $refund_taxes['total'][ $tax_rate_county ] )
			+ abs( (float) $refund_taxes['total'][ $tax_rate_special ] )
			+ abs( (float) $refund_taxes['total'][ $tax_rate_state ] );
		$this->assertEqualsWithDelta(
			55.26,
			$calculated_total,
			0.001,
			'Sum of base and taxes should equal the refund amount'
		);

		// Track for cleanup.
		$this->created_refunds[] = $response_data['id'];

		// Clean up product.
		$product->delete( true );
	}

	/**
	 * @testdox A partial refund on a line with multiple tax IDs distributes tax per ID by stored share.
	 *
	 * Full-refund tests exercise the proportional split only at ratio 1.0 (the identity).
	 * This refunds exactly half a $55 line ($50 net + $0.50 county + $4.50 state) so the
	 * per-ID distribution and the subtotal-as-remainder math actually run with a non-trivial
	 * ratio, and asserts each stored per-tax-ID amount.
	 */
	public function test_refunds_create_partial_multi_tax_id_distributes_per_id(): void {
		$tax_rate_county = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '1.0000',
				'tax_rate_name'     => 'County',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);
		$tax_rate_state  = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '9.0000',
				'tax_rate_name'     => 'State',
				'tax_rate_priority' => '2',
				'tax_rate_order'    => '2',
			)
		);

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
		$item->set_taxes(
			array(
				'total'    => array(
					$tax_rate_county => '0.50',
					$tax_rate_state  => '4.50',
				),
				'subtotal' => array(
					$tax_rate_county => '0.50',
					$tax_rate_state  => '4.50',
				),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_totals_by_rate = array(
			$tax_rate_county => 0.50,
			$tax_rate_state  => 4.50,
		);
		foreach ( $tax_totals_by_rate as $rate_id => $tax_total ) {
			$tax_item = new WC_Order_Item_Tax();
			$tax_item->set_rate( $rate_id );
			$tax_item->set_tax_total( $tax_total );
			$tax_item->save();
			$order->add_item( $tax_item );
		}

		$order->set_billing_country( 'US' );
		$order->set_total( 55.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Refund half the tax-inclusive line: $27.50 → $25.00 net, $0.25 county, $2.25 state.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 27.50,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );
		$this->created_refunds[] = $response->get_data()['id'];

		$refund           = wc_get_order( $response->get_data()['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals( -25.00, (float) $refund_line_item->get_total(), 'Net subtotal should be half of $50.' );
		$this->assertEquals( -0.25, (float) $refund_taxes['total'][ $tax_rate_county ], 'County tax should be half of $0.50.' );
		$this->assertEquals( -2.25, (float) $refund_taxes['total'][ $tax_rate_state ], 'State tax should be half of $4.50.' );

		$product->delete( true );
	}

	/**
	 * @testdox Refund creation on a non-refundable order returns 422 order_not_refundable, matching the preview endpoint.
	 */
	public function test_refunds_create_order_not_refundable_returns_422(): void {
		$order = $this->create_test_order();
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();

		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'order_not_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox Refund creation on an already-fully-refunded line returns 422 line_item_already_refunded with a clear message.
	 *
	 * Uses a two-line order so fully refunding one line leaves the order itself
	 * refundable — otherwise a full order refund flips the order to the refunded
	 * status and the order-level guard fires first.
	 */
	public function test_refunds_create_fully_refunded_line_returns_422(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_price( 10.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_price( 20.00 );
		$product_b->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );

		$order->set_total( 30.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$first = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$first->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'refund_total' => 10.00,
					),
				),
			)
		);
		$first_response = $this->server->dispatch( $first );
		$this->assertEquals( 201, $first_response->get_status(), 'First full-line refund should succeed.' );
		$this->created_refunds[] = $first_response->get_data()['id'];

		$second = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$second->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'refund_total' => 5.00,
					),
				),
			)
		);
		$second_response = $this->server->dispatch( $second );

		$this->assertEquals( 422, $second_response->get_status() );
		$this->assertEquals( 'line_item_already_refunded', $second_response->get_data()['code'] );
		$this->assertStringContainsString( 'already been fully refunded', $second_response->get_data()['message'] );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox Refund creation rejects a zero refund_total in a mixed request and stores no refund, matching preview.
	 */
	public function test_refunds_create_zero_refund_total_in_mixed_request_returns_error(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_price( 10.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_price( 20.00 );
		$product_b->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );

		$order->set_total( 30.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'refund_total' => 0,
					),
					array(
						'line_item_id' => $item_b->get_id(),
						'refund_total' => 10.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_refund_total', $response->get_data()['code'] );
		$this->assertCount( 0, $order->get_refunds(), 'No refund should be stored when any line is rejected.' );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox Refund creation rejects a negative refund_total on a positive line item.
	 */
	public function test_refunds_create_wrong_sign_refund_total_returns_error(): void {
		$order = $this->create_test_order();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => -5.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_refund_total', $data['code'], 'Create must use the same invalid_refund_total code as the preview path for a non-positive refund_total.' );
		$this->assertStringContainsString( 'wrong sign', $data['message'] );
	}

	/**
	 * @testdox Refund creation rejects an amount exceeding the order's remaining refundable amount.
	 */
	public function test_refunds_create_amount_exceeds_order_remaining_returns_422(): void {
		// $10 order. A goodwill over-refund of the line is allowed, but the amount
		// cannot exceed what remains refundable on the order.
		$order = $this->create_test_order();
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'total'      => 15.00,
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 10.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'refund_exceeds_remaining', $response->get_data()['code'] );
	}

	/**
	 * @testdox Refund creation auto-computes refund_total from the order line item when omitted.
	 */
	public function test_refunds_create_simplified_form_no_tax(): void {
		// Two-quantity product at $10 each = $20 order total.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order     = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Refund 1 of 2 — refund_total OMITTED.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $line_item->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '10.00', $data['total'], 'Auto-computed amount should be unit price × quantity' );

		$this->created_refunds[] = $data['id'];
		$product->delete( true );
	}

	/**
	 * @testdox Refund creation with omitted refund_total extracts tax correctly.
	 */
	public function test_refunds_create_simplified_form_with_tax(): void {
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

		// Capture original option values so we can restore them in finally —
		// tearDown doesn't reset these globally and leakage breaks subsequent
		// tests that assume the default tax config.
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		try {
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

			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_tax_total( 10.00 );
			$tax_item->save();
			$order->add_item( $tax_item );

			$order->set_billing_country( 'US' );
			// calculate_totals( false ) is not reliable in the test environment when
			// taxes are involved — set_total() explicitly so get_remaining_refund_amount()
			// matches the line + tax sum.
			$order->set_total( 110.00 );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Refund the line — refund_total OMITTED.
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			$this->assertEquals( '110.00', $data['total'], 'Auto-computed amount should include tax ($100 + 10% = $110)' );

			// Verify the per-line refund_tax was extracted (not 0).
			$this->assertNotEmpty( $data['line_items'] );
			$line_item_response = $data['line_items'][0];
			$this->assertEquals( '100.00', $line_item_response['refund_total'], 'Per-line refund_total should be tax-exclusive after extraction' );
			$this->assertNotEmpty( $line_item_response['refund_tax'], 'refund_tax should be populated from extraction' );
			$this->assertEquals( '10.00', $line_item_response['refund_tax'][0]['refund_total'] );

			$this->created_refunds[] = $data['id'];
			$product->delete( true );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Simplified form (no refund_total) produces the same amount as explicit refund_total.
	 */
	public function test_refunds_create_simplified_matches_explicit(): void {
		$product = WC_Helper_Product::create_simple_product();
		// set_regular_price() persists to product meta so the REST order-creation flow
		// picks it up. set_price() only updates the in-memory derived price and gets
		// overwritten when the product is reloaded inside the order controller.
		$product->set_regular_price( 25.00 );
		$product->save();

		// Order A: refunded via simplified form.
		$order_a   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 4,
					),
				),
			)
		);
		$items_a   = $order_a->get_items();
		$item_a    = reset( $items_a );
		$request_a = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request_a->set_body_params(
			array(
				'order_id'   => $order_a->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'quantity'     => 2,
					),
				),
			)
		);
		$response_a = $this->server->dispatch( $request_a );
		$this->assertEquals( 201, $response_a->get_status() );
		$amount_a                = $response_a->get_data()['total'];
		$this->created_refunds[] = $response_a->get_data()['id'];

		// Order B: same shape but with explicit refund_total computed by the client.
		$order_b   = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 4,
					),
				),
			)
		);
		$items_b   = $order_b->get_items();
		$item_b    = reset( $items_b );
		$request_b = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request_b->set_body_params(
			array(
				'order_id'   => $order_b->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_b->get_id(),
						'quantity'     => 2,
						'refund_total' => 50.00,
					),
				),
			)
		);
		$response_b = $this->server->dispatch( $request_b );
		$this->assertEquals( 201, $response_b->get_status() );
		$amount_b                = $response_b->get_data()['total'];
		$this->created_refunds[] = $response_b->get_data()['id'];

		$this->assertEquals( $amount_b, $amount_a, 'Simplified form should produce the same amount as the explicit form.' );

		$product->delete( true );
	}

	/**
	 * @testdox Simplified form produces the same amount as explicit refund_total on a tax-inclusive store.
	 *
	 * The no-tax equivalence test is trivial — compute_line_item_refund_total
	 * returns the raw line total. The interesting regression risk is the
	 * tax round-trip: auto-compute returns a tax-inclusive value, the converter
	 * runs WC_Tax::calc_inclusive_tax to split it. A future refactor that
	 * yielded a tax-exclusive auto-computed value would diverge from the
	 * explicit-form total by the tax delta with no other test catching it.
	 */
	public function test_refunds_create_simplified_matches_explicit_tax_inclusive(): void {
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

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		// Actually exercise a tax-inclusive store (the test name now matches reality).
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			$dispatch_refund = function ( array $line_item_overrides ) use ( $tax_rate_id ): array {
				$product = WC_Helper_Product::create_simple_product();
				// Tax-inclusive store: regular_price entered with tax baked in.
				$product->set_regular_price( 110.00 );
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

				$tax_item = new \WC_Order_Item_Tax();
				$tax_item->set_rate( $tax_rate_id );
				$tax_item->set_tax_total( 10.00 );
				$tax_item->save();
				$order->add_item( $tax_item );

				$order->set_billing_country( 'US' );
				$order->set_total( 110.00 );
				$order->set_status( OrderStatus::COMPLETED );
				$order->save();
				$this->created_orders[] = $order->get_id();

				$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
				$request->set_body_params(
					array(
						'order_id'   => $order->get_id(),
						'line_items' => array(
							array_merge( array( 'line_item_id' => $item->get_id() ), $line_item_overrides ),
						),
					)
				);
				$response = $this->server->dispatch( $request );

				$this->assertEquals( 201, $response->get_status() );
				$data                    = $response->get_data();
				$this->created_refunds[] = $data['id'];
				$product->delete( true );

				return $data;
			};

			// Path A: simplified form — no refund_total, backend auto-computes via compute_line_item_refund_total.
			$data_simplified = $dispatch_refund( array( 'quantity' => 1 ) );
			// Path B: explicit form — client supplies the tax-inclusive refund_total.
			$data_explicit = $dispatch_refund(
				array(
					'quantity'     => 1,
					'refund_total' => 110.00,
				)
			);

			$this->assertEquals(
				$data_explicit['total'],
				$data_simplified['total'],
				'Tax-inclusive store: simplified and explicit forms must produce the same amount.'
			);
			$this->assertEquals( '110.00', $data_simplified['total'] );

			// The per-line refund_total / refund_tax must round-trip identically too.
			$this->assertEquals(
				$data_explicit['line_items'][0]['refund_total'],
				$data_simplified['line_items'][0]['refund_total'],
				'Per-line refund_total must match (tax-exclusive after extraction).'
			);
			$this->assertEquals( '100.00', $data_simplified['line_items'][0]['refund_total'] );

			$this->assertNotEmpty( $data_simplified['line_items'][0]['refund_tax'] );
			$this->assertEquals(
				$data_explicit['line_items'][0]['refund_tax'][0]['refund_total'],
				$data_simplified['line_items'][0]['refund_tax'][0]['refund_total'],
				'Extracted refund_tax must match between paths.'
			);
			$this->assertEquals( '10.00', $data_simplified['line_items'][0]['refund_tax'][0]['refund_total'] );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Refund creation supports mixing items with and without refund_total in the same request.
	 */
	public function test_refunds_create_mixed_with_and_without_refund_total(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_price( 10.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_price( 20.00 );
		$product_b->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );
		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );
		$order->set_total( 30.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					// Item A — no refund_total, will be auto-computed to 10.00.
					array(
						'line_item_id' => $item_a->get_id(),
						'quantity'     => 1,
					),
					// Item B — explicit refund_total (less than item total — over-refund allowed for B).
					array(
						'line_item_id' => $item_b->get_id(),
						'quantity'     => 1,
						'refund_total' => 15.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '25.00', $data['total'], 'Total = 10 (auto) + 15 (explicit) = 25' );

		$this->created_refunds[] = $data['id'];
		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox Refund creation supports all three request shapes mixed into a single create call.
	 *
	 * The controller normalises every line item through fill_missing_refund_totals
	 * and then convert_line_items_to_internal_format in one pass. An ordering bug
	 * (e.g. a stateful helper, or the converter depending on uniform shape) would
	 * only surface when all three forms coexist:
	 *  - auto-compute (quantity, no refund_total)
	 *  - explicit-with-quantity (quantity + refund_total)
	 *  - legacy explicit-no-quantity (refund_total only)
	 */
	public function test_refunds_create_three_way_mixed_shapes(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_price( 10.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_price( 20.00 );
		$product_b->save();
		$product_c = WC_Helper_Product::create_simple_product();
		$product_c->set_price( 30.00 );
		$product_c->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );
		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 20.00,
				'total'    => 20.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );
		$item_c = new WC_Order_Item_Product();
		$item_c->set_props(
			array(
				'product'  => $product_c,
				'quantity' => 1,
				'subtotal' => 30.00,
				'total'    => 30.00,
			)
		);
		$item_c->save();
		$order->add_item( $item_c );
		$order->set_total( 60.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					// Shape 1: auto-compute (quantity only).
					array(
						'line_item_id' => $item_a->get_id(),
						'quantity'     => 1,
					),
					// Shape 2: explicit-with-quantity.
					array(
						'line_item_id' => $item_b->get_id(),
						'quantity'     => 1,
						'refund_total' => 15.00,
					),
					// Shape 3: legacy explicit-no-quantity (qty=0 on the refund record).
					array(
						'line_item_id' => $item_c->get_id(),
						'refund_total' => 25.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '50.00', $data['total'], 'Total = 10 (auto) + 15 (explicit) + 25 (legacy) = 50' );
		$this->created_refunds[] = $data['id'];

		// Verify all three lines are attached and carry the expected qty.
		// WC stores refund quantities as negative (refunded amount), so the
		// request quantity N becomes -N on the refund line item.
		$refund       = wc_get_order( $data['id'] );
		$refund_items = $refund->get_items( 'line_item' );
		$this->assertCount( 3, $refund_items, 'All three line items must be attached to the refund record.' );

		$qty_by_original_id = array();
		foreach ( $refund_items as $refund_item ) {
			$qty_by_original_id[ absint( $refund_item->get_meta( '_refunded_item_id' ) ) ] = $refund_item->get_quantity();
		}
		$this->assertSame( -1, $qty_by_original_id[ $item_a->get_id() ], 'Auto-compute path records qty=-1 (refund of 1 unit).' );
		$this->assertSame( -1, $qty_by_original_id[ $item_b->get_id() ], 'Explicit-with-quantity path records qty=-1 (refund of 1 unit).' );
		$this->assertSame( 0, $qty_by_original_id[ $item_c->get_id() ], 'Legacy no-quantity path records qty=0 (no units consumed).' );

		$product_a->delete( true );
		$product_b->delete( true );
		$product_c->delete( true );
	}

	/**
	 * @testdox Simplified form preserves existing quantity validation: over-quantity is still rejected.
	 */
	public function test_refunds_create_simplified_form_rejects_over_quantity(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order     = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Request refund_total omitted AND quantity > original.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $line_item->get_id(),
						'quantity'     => 99,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 422, $response->get_status(), 'Over-quantity must still be rejected even when refund_total is auto-computed.' );
		$this->assertEquals( 'quantity_exceeds_refundable', $response->get_data()['code'], 'Create must use the same quantity_exceeds_refundable code as the preview path.' );

		$product->delete( true );
	}

	/**
	 * @testdox Simplified form auto-computes refund_total for a positive-total fee line.
	 */
	public function test_refunds_create_simplified_form_fee_line(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 7.50,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 17.50 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Refund the fee line via the simplified form.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $fee->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '7.50', $data['total'], 'Auto-computed fee refund should equal the full fee total' );

		$this->created_refunds[] = $data['id'];
		$product->delete( true );
	}

	/**
	 * @testdox Simplified form does not silently break on a negative-total fee (discount-as-fee).
	 *
	 * The compute helper preserves the sign of negative fees, but the existing
	 * validate_line_items has an item_total_with_tax < refund_total check that
	 * normally guards over-refunds. For a negative fee (e.g. total: -10), the
	 * auto-computed refund_total is also -10. The validator's comparison
	 * (-10 < -10) is false, so the request passes. The downstream
	 * wc_create_refund() call is what ultimately accepts or rejects the
	 * negative refund — assert the request reaches that point without an
	 * earlier silent failure.
	 */
	public function test_refunds_create_simplified_form_negative_fee(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -3.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 7.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $fee->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Current platform behaviour: the controller's `0 > $refund_amount`
		// guard fires for any negative auto-computed total and surfaces
		// `invalid_refund_amount`. Pin the exact response so a future change
		// (e.g. platform support for negative-fee refunds, or a different
		// rejection code) is loud rather than silent. If the platform later
		// allows negative refunds, this test will fail and force the
		// conversation about whether to update it to assert 201 + `-3.00`.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_refund_amount', $response->get_data()['code'] );

		$product->delete( true );
	}

	/**
	 * @testdox Simplified form on a tax-inclusive store ($prices_include_tax = yes) produces the correct tax-inclusive amount.
	 */
	public function test_refunds_create_simplified_form_tax_inclusive_store(): void {
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

		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			// Product price $110 entered tax-inclusive; tax-exclusive total is $100, tax is $10.
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 110.00 );
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

			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_tax_total( 10.00 );
			$tax_item->save();
			$order->add_item( $tax_item );

			$order->set_billing_country( 'US' );
			$order->set_total( 110.00 );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Refund via the simplified form (no refund_total).
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			// Tax-inclusive store: refund amount must still be $110 ($100 + $10 tax),
			// confirming the auto-compute round-trip works under prices_include_tax=yes.
			$this->assertEquals( '110.00', $data['total'], 'Tax-inclusive store: auto-computed amount must equal the tax-inclusive line total.' );

			$this->assertNotEmpty( $data['line_items'] );
			$line_item_response = $data['line_items'][0];
			$this->assertEquals( '100.00', $line_item_response['refund_total'], 'Per-line refund_total should be tax-exclusive after extraction.' );
			$this->assertNotEmpty( $line_item_response['refund_tax'] );
			$this->assertEquals( '10.00', $line_item_response['refund_tax'][0]['refund_total'] );

			$this->created_refunds[] = $data['id'];
			$product->delete( true );
		} finally {
			// Restore the option so a failing assertion above can't leak state into other tests.
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Legacy v3-style path: explicit refund_total with no quantity still works (201).
	 *
	 * The PR added a strict quantity check in validate_line_items because the
	 * new auto-compute path needs a real quantity, but that check must NOT
	 * affect requests that supply refund_total directly — those are the
	 * pre-existing v4 contract and POS clients integrating against v3 will
	 * eventually depend on it too.
	 */
	public function test_refunds_create_legacy_form_no_quantity_with_explicit_refund_total(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 100.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Step 1: legacy explicit form — refund_total provided, no quantity.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 30.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '30.00', $data['total'] );
		$this->created_refunds[] = $data['id'];

		// The line item must be attached to the refund record (B regression guard).
		// qty=0 matches v3 semantics — refund_total recorded without consuming specific units.
		$refund       = wc_get_order( $data['id'] );
		$refund_items = $refund->get_items( 'line_item' );
		$this->assertCount( 1, $refund_items, 'Refund record must have the line item attached, not an empty array.' );
		$refund_item = reset( $refund_items );
		$this->assertSame( 0, $refund_item->get_quantity(), 'qty=0 expected for legacy-no-quantity path.' );
		$this->assertEquals( -30.00, (float) $refund_item->get_total(), 'Refund line item total should be -30.00.' );

		// Step 2: the per-line remaining-amount cap gates subsequent refunds.
		// Remaining refundable on the line = 100 - 30 = 70. A simplified-form request
		// for the full 2 units would compute 100 (2 * $50), which exceeds the remaining
		// 70, so validate_line_items rejects it with refund_total_exceeds_remaining — the
		// same code (and 422 status) the preview endpoint applies, before the request
		// ever reaches wc_create_refund.
		$request2 = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request2->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 2,
					),
				),
			)
		);
		$response2 = $this->server->dispatch( $request2 );

		$this->assertEquals( 422, $response2->get_status(), 'Follow-up refund exceeding remaining dollars must be rejected.' );
		$this->assertEquals( 'refund_total_exceeds_remaining', $response2->get_data()['code'] );

		// Step 3: a follow-up that fits within remaining ($40 of $70) must succeed.
		// Guards against a regression where the first refund silently consumed
		// the full $100 budget — that would surface here as a 400, not 201.
		$request3 = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request3->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 40.00,
					),
				),
			)
		);
		$response3 = $this->server->dispatch( $request3 );

		$this->assertEquals( 201, $response3->get_status(), 'Follow-up refund within remaining dollars must succeed.' );
		$data3 = $response3->get_data();
		$this->assertEquals( '40.00', $data3['total'] );
		$this->created_refunds[] = $data3['id'];

		// And after $30 + $40 = $70 refunded, total refunded equals 70, remaining = 30.
		$order_after = wc_get_order( $order->get_id() );
		$this->assertEquals( 70.00, (float) $order_after->get_total_refunded() );
		$this->assertEquals( 30.00, (float) $order_after->get_remaining_refund_amount() );

		$product->delete( true );
	}

	/**
	 * @testdox Legacy form with api_restock=true does not restock anything (qty=0 semantics).
	 *
	 * When no quantity is provided, qty defaults to 0 on the refund line item.
	 * api_restock therefore has no units to add back to inventory. Pin that
	 * behavior so future contract changes don't silently start restocking
	 * a guessed unit count.
	 */
	public function test_refunds_create_legacy_form_api_restock_does_not_restock(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 5 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 100.00,
				'total'    => 100.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 100.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Capture stock after order completion (the order may or may not have
		// reduced stock depending on settings) — what matters is the refund
		// step does not change it.
		$stock_before_refund = wc_get_product( $product->get_id() )->get_stock_quantity();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'    => $order->get_id(),
				'api_restock' => true,
				'line_items'  => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 30.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$this->created_refunds[] = $response->get_data()['id'];

		$stock_after_refund = wc_get_product( $product->get_id() )->get_stock_quantity();
		$this->assertSame(
			$stock_before_refund,
			$stock_after_refund,
			'Legacy form (no quantity) + api_restock must not restock — qty=0 means no units to put back.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Legacy form (refund_total without quantity) on a tax-inclusive store extracts the right tax.
	 *
	 * The legacy v3-style path hits a different converter branch than the
	 * simplified form (qty defaults to 0; refund_total is supplied directly).
	 * On a tax-inclusive store this combination is the one POS clients will
	 * actually exercise after the v3 port, so a converter regression in the
	 * tax-extraction block would only surface here.
	 */
	public function test_refunds_create_legacy_form_tax_inclusive_store(): void {
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

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 110.00 );
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

			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_tax_total( 10.00 );
			$tax_item->save();
			$order->add_item( $tax_item );

			$order->set_billing_country( 'US' );
			$order->set_total( 110.00 );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Legacy form: client supplies the tax-inclusive refund_total ($110)
			// and omits quantity. Converter must extract the $10 tax portion the
			// same way it does for the simplified/explicit-with-quantity paths.
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'refund_total' => 110.00,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$data                    = $response->get_data();
			$this->created_refunds[] = $data['id'];

			$this->assertEquals( '110.00', $data['total'] );
			$this->assertNotEmpty( $data['line_items'] );
			$this->assertEquals( '100.00', $data['line_items'][0]['refund_total'], 'Per-line refund_total should be tax-exclusive after extraction.' );
			$this->assertNotEmpty( $data['line_items'][0]['refund_tax'], 'refund_tax must be extracted on the tax-inclusive legacy path.' );
			$this->assertEquals( '10.00', $data['line_items'][0]['refund_tax'][0]['refund_total'] );

			$product->delete( true );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Simplified form rejects a line_item_id that belongs to a different order with invalid_line_item.
	 */
	public function test_refunds_create_simplified_form_rejects_cross_order_line_item_id(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		// Order A: target of the refund request.
		$order_a = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// Order B: holds the line_item the client will mistakenly reference.
		$order_b       = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$order_b_items = $order_b->get_items();
		$order_b_item  = reset( $order_b_items );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order_a->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $order_b_item->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'Cross-order line_item_id must be rejected, not silently auto-computed.' );
		$data = $response->get_data();
		$this->assertEquals( 'line_item_not_found', $data['code'], 'Create must use the same line_item_not_found code as the preview path.' );

		$product->delete( true );
	}

	/**
	 * @testdox Simplified form surfaces a specific error when the source product line has zero original quantity.
	 *
	 * Without explicit handling, fill_missing_refund_totals would compute 0.0 from a divide-by-zero
	 * scenario and the request would fall through to the misleading "Refund total must be greater
	 * than zero" cascade. Lock in the clear error.
	 */
	public function test_refunds_create_simplified_form_zero_source_quantity(): void {
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'quantity' => 0,
				'subtotal' => 0,
				'total'    => 0,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_status( OrderStatus::COMPLETED );
		// A non-zero order total is needed so the order is not considered fully refunded.
		$order->set_total( 10.00 );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_line_item', $data['code'] );
		$this->assertStringContainsString( 'source quantity is zero', $data['message'] );
	}

	/**
	 * @testdox Simplified form rejects a second refund of an already-fully-refunded product line.
	 *
	 * Codex regression guard: a previous implementation compared the request's
	 * quantity to `$item->get_quantity()` (the ORIGINAL count) rather than the
	 * remaining-after-prior-refunds count. On a multi-line order, refunding
	 * item A once would leave it look unrefunded to the validator on the next
	 * request — and if item B left enough order-level dollar room, the second
	 * `{line_item_id: A, quantity: 1}` request would be accepted and refund
	 * item A twice. The fix uses compute_refunded_quantities_and_totals to
	 * cap against remaining qty.
	 */
	public function test_refunds_create_simplified_form_rejects_already_refunded_product(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_price( 50.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_price( 50.00 );
		$product_b->save();

		$order  = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );

		$order->set_total( 100.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// First simplified refund of item A — must succeed.
		$request1 = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request1->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response1 = $this->server->dispatch( $request1 );
		$this->assertEquals( 201, $response1->get_status() );
		$this->created_refunds[] = $response1->get_data()['id'];

		// Second simplified refund of item A — must be rejected by the
		// remaining-qty check (item A is fully refunded). Without the fix,
		// item B's dollar room would let this through.
		$request2 = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request2->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_a->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);
		$response2 = $this->server->dispatch( $request2 );

		$this->assertEquals( 422, $response2->get_status(), 'Over-refunding quantity is unprocessable (422), matching the preview path.' );
		$data2 = $response2->get_data();
		$this->assertEquals( 'quantity_exceeds_refundable', $data2['code'], 'Create must use the same quantity_exceeds_refundable code as the preview path.' );
		$this->assertStringContainsString( 'remaining refundable quantity', $data2['message'] );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox Simplified form rejects auto-computed refund_total combined with explicit refund_tax.
	 *
	 * Codex regression guard: with refund_total omitted and refund_tax
	 * supplied, fill_missing_refund_totals would have written a tax-inclusive
	 * refund_total (110 for a $100 item with $10 tax) and the converter would
	 * then skip tax extraction because refund_tax was already present —
	 * calculate_refund_amount summed both and emitted amount=120 (overstated
	 * by the tax). The combination is now rejected up-front.
	 */
	public function test_refunds_create_rejects_auto_compute_with_explicit_refund_tax(): void {
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

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		try {
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

			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_tax_total( 10.00 );
			$tax_item->save();
			$order->add_item( $tax_item );

			$order->set_billing_country( 'US' );
			$order->set_total( 110.00 );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Auto-compute (no refund_total) + explicit refund_tax.
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'quantity'     => 1,
							'refund_tax'   => array(
								array(
									'id'           => $tax_rate_id,
									'refund_total' => 10.00,
								),
							),
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 400, $response->get_status() );
			$data = $response->get_data();
			$this->assertEquals( 'invalid_line_item', $data['code'] );
			$this->assertStringContainsString( 'refund_tax cannot be combined', $data['message'] );

			$product->delete( true );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Scoped catch around fill_missing_refund_totals returns a 500 with invalid_refund_request when the helper throws.
	 *
	 * The catch is defensive — fill_missing_refund_totals pre-checks the
	 * invariant that compute_line_item_refund_total cares about, so the
	 * throw is unreachable from public input. Locking in the response shape
	 * here means a future refactor that broadens the catch (e.g. to
	 * \Throwable) or accidentally re-narrows fill's pre-check is caught.
	 */
	public function test_refunds_create_invariant_violation_returns_500(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
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
		$item->save();
		$order->add_item( $item );
		$order->set_total( 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Inject a DataUtils stub that throws on fill_missing_refund_totals
		// into the *DI-resolved* RefundsController instance — that's the one
		// the REST server dispatches against. $this->endpoint in setUp is a
		// separate instance and mutating it would not affect dispatch.
		$throwing_utils = $this->getMockBuilder( DataUtils::class )
			->onlyMethods( array( 'fill_missing_refund_totals' ) )
			->getMock();
		$throwing_utils->method( 'fill_missing_refund_totals' )
			->willThrowException( new \InvalidArgumentException( 'simulated invariant violation' ) );

		$container       = wc_get_container();
		$dispatch_target = $container->get( RefundsController::class );
		$dispatch_target->init( $this->refund_schema, new RefundPreviewSchema(), new CollectionQuery(), $throwing_utils );

		try {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 500, $response->get_status() );
			$data = $response->get_data();
			$this->assertEquals( 'invalid_refund_request', $data['code'] );
		} finally {
			// Restore the real data_utils on the dispatch-target controller
			// so the rest of the suite is unaffected.
			$dispatch_target->init( $this->refund_schema, new RefundPreviewSchema(), new CollectionQuery(), new DataUtils() );
			$product->delete( true );
		}
	}

	/**
	 * @testdox The 'created' hook receives a request whose line_items include the auto-computed refund_total.
	 */
	public function test_refunds_create_hook_sees_normalised_line_items(): void {
		$product = WC_Helper_Product::create_simple_product();
		// See test_refunds_create_simplified_matches_explicit for why set_regular_price()
		// is required when the order is created via the REST API.
		$product->set_regular_price( 25.00 );
		$product->save();

		$order     = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$items     = $order->get_items();
		$line_item = reset( $items );

		$captured_line_items = null;
		$hook                = 'woocommerce_rest_api_v4_refunds_created';
		$listener            = function ( $refund, $captured_request ) use ( &$captured_line_items ) {
			$captured_line_items = $captured_request['line_items'];
		};
		add_action( $hook, $listener, 10, 2 );

		try {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $line_item->get_id(),
							'quantity'     => 1,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$this->created_refunds[] = $response->get_data()['id'];

			$this->assertIsArray( $captured_line_items, 'Hook should have fired and captured the request line_items' );
			$this->assertNotEmpty( $captured_line_items );
			$this->assertArrayHasKey( 'refund_total', $captured_line_items[0], 'Hook listener should see the auto-computed refund_total on the request' );
			$this->assertSame( 25.00, (float) $captured_line_items[0]['refund_total'] );
		} finally {
			remove_action( $hook, $listener, 10 );
			$product->delete( true );
		}
	}

	/**
	 * @testdox The 'created' hook sees client-supplied refund_total unchanged on the explicit form.
	 *
	 * Guards against a future bug where the request-mirroring step (set_param
	 * after fill_missing_refund_totals) accidentally overwrites client-supplied
	 * refund_total values.
	 */
	public function test_refunds_create_hook_sees_explicit_refund_total_unchanged(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 25.00 );
		$product->save();

		$order     = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$items     = $order->get_items();
		$line_item = reset( $items );

		$captured_line_items = null;
		$hook                = 'woocommerce_rest_api_v4_refunds_created';
		$listener            = function ( $refund, $captured_request ) use ( &$captured_line_items ) {
			$captured_line_items = $captured_request['line_items'];
		};
		add_action( $hook, $listener, 10, 2 );

		try {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $line_item->get_id(),
							'quantity'     => 1,
							// Deliberately different from the auto-computed value
							// so an accidental overwrite would be detectable.
							'refund_total' => 7.50,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$this->created_refunds[] = $response->get_data()['id'];

			$this->assertIsArray( $captured_line_items );
			$this->assertArrayHasKey( 'refund_total', $captured_line_items[0] );
			$this->assertSame( 7.50, (float) $captured_line_items[0]['refund_total'], 'Hook listener must see the client-supplied refund_total unchanged.' );
		} finally {
			remove_action( $hook, $listener, 10 );
			$product->delete( true );
		}
	}

	/**
	 * @testdox Refund creation with missing quantity returns a clear invalid_line_item error (not the misleading "amount > 0" cascade).
	 */
	public function test_refunds_create_missing_quantity_returns_clear_error(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order     = $this->create_test_order(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Send a line item with NO quantity and NO refund_total — both required for auto-compute.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $line_item->get_id(),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'missing_quantity_or_refund_total', $data['code'], 'Create must use the same missing_quantity_or_refund_total code as the preview path, not cascade to invalid_refund_amount.' );
		$this->assertStringContainsString( 'positive integer', $data['message'] );

		$product->delete( true );
	}

	/**
	 * @testdox The create endpoint's auto-computed amount matches build_refund_preview's grand total for the same line items.
	 *
	 * Regression guard for create vs preview drift. Calls `build_refund_preview()`
	 * directly to capture the authoritative total, then posts the same line items
	 * (quantity only, no `refund_total`) to the create endpoint. The resulting
	 * refund amount must equal the preview total exactly. A future change that
	 * subtly diverges create's auto-compute from the preview-side calculation
	 * would fail this assertion.
	 */
	public function test_refunds_create_auto_compute_matches_build_refund_preview(): void {
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

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 200.00,
				'total'    => 200.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => 20.00 ),
				'subtotal' => array( $tax_rate_id => 20.00 ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$order->set_total( 220.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
			),
		);

		$data_utils = wc_get_container()->get( DataUtils::class );
		$preview    = $data_utils->build_refund_preview( $order, $line_items );

		$request = new \WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$create_data             = $response->get_data();
		$this->created_refunds[] = $create_data['id'];

		$this->assertEquals(
			$preview['total'],
			$create_data['total'],
			'Create amount must match build_refund_preview total exactly.'
		);

		$product->delete( true );
	}

	/**
	 * Helper to create an order containing one product line item with exact totals.
	 *
	 * Builds the order directly (no REST round-trip) so line totals that do not
	 * divide evenly by quantity can be set verbatim — the rounding tests below
	 * need unit prices like 11.00/3 that cannot be produced via a product price.
	 *
	 * @param int   $quantity    Line item quantity.
	 * @param float $subtotal    Line subtotal (tax-exclusive, pre-discount).
	 * @param float $total       Line total (tax-exclusive).
	 * @param float $order_total Order grand total.
	 * @param array $taxes       Optional map of tax_rate_id => tax amount for the line.
	 * @return array{0: WC_Order, 1: WC_Order_Item_Product} The order and its line item.
	 */
	private function create_order_with_exact_line( int $quantity, float $subtotal, float $total, float $order_total, array $taxes = array() ): array {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $quantity,
				'subtotal' => $subtotal,
				'total'    => $total,
			)
		);
		if ( ! empty( $taxes ) ) {
			$item->set_taxes(
				array(
					'total'    => $taxes,
					'subtotal' => $taxes,
				)
			);
		}
		$item->save();
		$order->add_item( $item );

		foreach ( $taxes as $rate_id => $tax_total ) {
			$tax_item = new \WC_Order_Item_Tax();
			$tax_item->set_rate( $rate_id );
			$tax_item->set_tax_total( $tax_total );
			$tax_item->save();
			$order->add_item( $tax_item );
		}

		$order->set_billing_country( 'US' );
		$order->set_total( $order_total );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$product->delete( true );

		return array( $order, $item );
	}

	/**
	 * Helper to POST a refund request for an order and return the response.
	 *
	 * @param int   $order_id   Order ID.
	 * @param array $line_items Request line items.
	 * @return WP_REST_Response
	 */
	private function dispatch_refund_request( int $order_id, array $line_items ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order_id,
				'line_items' => $line_items,
			)
		);
		return $this->server->dispatch( $request );
	}

	/**
	 * @testdox Simplified form rejects a second refund of already-fully-refunded fee and shipping lines.
	 */
	public function test_refunds_create_simplified_form_rejects_already_refunded_fee_and_shipping(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 50.00 );
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
		$item->save();
		$order->add_item( $item );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 7.50,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat rate',
				'method_id'    => 'flat_rate',
				'total'        => 5.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$order->set_total( 62.50 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		foreach ( array( $fee, $shipping ) as $non_product_item ) {
			$first_response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $non_product_item->get_id(),
						'quantity'     => 1,
					),
				)
			);
			$this->assertEquals( 201, $first_response->get_status() );
			$this->created_refunds[] = $first_response->get_data()['id'];

			$second_response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $non_product_item->get_id(),
						'quantity'     => 1,
					),
				)
			);
			if ( 201 === $second_response->get_status() ) {
				$this->created_refunds[] = $second_response->get_data()['id'];
			}

			$this->assertEquals( 422, $second_response->get_status(), 'A fee or shipping line must not be refunded twice using other order lines remaining balance.' );
			$data = $second_response->get_data();
			$this->assertEquals( 'line_item_already_refunded', $data['code'] );
			$this->assertStringContainsString( 'already been fully refunded', $data['message'] );
		}

		$product->delete( true );
	}

	/**
	 * @testdox Sequential single-unit auto-computed refunds that round above the remaining balance are rejected; an explicit refund_total recovers the remainder.
	 *
	 * A 3-quantity line totalling 11.00 has a repeating unit price (3.6667), so
	 * each single-unit refund rounds up to 3.67. After two such refunds only 3.66
	 * remains and the third auto-computed 3.67 is rejected by the remaining-amount
	 * guard. A one-shot qty-3 refund rounds once and consumes the line exactly.
	 */
	public function test_refunds_create_sequential_unit_refunds_with_repeating_unit_price(): void {
		list( $one_shot_order, $one_shot_item ) = $this->create_order_with_exact_line( 3, 11.00, 11.00, 11.00 );

		$response = $this->dispatch_refund_request(
			$one_shot_order->get_id(),
			array(
				array(
					'line_item_id' => $one_shot_item->get_id(),
					'quantity'     => 3,
				),
			)
		);
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualsWithDelta( 11.00, (float) $response->get_data()['total'], 0.001, 'One-shot qty-3 refund should equal the full line total' );
		$this->created_refunds[] = $response->get_data()['id'];

		list( $order, $item ) = $this->create_order_with_exact_line( 3, 11.00, 11.00, 11.00 );

		$unit_refund = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
			),
		);

		foreach ( array( 1, 2 ) as $refund_number ) {
			$response = $this->dispatch_refund_request( $order->get_id(), $unit_refund );
			$this->assertEquals( 201, $response->get_status(), "Single-unit refund {$refund_number} should succeed" );
			$this->assertEqualsWithDelta( 3.67, (float) $response->get_data()['total'], 0.001, 'Each single-unit refund rounds 11.00/3 up to 3.67' );
			$this->created_refunds[] = $response->get_data()['id'];
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 3.66, (float) $order->get_remaining_refund_amount(), 0.001, 'Two 3.67 refunds leave 3.66 of the 11.00 line' );

		$response = $this->dispatch_refund_request( $order->get_id(), $unit_refund );
		$this->assertEquals( 422, $response->get_status(), 'Third auto-computed 3.67 exceeds the 3.66 remaining and must be rejected' );
		$this->assertEquals( 'refund_total_exceeds_remaining', $response->get_data()['code'] );

		$response = $this->dispatch_refund_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
					'refund_total' => 3.66,
				),
			)
		);
		$this->assertEquals( 201, $response->get_status(), 'Explicit refund_total recovers the rounding remainder' );
		$this->created_refunds[] = $response->get_data()['id'];
	}

	/**
	 * @testdox Auto-compute follows the store's zero-decimal price setting and repeated unit refunds strand one currency unit.
	 */
	public function test_refunds_create_auto_compute_zero_decimal_currency(): void {
		$original_decimals = get_option( 'woocommerce_price_num_decimals', '2' );
		update_option( 'woocommerce_price_num_decimals', '0' );

		try {
			list( $order, $item ) = $this->create_order_with_exact_line( 3, 1000.00, 1000.00, 1000.00 );

			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 2,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 667.0, (float) $response->get_data()['total'], 0.001, 'Qty-2 refund of a 1000/3 line rounds to 667 at zero decimals' );
			$this->created_refunds[] = $response->get_data()['id'];

			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 1,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 333.0, (float) $response->get_data()['total'], 0.001, '667 + 333 consumes the 1000 line exactly' );
			$this->created_refunds[] = $response->get_data()['id'];

			list( $order_b, $item_b ) = $this->create_order_with_exact_line( 3, 1000.00, 1000.00, 1000.00 );

			$unit_refund = array(
				array(
					'line_item_id' => $item_b->get_id(),
					'quantity'     => 1,
				),
			);
			for ( $i = 0; $i < 3; $i++ ) {
				$response = $this->dispatch_refund_request( $order_b->get_id(), $unit_refund );
				$this->assertEquals( 201, $response->get_status() );
				$this->assertEqualsWithDelta( 333.0, (float) $response->get_data()['total'], 0.001, 'Each single-unit refund rounds 1000/3 down to 333' );
				$this->created_refunds[] = $response->get_data()['id'];
			}

			$order_b = wc_get_order( $order_b->get_id() );
			$this->assertEqualsWithDelta( 1.0, (float) $order_b->get_remaining_refund_amount(), 0.001, 'Three 333 refunds strand 1 currency unit of the 1000 line' );

			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id' => $order_b->get_id(),
					'total'    => 1,
				)
			);
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 201, $response->get_status(), 'The stranded unit stays refundable via an order-level amount' );
			$this->created_refunds[] = $response->get_data()['id'];
		} finally {
			update_option( 'woocommerce_price_num_decimals', $original_decimals );
		}
	}

	/**
	 * @testdox Multi-quantity auto-compute with a fractional tax rate reassembles net + tax and consumes the line exactly.
	 */
	public function test_refunds_create_auto_compute_multi_qty_fractional_tax_rate(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '8.8750',
				'tax_rate_name'     => 'NYC',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		try {
			// 3 × 9.99 = 29.97, tax at 8.875% = 2.66, grand total 32.63.
			list( $one_shot_order, $one_shot_item ) = $this->create_order_with_exact_line( 3, 29.97, 29.97, 32.63, array( $tax_rate_id => 2.66 ) );

			$response = $this->dispatch_refund_request(
				$one_shot_order->get_id(),
				array(
					array(
						'line_item_id' => $one_shot_item->get_id(),
						'quantity'     => 3,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 32.63, (float) $response->get_data()['total'], 0.001, 'Full-quantity refund must equal line total + line tax exactly' );
			$this->created_refunds[] = $response->get_data()['id'];

			list( $order, $item ) = $this->create_order_with_exact_line( 3, 29.97, 29.97, 32.63, array( $tax_rate_id => 2.66 ) );

			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 2,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			$this->assertEqualsWithDelta( 21.75, (float) $data['total'], 0.001, 'Qty-2 refund of the 32.63 line rounds 21.7533 to 21.75' );
			$this->created_refunds[] = $data['id'];

			$line    = $data['line_items'][0];
			$tax_sum = 0.0;
			foreach ( $line['refund_tax'] as $tax ) {
				$tax_sum += (float) $tax['refund_total'];
			}
			$this->assertEqualsWithDelta( 21.75, (float) $line['refund_total'] + $tax_sum, 0.001, 'Extracted net + tax must reassemble the tax-inclusive amount' );

			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 1,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 10.88, (float) $response->get_data()['total'], 0.001, '21.75 + 10.88 consumes the 32.63 line exactly' );
			$this->created_refunds[] = $response->get_data()['id'];

			$order = wc_get_order( $order->get_id() );
			$this->assertEqualsWithDelta( 0.0, (float) $order->get_remaining_refund_amount(), 0.001, 'Qty-2 then qty-1 must leave nothing unrefunded' );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Multi-quantity auto-compute on a tax-inclusive store returns quantity × displayed price.
	 */
	public function test_refunds_create_auto_compute_multi_qty_prices_include_tax(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '23.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			// 5 × 9.99 displayed (tax-inclusive) = 49.95; stored net 40.61 + 9.34 tax.
			list( $order, $item ) = $this->create_order_with_exact_line( 5, 40.61, 40.61, 49.95, array( $tax_rate_id => 9.34 ) );

			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 5,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 49.95, (float) $response->get_data()['total'], 0.001, 'Full-quantity refund must equal 5 × the displayed 9.99 price' );
			$this->created_refunds[] = $response->get_data()['id'];

			list( $order_b, $item_b ) = $this->create_order_with_exact_line( 5, 40.61, 40.61, 49.95, array( $tax_rate_id => 9.34 ) );

			$response = $this->dispatch_refund_request(
				$order_b->get_id(),
				array(
					array(
						'line_item_id' => $item_b->get_id(),
						'quantity'     => 2,
					),
				)
			);
			$this->assertEquals( 201, $response->get_status() );
			$this->assertEqualsWithDelta( 19.98, (float) $response->get_data()['total'], 0.001, 'Qty-2 refund must equal 2 × the displayed 9.99 price' );
			$this->created_refunds[] = $response->get_data()['id'];
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Multi-quantity auto-compute with compound taxes matches the preview total and reassembles per-rate taxes.
	 */
	public function test_refunds_create_auto_compute_multi_qty_compound_taxes(): void {
		$gst_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'GST',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		$pst_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '7.0000',
				'tax_rate_name'     => 'PST',
				'tax_rate_priority' => '2',
				'tax_rate_compound' => '1',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => '',
			)
		);

		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		try {
			// 3 × 50 = 150; GST 5% = 7.50; compound PST 7% of 157.50 = 11.03; grand total 168.53.
			list( $order, $item ) = $this->create_order_with_exact_line(
				3,
				150.00,
				150.00,
				168.53,
				array(
					$gst_rate_id => 7.50,
					$pst_rate_id => 11.03,
				)
			);

			$line_items = array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
				),
			);

			$data_utils = wc_get_container()->get( DataUtils::class );
			$preview    = $data_utils->build_refund_preview( $order, $line_items );

			$response = $this->dispatch_refund_request( $order->get_id(), $line_items );
			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			$this->assertEqualsWithDelta( 112.35, (float) $data['total'], 0.001, 'Qty-2 refund of the 168.53 line rounds 112.3533 to 112.35' );
			$this->assertEquals( $preview['total'], $data['total'], 'Create amount must match build_refund_preview total exactly' );
			$this->created_refunds[] = $data['id'];

			$line    = $data['line_items'][0];
			$tax_sum = 0.0;
			foreach ( $line['refund_tax'] as $tax ) {
				$tax_sum += (float) $tax['refund_total'];
			}
			$this->assertEqualsWithDelta( 112.35, (float) $line['refund_total'] + $tax_sum, 0.001, 'Net + per-rate taxes must reassemble the tax-inclusive amount' );
		} finally {
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox Auto-compute uses the discounted line total, not the pre-discount subtotal.
	 */
	public function test_refunds_create_auto_compute_uses_discounted_total(): void {
		// 3 × 10.00 with a 10% discount applied: subtotal 30.00, total 27.00.
		list( $order, $item ) = $this->create_order_with_exact_line( 3, 30.00, 27.00, 27.00 );

		$response = $this->dispatch_refund_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 2,
				),
			)
		);
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualsWithDelta( 18.00, (float) $response->get_data()['total'], 0.001, 'Qty-2 refund must use the discounted 9.00 unit price, not the 10.00 subtotal price' );
		$this->created_refunds[] = $response->get_data()['id'];
	}

	/**
	 * @testdox Fee and shipping lines reject quantity above 1 and auto-compute their full total at quantity 1.
	 */
	public function test_refunds_create_fee_and_shipping_quantity_is_informational(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 10.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 7.50,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat rate',
				'method_id'    => 'flat_rate',
				'total'        => 5.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$order->set_total( 22.50 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		foreach ( array( $fee, $shipping ) as $non_product_item ) {
			$response = $this->dispatch_refund_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $non_product_item->get_id(),
						'quantity'     => 3,
					),
				)
			);
			$this->assertEquals( 400, $response->get_status(), 'Fee/shipping items have quantity 1; requesting 3 must be rejected' );
			$this->assertEquals( 'invalid_quantity', $response->get_data()['code'] );
		}

		$response = $this->dispatch_refund_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
			)
		);
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEqualsWithDelta( 12.50, (float) $response->get_data()['total'], 0.001, 'Quantity 1 refunds each non-product line at its full total, exactly once' );
		$this->created_refunds[] = $response->get_data()['id'];

		$product->delete( true );
	}

	/**
	 * @testdox Creating a V4 refund with incomplete meta_data entries does not cause errors.
	 */
	public function test_create_refund_meta_data_with_incomplete_entries(): void {
		$order = $this->create_test_order();

		$request = new \WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'  => $order->get_id(),
				'total'     => 1.00,
				'meta_data' => $this->get_incomplete_meta_data_input(),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$refund                  = wc_get_order( $response->get_data()['id'] );
		$this->created_refunds[] = $refund->get_id();

		$this->assert_incomplete_meta_data_handled_correctly( $refund );
	}

	/**
	 * @testdox Create splits a single-tax partial refund_total into the stored net total and tax, independently of the preview path.
	 */
	public function test_refunds_create_partial_amount_single_tax_split_stored(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Refund $55 of the $110 tax-inclusive line → $50 net, $5 tax.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 55.00,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data                    = $response->get_data();
		$this->created_refunds[] = $data['id'];

		$this->assertEquals( '55.00', $data['total'], 'Refund amount must equal the tax-inclusive partial total.' );

		$refund           = wc_get_order( $data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals( -50.00, (float) $refund_line_item->get_total(), 'Stored net total should be half of $100.' );
		$this->assertEquals( -5.00, (float) $refund_taxes['total'][ $tax_rate_id ], 'Stored tax should be half of $10.' );

		$product->delete( true );
	}

	/**
	 * @testdox Create uses an explicit refund_total over quantity for the money split on a multi-quantity line, while storing the requested quantity.
	 */
	public function test_refunds_create_partial_amount_multi_quantity_line(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 3,
				'subtotal' => 30.00,
				'total'    => 30.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => 3.00 ),
				'subtotal' => array( $tax_rate_id => 3.00 ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( 3.00 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_billing_country( 'US' );
		$order->set_total( 33.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Refund half the $33 tax-inclusive line ($16.50) while passing quantity 3:
		// the money split follows refund_total ($15 net, $1.50 tax), quantity is stored as-is.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'quantity'     => 3,
						'refund_total' => 16.50,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data                    = $response->get_data();
		$this->created_refunds[] = $data['id'];

		$this->assertEquals( '16.50', $data['total'], 'Refund amount must follow refund_total, not the full quantity total.' );

		$refund           = wc_get_order( $data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals( -15.00, (float) $refund_line_item->get_total(), 'Stored net total should be half of $30.' );
		$this->assertEquals( -1.50, (float) $refund_taxes['total'][ $tax_rate_id ], 'Stored tax should be half of $3.' );
		// Refund line items store quantity as a negative, mirroring the negative totals.
		$this->assertEquals( -3, $refund_line_item->get_quantity(), 'The requested quantity is stored even when refund_total drives the amount.' );

		$product->delete( true );
	}

	/**
	 * @testdox Create rounds a partial refund_total split to a zero-decimal currency precision.
	 */
	public function test_refunds_create_partial_amount_zero_decimal_currency(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

		// Force whole-number currency precision deterministically. update_option on
		// woocommerce_price_num_decimals does not reliably propagate to
		// wc_get_price_decimals() within a single request in the test environment.
		$zero_decimals = static function () {
			return 0;
		};
		add_filter( 'wc_get_price_decimals', $zero_decimals );

		try {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 1000.00 );
			$product->set_tax_status( 'taxable' );
			$product->save();

			$order = wc_create_order();
			$item  = new WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => 1000.00,
					'total'    => 1000.00,
				)
			);
			$item->set_taxes(
				array(
					'total'    => array( $tax_rate_id => 100.00 ),
					'subtotal' => array( $tax_rate_id => 100.00 ),
				)
			);
			$item->save();
			$order->add_item( $item );

			$tax_item = new WC_Order_Item_Tax();
			$tax_item->set_rate( $tax_rate_id );
			$tax_item->set_tax_total( 100.00 );
			$tax_item->save();
			$order->add_item( $tax_item );

			$order->set_billing_country( 'US' );
			$order->set_total( 1100.00 );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Refund 549 of the 1100 tax-inclusive line. The stored ratio gives
			// 549 * 100/1100 = 49.909..., which rounds to 50 at zero decimals (it would
			// be 49.91 at two), and the net subtotal becomes 549 - 50 = 499. Asserting
			// these whole numbers proves the split honours the currency precision.
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'refund_total' => 549,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$data                    = $response->get_data();
			$this->created_refunds[] = $data['id'];

			$refund           = wc_get_order( $data['id'] );
			$refund_items     = $refund->get_items( 'line_item' );
			$refund_line_item = reset( $refund_items );
			$refund_taxes     = $refund_line_item->get_taxes();

			$this->assertEquals( -499.0, (float) $refund_line_item->get_total(), 'Net subtotal rounds to a whole number at zero decimals.' );
			$this->assertEquals( -50.0, (float) $refund_taxes['total'][ $tax_rate_id ], 'Tax rounds to a whole number at zero decimals.' );
			$this->assertEquals( 549.0, (float) $data['total'], 'Net + tax must reconstitute the requested amount.' );

			$product->delete( true );
		} finally {
			remove_filter( 'wc_get_price_decimals', $zero_decimals );
		}
	}

	/**
	 * @testdox Create absorbs the rounding remainder into the net subtotal when a partial refund_total does not split into clean cents.
	 */
	public function test_refunds_create_partial_amount_rounding_remainder(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '15.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10.00 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => 1.50 ),
				'subtotal' => array( $tax_rate_id => 1.50 ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( 1.50 );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_billing_country( 'US' );
		$order->set_total( 11.50 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Refund $3.33 of the $11.50 tax-inclusive line. tax = round(3.33 * 1.50 / 11.50, 2) = 0.43,
		// and the net subtotal absorbs the remainder: 3.33 - 0.43 = 2.90, so subtotal + tax == amount.
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item->get_id(),
						'refund_total' => 3.33,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data                    = $response->get_data();
		$this->created_refunds[] = $data['id'];

		$refund           = wc_get_order( $data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals( -0.43, (float) $refund_taxes['total'][ $tax_rate_id ], 'Tax rounds to the nearest cent.' );
		$this->assertEquals( -2.90, (float) $refund_line_item->get_total(), 'Net subtotal absorbs the rounding remainder.' );
		$this->assertEquals( '3.33', $data['total'], 'Net + tax must reconstitute the requested amount to the cent.' );

		$product->delete( true );
	}

	/**
	 * @testdox Create rejects a partial refund_total that exceeds the remaining refundable amount on a fee line.
	 */
	public function test_refunds_create_partial_amount_fee_exceeds_remaining_returns_422(): void {
		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Handling',
				'total' => 20.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 20.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// First refund $15 of the $20 fee, leaving $5 remaining.
		$first_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$first_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $fee->get_id(),
						'refund_total' => 15.00,
					),
				),
			)
		);
		$first_response = $this->server->dispatch( $first_request );
		$this->assertEquals( 201, $first_response->get_status(), 'First partial fee refund should succeed.' );
		$this->created_refunds[] = $first_response->get_data()['id'];

		// Second refund of $10 exceeds the $5 remaining on the fee line.
		$second_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$second_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $fee->get_id(),
						'refund_total' => 10.00,
					),
				),
			)
		);
		$second_response = $this->server->dispatch( $second_request );

		$this->assertEquals( 422, $second_response->get_status() );
		$this->assertEquals( 'refund_total_exceeds_remaining', $second_response->get_data()['code'] );
		$this->assertStringContainsString( 'remaining refundable amount', $second_response->get_data()['message'] );
	}

	/**
	 * @testdox Create splits a partial refund_total by the stored ratio under a tax-inclusive store, without double-extracting tax.
	 */
	public function test_refunds_create_partial_amount_tax_inclusive_store(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 110.00 );
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
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$this->created_orders[] = $order->get_id();

			// Refund $55 of the $110 tax-inclusive line → $50 net, $5 tax, same split as a tax-exclusive store.
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'refund_total' => 55.00,
						),
					),
				)
			);
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 201, $response->get_status() );
			$data                    = $response->get_data();
			$this->created_refunds[] = $data['id'];

			$this->assertEquals( '55.00', $data['total'], 'Tax-inclusive store: amount equals the requested tax-inclusive partial.' );

			$refund           = wc_get_order( $data['id'] );
			$refund_items     = $refund->get_items( 'line_item' );
			$refund_line_item = reset( $refund_items );
			$refund_taxes     = $refund_line_item->get_taxes();

			$this->assertEquals( -50.00, (float) $refund_line_item->get_total(), 'Stored net total should be half of $100.' );
			$this->assertEquals( -5.00, (float) $refund_taxes['total'][ $tax_rate_id ], 'Stored tax should be half of $10, not re-extracted.' );

			$product->delete( true );
		} finally {
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		}
	}

	/**
	 * @testdox The created refund's per-line split matches build_refund_preview's breakdown for the explicit refund_total form.
	 *
	 * Guards the headline guarantee of the partial-amount feature: a previewed
	 * refund_total matches the created refund to the cent, including the per-tax
	 * split, not just the grand total.
	 */
	public function test_refunds_create_partial_amount_matches_build_refund_preview_breakdown(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		// Partial-amount form: refund $55 of the $110 tax-inclusive line.
		$line_items = array(
			array(
				'line_item_id' => $item->get_id(),
				'refund_total' => 55.00,
			),
		);

		$data_utils = wc_get_container()->get( DataUtils::class );
		$preview    = $data_utils->build_refund_preview( $order, $line_items );

		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data                    = $response->get_data();
		$this->created_refunds[] = $data['id'];

		$preview_item = $preview['breakdown']['products']['items'][0];

		// Grand-total parity.
		$this->assertEquals( $preview['total'], $data['total'], 'Create amount must match the preview total.' );

		// Per-line split parity: stored refund values are negative, the preview is positive.
		$refund           = wc_get_order( $data['id'] );
		$refund_items     = $refund->get_items( 'line_item' );
		$refund_line_item = reset( $refund_items );
		$refund_taxes     = $refund_line_item->get_taxes();

		$this->assertEquals(
			-1 * (float) $preview_item['subtotal'],
			(float) $refund_line_item->get_total(),
			'Stored net total must match the previewed subtotal.'
		);
		$this->assertEquals(
			-1 * (float) $preview_item['tax'],
			(float) $refund_taxes['total'][ $tax_rate_id ],
			'Stored tax must match the previewed tax.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Two sequential partial-amount refunds that sum to the line total both succeed; a further refund is rejected.
	 *
	 * Exercises the rounded remaining-amount boundary in validate_line_items for a
	 * product line: the second refund hits the exact remaining amount and must be
	 * accepted, after which the order is fully refunded.
	 */
	public function test_refunds_create_partial_amount_product_sequential_to_full(): void {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_order'    => '1',
			)
		);

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$refund_line = function ( float $amount ) use ( $order, $item ) {
			$request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
			$request->set_body_params(
				array(
					'order_id'   => $order->get_id(),
					'line_items' => array(
						array(
							'line_item_id' => $item->get_id(),
							'refund_total' => $amount,
						),
					),
				)
			);
			return $this->server->dispatch( $request );
		};

		// First refund: $40 of the $110 tax-inclusive line.
		$first = $refund_line( 40.00 );
		$this->assertEquals( 201, $first->get_status(), 'First partial refund should succeed.' );
		$this->created_refunds[] = $first->get_data()['id'];

		// Second refund: exactly the $70 remaining — the boundary must be accepted.
		$second = $refund_line( 70.00 );
		$this->assertEquals( 201, $second->get_status(), 'Second partial refund at the exact remaining amount should succeed.' );
		$this->created_refunds[] = $second->get_data()['id'];

		$this->assertEquals(
			0.0,
			(float) wc_get_order( $order->get_id() )->get_remaining_refund_amount(),
			'Order should be fully refunded after both partials.'
		);

		// A further refund on the now fully-refunded order is rejected.
		$third = $refund_line( 0.01 );
		$this->assertEquals( 422, $third->get_status() );
		$this->assertEquals( 'order_not_refundable', $third->get_data()['code'] );

		$product->delete( true );
	}
}
