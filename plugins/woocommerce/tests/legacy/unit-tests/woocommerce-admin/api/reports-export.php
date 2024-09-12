<?php
/**
 * Reports Export REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

/**
 * Class WC_Admin_Tests_API_Reports_Export
 */
class WC_Admin_Tests_API_Reports_Export extends WC_REST_Unit_Test_Case {
	/**
	 * Export route.
	 *
	 * @var string
	 */
	protected $export_route = '/wc-analytics/reports/(?P<type>[a-z]+)/export';

	/**
	 * Export status route.
	 *
	 * @var string
	 */
	protected $status_route = '/wc-analytics/reports/(?P<type>[a-z]+)/export/(?P<export_id>[a-z0-9]+)/status';

	/**
	 * Setup test reports categories data.
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

		$this->assertArrayHasKey( $this->export_route, $routes );
		$this->assertArrayHasKey( $this->status_route, $routes );
	}

	/**
	 * Test requesting export without valid permissions.
	 */
	public function test_request_export_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc-analytics/reports/taxes/export' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test the export of a taxes report.
	 *
	 * @since 3.5.0
	 */
	public function test_taxes_report_export() {
		global $wpdb;
		add_filter( 'wc_tax_enabled', '__return_true' );
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Add a GA tax rate.
		$ga_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'          => '7',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'GA',
				'tax_rate_name'     => 'GA Tax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		// Add a FL tax rate.
		$fl_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'          => '6',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'FL',
				'tax_rate_name'     => 'FL Tax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		// Create a GA order.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_billing_city( 'Savannah' );
		$order->set_billing_state( 'GA' );
		$order->set_billing_postcode( '31401' );
		$order->set_status( 'completed' );
		$order->calculate_totals();
		$order->save();

		// Create a FL order.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_billing_city( 'Orlando' );
		$order->set_billing_state( 'FL' );
		$order->set_billing_postcode( '32801' );
		$order->set_status( 'completed' );
		$order->calculate_totals();
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Initiate an export of the taxes report.
		$response   = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc-analytics/reports/taxes/export' ) );
		$export     = $response->get_data();
		$status_url = $export['_links']['status'][0]['href'];

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Your report file is being generated.', $export['message'] );
		$this->assertStringMatchesFormat( '%s/wc-analytics/reports/taxes/export/%d/status', $status_url );

		// Check the initial status of the export.
		$status_url_query = array();
		parse_str( parse_url( $status_url, PHP_URL_QUERY ), $status_url_query );
		$status_route = $status_url_query['rest_route'];

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $status_route ) );
		$status   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $status['percent_complete'] );
		$this->assertStringMatchesFormat( '%s/wc-analytics/reports/taxes/export/%d/status', $status['_links']['self'][0]['href'] );

		// Run the pending export jobs.
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Check that the status shows 100% and includes a download url.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $status_route ) );
		$status   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 100, $status['percent_complete'] );
		$this->assertStringMatchesFormat( '%s/wp-admin/?action=woocommerce_admin_download_report_csv&filename=wc-taxes-report-export-%d', $status['download_url'] );
		$this->assertStringMatchesFormat( '%s/wc-analytics/reports/taxes/export/%d/status', $status['_links']['self'][0]['href'] );
		remove_filter( 'wc_tax_enabled', '__return_true' );
	}
}
