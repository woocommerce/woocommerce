<?php
/**
 * Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API ProductsLowInStock
 */
class WC_Admin_Tests_API_ProductsLowInStock extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/products/low-in-stock';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test low stock query.
	 */
	public function test_low_stock() {
		wp_set_current_user( $this->user );

		// Create a product with stock management.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_low_stock_amount( 2 );
		$product->set_stock_quantity( 5 );
		$product->save();

		// Order enough of the product to trigger low stock status.
		$order_time = '2020-11-24T10:00:00';
		$order      = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_date_created( $order_time );
		$order->save();

		// Sync analytics data (used for last order date).
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products/low-in-stock' );
		$request->set_param( 'low_in_stock', true );
		$request->set_param( 'status', 'publish' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertEquals( $product->get_id(), $data[0]['id'] );
		$this->assertEquals( $order_time, $data[0]['last_order_date'] );
	}
}
