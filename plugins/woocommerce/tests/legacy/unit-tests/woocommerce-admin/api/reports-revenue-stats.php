<?php
/**
 * Reports Revenue Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

/**
 * Class WC_Admin_Tests_API_Reports_Revenue_Stats
 */
class WC_Admin_Tests_API_Reports_Revenue_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/revenue/stats';

	/**
	 * Orders
	 *
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Setup test reports revenue data.
	 *
	 * @since 3.5.0
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

		// @todo update after report interface is done.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) ); // @todo Update results after implement report interface.
		// $this->assertEquals( array(), $reports ); // @todo Update results after implement report interface.
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
		$this->assertEquals( 12, count( $totals ) );
		$this->assertArrayHasKey( 'gross_sales', $totals );
		$this->assertArrayHasKey( 'total_sales', $totals );
		$this->assertArrayHasKey( 'net_revenue', $totals );
		$this->assertArrayHasKey( 'coupons', $totals );
		$this->assertArrayHasKey( 'coupons_count', $totals );
		$this->assertArrayHasKey( 'shipping', $totals );
		$this->assertArrayHasKey( 'taxes', $totals );
		$this->assertArrayHasKey( 'refunds', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'num_items_sold', $totals );
		$this->assertArrayHasKey( 'products', $totals );
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
		$this->assertEquals( 11, count( $subtotals ) );
		$this->assertArrayHasKey( 'gross_sales', $subtotals );
		$this->assertArrayHasKey( 'total_sales', $subtotals );
		$this->assertArrayHasKey( 'net_revenue', $subtotals );
		$this->assertArrayHasKey( 'coupons', $subtotals );
		$this->assertArrayHasKey( 'coupons_count', $subtotals );
		$this->assertArrayHasKey( 'shipping', $subtotals );
		$this->assertArrayHasKey( 'taxes', $subtotals );
		$this->assertArrayHasKey( 'refunds', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'num_items_sold', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}
}
