<?php
/**
 * Reports Coupons REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * Class WC_Admin_Tests_API_Reports_Coupons
 */
class WC_Admin_Tests_API_Reports_Coupons extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/coupons';

	/**
	 * Setup test reports products data.
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
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting basic reports.
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order without coupon.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( 'completed' );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( 'completed' );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$coupon_reports = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $coupon_reports ) );

		$this->assertEquals( $coupon_2->get_id(), $coupon_reports[0]['coupon_id'] );
		$this->assertEquals( 1 * $coupon_2_amount, $coupon_reports[0]['amount'] );
		$this->assertEquals( 1, $coupon_reports[0]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[0] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[0]['_links'] );

		$this->assertEquals( $coupon_1->get_id(), $coupon_reports[1]['coupon_id'] );
		$this->assertEquals( 2 * $coupon_1_amount, $coupon_reports[1]['amount'] );
		$this->assertEquals( 2, $coupon_reports[1]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[1] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[1]['_links'] );
	}

	/**
	 * Test getting basic reports with the `coupons` param.
	 */
	public function test_get_reports_coupons_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( 'completed' );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'coupons' => $coupon_1->get_id() . ',' . $coupon_2->get_id(),
			)
		);
		$response       = $this->server->dispatch( $request );
		$coupon_reports = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $coupon_reports ) );

		$this->assertEquals( $coupon_2->get_id(), $coupon_reports[0]['coupon_id'] );
		$this->assertEquals( 0, $coupon_reports[0]['amount'] );
		$this->assertEquals( 0, $coupon_reports[0]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[0] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[0]['_links'] );

		$this->assertEquals( $coupon_1->get_id(), $coupon_reports[1]['coupon_id'] );
		$this->assertEquals( $coupon_1_amount, $coupon_reports[1]['amount'] );
		$this->assertEquals( 1, $coupon_reports[1]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[1] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[1]['_links'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 */
	public function test_reports_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 4, count( $properties ) );
		$this->assertArrayHasKey( 'coupon_id', $properties );
		$this->assertArrayHasKey( 'amount', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
