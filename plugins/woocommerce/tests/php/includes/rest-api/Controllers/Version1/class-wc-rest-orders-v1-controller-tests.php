<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests relating to WC_REST_Product_Reviews_V1_Controller.
 */
class WC_REST_Orders_V1_Controller_Tests extends WC_REST_Unit_Test_Case {
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
	 * Test that an order can be fetched via REST API V1 without triggering a deprecation notice.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/39006
	 *
	 * @return void
	 */
	public function test_orders_with_coupons_can_be_fetched(): void {
		// Create a legacy order (APIv1 does not work with HPOS).
		if ( get_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) !== 'no' ) {
			$this->markTestSkipped( 'This test only runs when HPOS is not enabled.' );
		}

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
}
