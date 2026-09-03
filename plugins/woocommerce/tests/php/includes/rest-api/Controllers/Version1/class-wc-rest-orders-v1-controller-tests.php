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
	 * Dispatches a v1 line-item update after deleting its variation during product resolution.
	 *
	 * @param WC_Order                  $order Order to update.
	 * @param WC_Order_Item_Product     $item Line item to update.
	 * @param WC_Product_Variation      $variation Variation to delete.
	 * @param array<string, int|string> $line_item Line-item payload.
	 * @return WP_REST_Response
	 */
	private function dispatch_line_item_update_after_deleting_variation( WC_Order $order, WC_Order_Item_Product $item, WC_Product_Variation $variation, array $line_item ): WP_REST_Response {
		$delete_variation = static function ( $product, $order_item ) use ( $item, $variation ) {
			static $deleted = false;

			if ( ! $deleted && $item->get_id() === $order_item->get_id() ) {
				$deleted = true;
				wp_delete_post( $variation->get_id(), true );
			}

			return $product;
		};
		add_filter( 'woocommerce_get_product_from_item', $delete_variation, 10, 2 );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order->get_id() );
		$request->set_body_params( array( 'line_items' => array( array_merge( array( 'id' => $item->get_id() ), $line_item ) ) ) );

		try {
			return $this->server->dispatch( $request );
		} finally {
			remove_filter( 'woocommerce_get_product_from_item', $delete_variation, 10 );
		}
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
	 * @testdox Updating with the unchanged parent preserves the variation while ignoring view-context product ID filters.
	 */
	public function test_update_line_item_with_unchanged_parent_preserves_variation_id(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();
		$parent->set_name( 'Updated parent name' );
		$parent->set_tax_class( 'reduced-rate' );
		$parent->save();
		$item->set_name( 'Historical line item name' );
		$item->set_tax_class( '' );
		$item->save();
		$filtered_product     = WC_Helper_Product::create_simple_product();
		$product_id_filter    = static function ( $product_id, $order_item ) use ( $filtered_product, $item ) {
			return $item->get_id() === $order_item->get_id() ? $filtered_product->get_id() : $product_id;
		};
		$product_id_hook_name = 'woocommerce_order_item_get_product_id';
		add_filter( $product_id_hook_name, $product_id_filter, 10, 2 );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'         => $item->get_id(),
						'product_id' => $parent->get_id(),
					),
				),
			)
		);

		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_filter( $product_id_hook_name, $product_id_filter, 10 );
		}
		$this->assertSame( 200, $response->get_status(), 'The partial update should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( $variation->get_id(), $reloaded->get_variation_id(), 'The existing variation ID should be preserved.' );
		$this->assertSame( $variation->get_id(), $reloaded->get_product()->get_id(), 'The line item should continue to resolve to the variation.' );
		$this->assertSame( $parent->get_name(), $reloaded->get_name(), 'The line-item name should retain its pre-regression resynchronization behavior.' );
		$this->assertSame( $parent->get_tax_class(), $reloaded->get_tax_class(), 'The line-item tax class should retain its pre-regression resynchronization behavior.' );
	}

	/**
	 * @testdox Updating with the unchanged parent demotes the line item if its variation is deleted after loading.
	 */
	public function test_update_line_item_demotes_when_variation_is_deleted_after_loading(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();

		$response = $this->dispatch_line_item_update_after_deleting_variation(
			$order,
			$item,
			$variation,
			array(
				'product_id' => $parent->get_id(),
			)
		);

		$this->assertSame( 200, $response->get_status(), 'A variation deleted after item loading should not reject the order update.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'The deleted variation should not be restored.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product_id(), 'The line item should retain the parent product ID.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product()->get_id(), 'The line item should resolve to the parent product.' );
	}

	/**
	 * @testdox Updating with an echoed variation ID demotes the line item if the variation is deleted after loading.
	 */
	public function test_update_line_item_with_echoed_variation_id_demotes_when_variation_is_deleted_after_loading(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();

		$parent_sku = 'REST-V1-PARENT-' . wp_generate_uuid4();
		$parent->set_sku( $parent_sku );
		$parent->save();
		$variation->set_sku( '' );
		$variation->save();

		$response = $this->dispatch_line_item_update_after_deleting_variation(
			$order,
			$item,
			$variation,
			array(
				'product_id'   => $parent->get_id(),
				'variation_id' => $variation->get_id(),
				'sku'          => $parent_sku,
			)
		);

		$this->assertSame( 200, $response->get_status(), 'Echoing the stored variation ID back should not reject the order update.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'The deleted variation should not be restored.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product_id(), 'The line item should retain the parent product ID.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product()->get_id(), 'The line item should resolve to the parent product.' );
	}

	/**
	 * @testdox Updating with an explicit zero variation ID clears the variation even when SKU resolves to it.
	 */
	public function test_update_line_item_with_zero_variation_id_and_current_sku_switches_to_parent(): void {
		list( $parent, $variation, $order, $item ) = $this->create_order_with_variation_line_item();

		$parent->set_name( 'REST V1 parent product' );
		$parent->set_tax_class( '' );
		$parent->save();
		$variation_sku = 'REST-V1-VARIATION-' . wp_generate_uuid4();
		$variation->set_sku( $variation_sku );
		$variation->set_tax_class( 'reduced-rate' );
		$variation->save();
		$item->set_name( $variation->get_name() );
		$item->set_tax_class( $variation->get_tax_class() );
		$item->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'           => $item->get_id(),
						'product_id'   => $parent->get_id(),
						'variation_id' => 0,
						'sku'          => $variation_sku,
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Explicitly demoting the line item should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'An explicit zero variation ID should clear the existing variation.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product_id(), 'The line item should retain the submitted parent product ID.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product()->get_id(), 'The line item should resolve to the parent product.' );
		$this->assertSame( $parent->get_name(), $reloaded->get_name(), 'The line-item name should be synchronized with the parent product.' );
		$this->assertSame( $parent->get_tax_class(), $reloaded->get_tax_class(), 'The line-item tax class should be synchronized with the parent product.' );
	}

	/**
	 * @testdox Updating with the parent product as variation_id does not restore the existing variation.
	 */
	public function test_update_line_item_with_parent_as_variation_id_does_not_restore_variation(): void {
		list( $parent, , $order, $item ) = $this->create_order_with_variation_line_item();

		$request = new WP_REST_Request( 'PUT', '/wc/v1/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'           => $item->get_id(),
						'product_id'   => $parent->get_id(),
						'variation_id' => $parent->get_id(),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'The update should succeed.' );

		$reloaded = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( 0, $reloaded->get_variation_id(), 'A parent product ID should not be restored as a variation.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product_id(), 'The line item should retain the parent product ID.' );
		$this->assertSame( $parent->get_id(), $reloaded->get_product()->get_id(), 'The line item should resolve to the parent product.' );
	}
}
