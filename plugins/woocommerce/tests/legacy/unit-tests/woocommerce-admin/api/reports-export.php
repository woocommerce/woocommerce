<?php
/**
 * Reports Export REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;

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
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->calculate_totals();
		$order->save();

		// Create a FL order.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_billing_city( 'Orlando' );
		$order->set_billing_state( 'FL' );
		$order->set_billing_postcode( '32801' );
		$order->set_status( OrderStatus::COMPLETED );
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

	/**
	 * @testdox Should reject an export whose report_args.orderby is outside the report's enum.
	 */
	public function test_export_rejects_orderby_outside_enum() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc-analytics/reports/orders/export' );
		$request->set_body_params( array( 'report_args' => array( 'orderby' => 'date,(SELECT SLEEP(3))' ) ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status(), 'An orderby outside the enum must be rejected.' );
		$this->assertEquals( 'rest_invalid_param', $data['code'], 'The rejection must be a parameter validation error.' );
		$this->assertArrayHasKey( 'report_args', $data['data']['params'], 'The report_args parameter must be flagged as invalid.' );
		$this->assertStringContainsString( 'orderby', $data['data']['params']['report_args'], 'The failure must point at the orderby key.' );
	}

	/**
	 * @testdox Should accept an export whose report_args.orderby is in the report's enum.
	 */
	public function test_export_accepts_orderby_in_enum() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc-analytics/reports/orders/export' );
		$request->set_body_params( array( 'report_args' => array( 'orderby' => 'net_total' ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'An orderby in the enum must be accepted.' );
	}

	/**
	 * @testdox Should fail closed and reject an export for an unresolvable report type.
	 */
	public function test_export_rejects_unresolvable_report_type() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc-analytics/reports/bogus/export' );
		$request->set_body_params( array( 'report_args' => array( 'orderby' => 'date' ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'An unresolvable report type must be rejected.' );
	}

	/**
	 * @testdox Should enforce the report schema when queue_report_export is called directly.
	 */
	public function test_direct_queue_report_export_enforces_schema() {
		WC_Helper_Reports::reset_stats_dbs();

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$valid_rows = \Automattic\WooCommerce\Admin\ReportExporter::queue_report_export(
			'export-valid',
			'orders',
			array( 'orderby' => 'net_total' )
		);
		$this->assertGreaterThan( 0, $valid_rows, 'A valid orderby should export the available rows.' );

		$injected_rows = \Automattic\WooCommerce\Admin\ReportExporter::queue_report_export(
			'export-injected',
			'orders',
			array( 'orderby' => 'date,(SELECT SLEEP(3))' )
		);
		$this->assertEquals( 0, $injected_rows, 'An orderby outside the enum must not be exported, even off the REST route.' );
	}

	/**
	 * @testdox Should still export when the batch limit filter exceeds the schema per_page maximum.
	 */
	public function test_export_allows_batch_limit_above_schema_maximum() {
		WC_Helper_Reports::reset_stats_dbs();

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// The report schema caps per_page at 100; the batch size is ours and may exceed it.
		add_filter( 'woocommerce_admin_orders_report_export_batch_limit', array( $this, 'return_large_batch_limit' ) );

		$rows = \Automattic\WooCommerce\Admin\ReportExporter::queue_report_export(
			'export-big-batch',
			'orders',
			array( 'orderby' => 'net_total' )
		);

		remove_filter( 'woocommerce_admin_orders_report_export_batch_limit', array( $this, 'return_large_batch_limit' ) );

		$this->assertGreaterThan( 0, $rows, 'A batch limit above the schema per_page maximum must still export.' );
	}

	/**
	 * Batch limit filter callback returning a value above the schema per_page maximum.
	 *
	 * @return int
	 */
	public function return_large_batch_limit() {
		return 500;
	}
}
