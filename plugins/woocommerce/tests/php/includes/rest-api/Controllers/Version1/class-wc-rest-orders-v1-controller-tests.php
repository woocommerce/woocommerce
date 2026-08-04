<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests relating to WC_REST_Orders_V1_Controller.
 */
class WC_REST_Orders_V1_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Stores the previous HPOS state.
	 * @var bool
	 */
	private static $hpos_prev_state;

	/**
	 * Prepare for running the tests. Disables HPOS, as it's not compatible with this test.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Store the previous HPOS state.
		self::$hpos_prev_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot_feature_and_usage( false );
	}

	/**
	 * Restore previous state (including HPOS) after all tests have run.
	 */
	public static function tearDownAfterClass(): void {
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );
		parent::tearDownAfterClass();
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user(
			$this->factory->user->create(
				array( 'role' => 'administrator' )
			)
		);
	}

	/**
	 * Creates an order containing a variation line item.
	 *
	 * @return array{WC_Product_Variable, WC_Product_Variation, WC_Order, WC_Order_Item_Product}
	 */
	private function create_order_with_variation_line_item(): array {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$order     = new WC_Order();
		$item      = new WC_Order_Item_Product();
		$item->set_product( $variation );
		$item->set_quantity( 1 );
		$item->set_total( 10 );
		$order->add_item( $item );
		$order->save();

		return array( $parent, $variation, $order, $item );
	}

	/**
	 * Dispatches a v1 line-item update.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $line_item Line-item payload.
	 * @return WP_REST_Response
	 */
	private function dispatch_line_item_update( int $order_id, array $line_item ): WP_REST_Response {
		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order_id );
		$request->set_body_params( array( 'line_items' => array( $line_item ) ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Test that an order can be fetched via REST API V1 without triggering a deprecation notice.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/39006
	 *
	 * @return void
	 */
	public function test_orders_with_coupons_can_be_fetched(): void {
		// Create an order and apply a coupon.
		CouponHelper::create_coupon( 'savebig' );
		$coupon_line_item = new WC_Order_Item_Coupon();
		$coupon_line_item->set_code( 'savebig' );

		$order = OrderHelper::create_order();
		$order->add_item( $coupon_line_item );
		$order->save();

		$api_request  = new WP_REST_Request( 'GET', '/wc/v1/orders/' . $order->get_id() );
		$controller   = new WC_REST_Orders_V1_Controller();
		$api_response = $controller->prepare_item_for_response( get_post( $order->get_id() ), $api_request );

		$this->assertInstanceOf(
			WP_REST_Response::class,
			$api_response,
			'API response was generated successfully, and without triggering a deprecation notice.'
		);
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

		$request = new WP_REST_Request( 'POST', '/wc/v1/orders' );
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

		$request = new WP_REST_Request( 'POST', '/wc/v1/orders' );
		$request->set_body_params( array( 'customer_id' => $customer_b->get_id() ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status(), 'The order was not created, as the specified customer does not belong to the blog.' );
		$this->assertEquals( 'woocommerce_rest_invalid_customer_id', $response->get_data()['code'], 'The returned error indicates the customer ID was invalid.' );

		// Repeat the last test, except by performing an order update (instead of order creation).
		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order_id );
		$request->set_body_params( array( 'customer_id' => $customer_b->get_id() ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status(), 'The order was not updated, as the specified customer does not belong to the blog.' );
		$this->assertEquals( 'woocommerce_rest_invalid_customer_id', $response->get_data()['code'], 'The returned error indicates the customer ID was invalid.' );
	}

	/**
	 * @testdox Updating a variation line item with its unchanged parent product_id preserves an omitted variation_id.
	 */
	public function test_update_line_item_with_unchanged_parent_preserves_omitted_variation_id(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();
		$item->set_name( 'Historical variation line item' );
		$item->save();

		$response = $this->dispatch_line_item_update(
			$order->get_id(),
			array(
				'id'         => $item->get_id(),
				'product_id' => $parent->get_id(),
			)
		);
		$this->assertSame( 200, $response->get_status(), 'The partial update should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( $variation->get_id(), $reloaded->get_variation_id(), 'Omitting variation_id should preserve the existing variation.' );
		$this->assertSame( $variation->get_id(), $reloaded->get_product()->get_id(), 'get_product() should continue to resolve to the variation.' );
		$this->assertSame( 'Historical variation line item', $reloaded->get_name(), 'Omitted item fields should retain their historical values.' );
	}

	/**
	 * @testdox Round-tripping a variation with its parent's inherited SKU preserves the variation ID.
	 */
	public function test_round_trip_line_item_with_inherited_parent_sku_preserves_variation_id(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();
		$parent_sku                                = 'REST-V1-PARENT-' . wp_generate_uuid4();
		$parent->set_sku( $parent_sku );
		$parent->save();
		$variation->set_sku( '' );
		$variation->save();

		$get_request  = new WP_REST_Request( 'GET', '/wc/v1/orders/' . $order->get_id() );
		$get_response = $this->server->dispatch( $get_request );
		$this->assertSame( 200, $get_response->get_status(), 'Fetching the order should succeed.' );

		$round_trip_item = $get_response->get_data()['line_items'][0];
		$this->assertSame( $parent_sku, $round_trip_item['sku'], 'The response should expose the SKU inherited from the parent.' );

		$put_response = $this->dispatch_line_item_update( $order->get_id(), $round_trip_item );
		$this->assertSame( 200, $put_response->get_status(), 'Round-tripping the line item should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( $variation->get_id(), $reloaded->get_variation_id(), 'The inherited parent SKU should not demote the variation.' );
		$this->assertSame( $variation->get_id(), $reloaded->get_product()->get_id(), 'The line item should continue to resolve to the variation.' );
	}

	/**
	 * @testdox Updating a variation line item honors a filtered SKU alias that resolves to its parent.
	 * @dataProvider provide_parent_sku_shapes
	 *
	 * @param bool $use_malformed_parent_sku Whether to inject malformed raw parent SKU data.
	 */
	public function test_update_line_item_honors_filtered_parent_sku_alias( bool $use_malformed_parent_sku ): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();

		$alias_sku = 'REST-V1-ALIAS-' . wp_generate_uuid4();
		$parent->set_sku( 'REST-V1-PARENT-' . wp_generate_uuid4() );
		$parent->save();
		$variation->set_sku( '' );
		$variation->save();

		$product_filter = null;
		if ( $use_malformed_parent_sku ) {
			$malformed_variation = new WC_Product_Variation( $variation->get_id() );
			$parent_data         = $malformed_variation->get_parent_data();
			$parent_data['sku']  = new stdClass();
			$malformed_variation->set_parent_data( $parent_data );
			$this->assertInstanceOf( stdClass::class, $malformed_variation->get_parent_data()['sku'], 'Precondition: the variation should expose malformed raw parent SKU data.' );
			$product_filter = static function ( $product ) use ( $malformed_variation ) {
				return $product instanceof WC_Product_Variation && $product->get_id() === $malformed_variation->get_id() ? $malformed_variation : $product;
			};
			add_filter( 'woocommerce_order_item_product', $product_filter );
		}

		$sku_filter = static function ( $product_id, $posted_sku ) use ( $alias_sku, $parent ) {
			return $alias_sku === $posted_sku ? $parent->get_id() : $product_id;
		};
		add_filter( 'woocommerce_get_product_id_by_sku', $sku_filter, 10, 2 );

		try {
			$response = $this->dispatch_line_item_update(
				$order->get_id(),
				array(
					'id'         => $item->get_id(),
					'product_id' => $parent->get_id(),
					'sku'        => $alias_sku,
				)
			);
		} finally {
			remove_filter( 'woocommerce_get_product_id_by_sku', $sku_filter, 10 );
			if ( $product_filter ) {
				remove_filter( 'woocommerce_order_item_product', $product_filter );
			}
		}
		$this->assertSame( 200, $response->get_status(), 'The filtered parent alias update should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'A filtered alias resolving to the parent should clear variation_id.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product()->get_id(), 'The line item should use the filtered parent result.' );
	}

	/**
	 * Provides raw parent SKU shapes for filtered alias updates.
	 *
	 * @return array<string, array{bool}>
	 */
	public function provide_parent_sku_shapes(): array {
		return array(
			'ordinary parent SKU'  => array( false ),
			'malformed parent SKU' => array( true ),
		);
	}

	/**
	 * @testdox Creating with unresolved SKU zero falls back while updates honor resolvable SKU zero.
	 */
	public function test_zero_sku_product_reference_behavior(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();
		$posted_product                            = WC_Helper_Product::create_simple_product();
		$sku_product                               = WC_Helper_Product::create_simple_product();
		$create_request                            = new WP_REST_Request( 'POST', '/wc/v1/orders' );
		$create_line_item                          = array(
			'product_id' => $posted_product->get_id(),
			'sku'        => '0',
		);
		$create_request->set_body_params( array( 'line_items' => array( $create_line_item ) ) );

		$create_response = $this->server->dispatch( $create_request );
		$this->assertSame( $posted_product->get_id(), $create_response->get_data()['line_items'][0]['product_id'], 'An unresolved SKU zero should fall back to the posted product ID on create.' );

		$sku_product->set_sku( '0' );
		$sku_product->save();

		$response = $this->dispatch_line_item_update(
			$order->get_id(),
			array(
				'id'         => $item->get_id(),
				'product_id' => $parent->get_id(),
				'sku'        => '0',
			)
		);
		$this->assertSame( 200, $response->get_status(), 'Switching by SKU zero should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'Switching by SKU zero should clear variation_id.' );
		$this->assertSame( $sku_product->get_id(), $reloaded->get_product()->get_id(), 'The line item should use the SKU lookup result.' );
	}

	/**
	 * @testdox Updating a variation line item to a sibling variation honors the explicit variation_id.
	 */
	public function test_update_line_item_switches_to_sibling_variation(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();
		$sibling                                   = wc_get_product( $parent->get_children()[1] );

		$response = $this->dispatch_line_item_update(
			$order->get_id(),
			array(
				'id'           => $item->get_id(),
				'product_id'   => $parent->get_id(),
				'variation_id' => $sibling->get_id(),
			)
		);
		$this->assertSame( 200, $response->get_status(), 'Switching to a sibling variation should succeed.' );

		$response_item = $response->get_data()['line_items'][0];
		$reloaded      = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( $sibling->get_id(), $reloaded->get_variation_id(), 'The sibling variation ID should be persisted.' );
		$this->assertSame( $sibling->get_id(), $reloaded->get_product()->get_id(), 'The line item should resolve to the sibling variation.' );
		$this->assertSame( $sibling->get_id(), $response_item['variation_id'], 'The response should identify the sibling variation.' );
	}
}
