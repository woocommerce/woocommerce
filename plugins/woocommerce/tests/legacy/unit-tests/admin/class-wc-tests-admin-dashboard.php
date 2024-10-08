<?php
/**
 * Class WC_Tests_Admin_Dashboard file.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Tests for the WC_Admin_Report class.
 */
class WC_Tests_Admin_Dashboard extends WC_Unit_Test_Case {

	/**
	 * Set up for tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Mock http request to performance endpoint.
		add_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10, 3 );
		add_filter( 'woocommerce_dashboard_status_widget_reports', array( $this, 'mock_replace_dashboard_status_widget_reports' ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10 );
		remove_filter( 'woocommerce_dashboard_status_widget_reports', array( $this, 'mock_replace_dashboard_status_widget_reports' ) );
	}

	/**
	 * Test: get_status_widget
	 */
	public function test_status_widget() {
		$this->skip_if_hpos_enabled( 'We don\'t support legacy reports on HPOS' );
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$this->expectOutputRegex( '/98,765\.00/' );

		( new WC_Admin_Dashboard() )->status_widget();

		$widget_output = $this->getActualOutput();

		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;path\=\%2Fanalytics\%2Frevenue/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;filter\=single_product/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;type\=lowstock/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;type\=outofstock/', $widget_output );
	}

	/**
	 * Test: get_status_widget with woo admin disabled.
	 */
	public function test_status_widget_with_woo_admin_disabled() {
		$this->skip_if_hpos_enabled( 'We don\'t support legacy reports on HPOS' );
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		add_filter( 'woocommerce_admin_disabled', '__return_true' );

		$this->expectOutputRegex( '/50\.00 worth in the/' );

		( new WC_Admin_Dashboard() )->status_widget();

		$widget_output = $this->getActualOutput();
		$this->assertMatchesRegularExpression( '/page\=wc-reports\&\#038\;tab\=orders\&\#038\;range\=month/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-reports\&\#038\;tab\=orders\&\#038\;report\=sales_by_product/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-reports\&\#038\;tab\=stock\&\#038\;report\=low_in_stock/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-reports\&\#038\;tab\=stock\&\#038\;report\=out_of_stock/', $widget_output );

		remove_filter( 'woocommerce_admin_disabled', '__return_true' );
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
						'value' => 98765.0,
					),
				)
			);
		} elseif ( '/wc-analytics/reports/revenue/stats' === $request->get_route() ) {
			$response = new WP_REST_Response(
				array(
					'status' => 200,
				)
			);
			$response->set_data(
				array(
					'intervals' => array(),
				)
			);
		}

		return $response;
	}

	/**
	 * Helper method to replace the data to to display in the status widget.
	 *
	 * @param array $status_widget_reports The data to display in the status widget.
	 */
	public function mock_replace_dashboard_status_widget_reports( $status_widget_reports ) {
		$report_data            = new stdClass();
		$report_data->net_sales = 123;

		$status_widget_reports['net_sales_link']      = 'admin.php?page=wc-reports&tab=orders&range=month';
		$status_widget_reports['top_seller_link']     = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=';
		$status_widget_reports['lowstock_link']       = 'admin.php?page=wc-reports&tab=stock&report=low_in_stock';
		$status_widget_reports['outofstock_link']     = 'admin.php?page=wc-reports&tab=stock&report=out_of_stock';
		$status_widget_reports['report_data']         = $report_data;
		$status_widget_reports['get_sales_sparkline'] = function () {
			return array(
				'total' => 50,
				'data'  => array(),
			);
		};

		return $status_widget_reports;
	}
}
