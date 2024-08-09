<?php
/**
 * Reports Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

/**
 * Class WC_Admin_Tests_API_Reports_Variations
 */
class WC_Admin_Tests_API_Reports_Variations extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/variations';

	/**
	 * Setup test reports products data.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product = new WC_Product_Variable();
		$this->product->set_name( 'Variable Product' );
		$this->product->save();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->product->get_id() );
		$variation->set_name( 'Test Variation' );
		$variation->set_regular_price( 25 );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$variation_report = reset( $reports );

		$this->assertEquals( $variation->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 4, $variation_report['items_sold'] );
		$this->assertEquals( 1, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );
	}

	/**
	 * Test to confirm that simple products are excluded from the variations reports by default
	 */
	public function test_simple_products_excluded_from_variations_reports_by_default() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = WC_Helper_Product::create_simple_product();

		$order = WC_Helper_Order::create_order( 1, $simple_product );
		$order->set_status( 'completed' );
		$order->set_total( 15 );
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_includes' => $simple_product->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $reports ) );
	}

	/**
	 * Test getting reports with the `variations` param.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_variations_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->product->get_id() );
		$variation->set_name( 'Test Variation' );
		$variation->set_regular_price( 25 );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_parent_id( $this->product->get_id() );
		$variation_2->set_name( 'Test Variation 2' );
		$variation_2->set_regular_price( 100 );
		$variation_2->set_attributes( array( 'color' => 'red' ) );
		$variation_2->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_includes' => $variation->get_parent_id(),
				'variations'       => $variation->get_id() . ',' . $variation_2->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$variation_report = reset( $reports );

		$this->assertEquals( $variation->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 4, $variation_report['items_sold'] );
		$this->assertEquals( 1, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );

		$variation_report = next( $reports );

		$this->assertEquals( $variation_2->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 0, $variation_report['items_sold'] );
		$this->assertEquals( 0, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 *
	 * @since 3.5.0
	 */
	public function test_reports_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'variation_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'net_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
