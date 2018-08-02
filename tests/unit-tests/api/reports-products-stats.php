<?php
/**
 * Reports Products Stats REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Reports_Products_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/reports/products/stats';

	/**
	 * Setup test reports products stats data.
	 *
	 * @since 3.5.0
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
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time = time();
		$stats_data = array(
			'num_orders'            => 1,
			'num_items_sold'        => 2,
			'orders_gross_total'    => 20.0,
			'orders_coupon_total'   => 0.0,
			'orders_refund_total'   => 0.0,
			'orders_tax_total'      => 0.0,
			'orders_shipping_total' => 5.0,
			'orders_net_total'      => 15.0,
		);
		WC_Reports_Orders_Data_Store::update( $time, $stats_data );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array(
			'before' => date( 'Y-m-d H:00:00', $time + DAY_IN_SECONDS ),
			'after' => date( 'Y-m-d H:00:00', $time - DAY_IN_SECONDS ),
			'interval' => 'day',
		) );

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$expected_reports = array(
			'totals' => array(
				'num_items_sold' => 2,
				'gross_revenue' => 20.0,
				'orders_count' => 1,
			),
			'intervals' => array(
				array(
					'time_interval' => date( 'Y-m-d', $time ),
					'num_items_sold' => 2,
					'gross_revenue' => 20.0,
					'orders_count' => 1,
					'date_start' => date( 'Y-m-d 00:00:00', $time ),
					'date_end' => date( 'Y-m-d 00:00:00', $time + DAY_IN_SECONDS ),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_reports, $reports );
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 3, count( $totals ) );
		$this->assertArrayHasKey( 'gross_revenue', $totals );
		$this->assertArrayHasKey( 'items_sold', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 3, count( $subtotals ) );
		$this->assertArrayHasKey( 'gross_revenue', $totals );
		$this->assertArrayHasKey( 'items_sold', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
	}
}
