<?php
/**
 * Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Products
 */
class WC_Tests_API_Products extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/products';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test product schema contains embed fields.
	 */
	public function test_product_schema() {
		wp_set_current_user( $this->user );
		$product    = WC_Helper_Product::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc-analytics/products/' . $product->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$properties_to_embed = array(
			'id',
			'name',
			'slug',
			'permalink',
			'images',
			'description',
			'short_description',
		);

		foreach ( $properties as $property_key => $property ) {
			if ( in_array( $property_key, $properties_to_embed, true ) ) {
				$this->assertEquals( array( 'view', 'edit', 'embed' ), $property['context'] );
			}
		}

		$this->assertArrayHasKey( 'last_order_date', $properties );

		$product->delete( true );
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
		WC_Helper_Queue::run_all_pending();

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products' );
		$request->set_param( 'low_in_stock', true );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertEquals( $product->get_id(), $data[0]['id'] );
		$this->assertEquals( $order_time, $data[0]['last_order_date'] );
	}
}
