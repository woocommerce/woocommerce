<?php
/**
 * Reports Coupons Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * Class WC_Admin_Tests_API_Reports_Coupons_Stats
 */
class WC_Admin_Tests_API_Reports_Coupons_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/coupons/stats';

	/**
	 * Setup test reports products stats data.
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
	 * Test getting reports.
	 */
	public function test_get_reports() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
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

		$time = time();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( 'completed' );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->set_date_created( $time );
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( 'completed' );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->set_date_created( $time );
		$order_2c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval' => 'day',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$expected_reports = array(
			'totals'    => array(
				'amount'        => 4.0,
				'coupons_count' => 2,
				'orders_count'  => 2,
				'segments'      => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d', $time ),
					'date_start'     => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_start_gmt' => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_end'       => gmdate( 'Y-m-d 23:59:59', $time ),
					'date_end_gmt'   => gmdate( 'Y-m-d 23:59:59', $time ),
					'subtotals'      => (object) array(
						'amount'        => 4.0,
						'coupons_count' => 2,
						'orders_count'  => 2,
						'segments'      => array(),
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_reports, $reports );
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 4, count( $totals ) );
		$this->assertArrayHasKey( 'amount', $totals );
		$this->assertArrayHasKey( 'coupons_count', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'segments', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 4, count( $subtotals ) );
		$this->assertArrayHasKey( 'amount', $subtotals );
		$this->assertArrayHasKey( 'coupons_count', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );

	}
}
