<?php
/**
 * Class WC_Tests_Admin_Report_V2 file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

/**
 * Tests for the WC_Admin_Report class.
 */
class WC_Tests_Admin_Report_V2 extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 *
	 */
	public static function setUpBeforeClass() {
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report-v2.php';
	}

	/**
	 * Set up for tests.
	 */
	public function setUp() {
		parent::setUp();

		// Mock http request to performance endpoint.
		add_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		remove_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10 );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_performance_data() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$report = new WC_Admin_Report_V2();

		$data = $report->get_performance_data();
		$this->assertEquals( $data->net_sales, 33.00 );
	}

	/**
	 * Helper method to mock rest_do_request method.
	 *
	 * @param false           $response Request arguments.
	 * @param WP_REST_Server  $rest_server rest server class.
	 * @param WP_REST_Request $request incoming request.
	 *
	 * @return WP_REST_Response|false mocked response or false to let WP perform a regular request.
	 */
	public function mock_rest_responses( $response, $rest_server, $request ) {
		if ( '/wc-analytics/reports/performance-indicators' === $request->get_route() ) {
			$response = new WP_REST_Response(
				array(
					'status' => 200,
				)
			);
			$response->set_data(
				array(
					array(
						'chart' => 'net_revenue',
						'value' => 33.0,
					),
				)
			);
		}

		return $response;
	}
}
