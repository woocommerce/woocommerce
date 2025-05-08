<?php
/**
 * Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStatus;

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_date_created( $order_time );
		$order->save();

		// Sync analytics data (used for last order date).
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products/low-in-stock' );
		$request->set_param( 'low_in_stock', true );
		$request->set_param( 'status', ProductStatus::PUBLISH );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertEquals( $product->get_id(), $data[0]['id'] );
		$this->assertEquals( $order_time, $data[0]['last_order_date'] );
	}

	/**
	 * Test multiple products with custom low stock amounts.
	 */
	public function test_multiple_products_custom_low_stock() {
		wp_set_current_user( $this->user );

		// Create three products with different stock quantities.
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_manage_stock( true );
		$product1->set_low_stock_amount( 5 );
		$product1->set_stock_quantity( 4 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_manage_stock( true );
		$product2->set_low_stock_amount( 3 );
		$product2->set_stock_quantity( 2 );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_manage_stock( true );
		$product3->set_low_stock_amount( 10 );
		$product3->set_stock_quantity( 15 );
		$product3->save();

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products/count-low-in-stock' );
		$request->set_param( 'low_in_stock', true );
		$request->set_param( 'status', ProductStatus::PUBLISH );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $data['total'] );
	}

	/**
	 * Test multiple products with global low stock setting.
	 */
	public function test_multiple_products_global_low_stock() {
		wp_set_current_user( $this->user );

		// Set global low stock amount.
		update_option( 'woocommerce_notify_low_stock_amount', 5 );

		// Create three products without custom low stock amounts.
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_manage_stock( true );
		$product1->set_stock_quantity( 4 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_manage_stock( true );
		$product2->set_stock_quantity( 2 );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_manage_stock( true );
		$product3->set_stock_quantity( 6 );
		$product3->save();

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products/count-low-in-stock' );
		$request->set_param( 'low_in_stock', true );
		$request->set_param( 'status', ProductStatus::PUBLISH );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $data['total'] );
	}
}
