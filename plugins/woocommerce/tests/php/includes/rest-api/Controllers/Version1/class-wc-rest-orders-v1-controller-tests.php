<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests relating to WC_REST_Product_Reviews_V1_Controller.
 */
class WC_REST_Orders_V1_Controller_Tests extends WC_Unit_Test_Case {
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
}
