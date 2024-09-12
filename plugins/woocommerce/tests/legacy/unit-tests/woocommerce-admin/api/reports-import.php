<?php
/**
 * Reports Import REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\ReportsSync;

/**
 * Reports Import REST API Test Class
 *
 * @package WooCommerce\Admin\Tests\API
 */
class WC_Admin_Tests_API_Reports_Import extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/import';

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

		$this->customer = $this->factory->user->create(
			array(
				'first_name' => 'Steve',
				'last_name'  => 'User',
				'role'       => 'customer',
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
	 * Asserts the report item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_report_item_schema( $schema ) {
		$this->assertArrayHasKey( 'status', $schema );
		$this->assertArrayHasKey( 'message', $schema );
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

		$this->assertCount( 2, $properties );
		$this->assert_report_item_schema( $properties );
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test the import parameters.
	 */
	public function test_import_params() {
		global $wpdb;
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_1 = WC_Helper_Order::create_order( $this->customer, $product );
		$order_1->set_status( 'completed' );
		$order_1->set_date_created( time() - ( 3 * DAY_IN_SECONDS ) );
		$order_1->save();
		$order_2 = WC_Helper_Order::create_order( $this->customer, $product );
		$order_2->set_total( 100 );
		$order_2->set_status( 'completed' );
		$order_2->save();

		// Delete order stats so we can test import API.
		WC_Helper_Queue::cancel_all_pending();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_stats" );

		// Use the days param to only process orders in the last day.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_query_params( array( 'days' => '1' ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/customers' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $this->customer, $reports[0]['user_id'] );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/orders' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $order_2->get_id(), $reports[0]['order_id'] );

		// Use the skip existing params to skip processing customers/orders.
		// Compare against order status to make sure previously imported order was skipped.
		$order_2->set_status( 'processing' );
		$order_2->save();

		// Compare against name to make sure previously imported customer was skipped.
		wp_update_user(
			array(
				'ID'         => $this->customer,
				'first_name' => 'Changed',
			)
		);

		// Delete scheduled actions to avoid default order processing.
		WC_Helper_Queue::cancel_all_pending();

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_query_params( array( 'skip_existing' => '1' ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/customers' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( 'Steve User', $reports[0]['name'] );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/reports/orders' );
		$request->set_query_params( array( 'per_page' => 5 ) );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );
		$this->assertEquals( 'completed', $reports[0]['status'] );
	}

	/**
	 * Test cancelling import actions.
	 */
	public function test_cancel_import() {
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_date_created( time() - ( 3 * DAY_IN_SECONDS ) );
		$order->save();

		// Verify there are actions to cancel.
		$pending_actions = WC_Helper_Queue::get_all_pending();
		$pending_hooks   = array_map(
			function ( $action ) {
				return $action->get_hook();
			},
			$pending_actions
		);
		$this->assertContains( 'wc-admin_import_orders', $pending_hooks );

		// Cancel outstanding actions.
		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/cancel' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		// Verify there are no pending actions.
		$pending_actions = WC_Helper_Queue::get_all_pending();
		$pending_hooks   = array_map(
			function ( $action ) {
				return $action->get_hook();
			},
			$pending_actions
		);
		$this->assertNotContains( 'wc-admin_import_orders', $pending_hooks );
	}

	/**
	 * Test import deletion.
	 */
	public function test_delete_stats() {
		global $wpdb;
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		for ( $i = 0; $i < 25; $i++ ) {
			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( 'completed' );
			$order->save();
		}

		// Check that stats exist before deleting.
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/reports/orders' );
		$request->set_query_params( array( 'per_page' => 25 ) );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 25, $reports );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/reports/customers' );
		$request->set_query_params( array( 'per_page' => 25 ) );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );

		// Delete all stats.
		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/delete' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Check that stats have been deleted.
		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/orders' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );

		$request  = new WP_REST_Request( 'GET', '/wc-analytics/reports/customers' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );
	}

	/**
	 * Test import status and totals query.
	 */
	public function test_import_status() {
		// Delete any pending actions that weren't fully run.
		ReportsSync::clear_queued_actions();

		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Create 5 completed orders.
		for ( $i = 0; $i < 5; $i++ ) {
			$order = WC_Helper_Order::create_order( $this->customer, $product );
			$order->set_status( 'completed' );
			$order->set_date_created( time() - ( ( $i + 1 ) * DAY_IN_SECONDS ) );
			$order->save();
		}

		// Trash one test order - excludes it from totals.
		$order->set_status( 'trash' );
		$order->save();

		// Create 1 draft order - to be excluded from totals.
		$order = WC_Helper_Order::create_order( $this->customer, $product );
		$order->set_date_created( time() - ( 5 * DAY_IN_SECONDS ) );
		$order->set_status( 'auto-draft' );
		$order->save();

		// Test totals and total params.
		$request    = new WP_REST_Request( 'GET', $this->endpoint . '/totals' );
		$response   = $this->server->dispatch( $request );
		$report     = $response->get_data();
		$user_query = new WP_User_Query(
			array(
				'fields'   => 'ID',
				'number'   => 1,
				'role__in' => array( 'customer' ),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, $report['orders'] );
		$this->assertEquals( $user_query->get_total(), $report['customers'] );

		// Test totals with days param.
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/totals' );
		$request->set_query_params( array( 'days' => 2 ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $report['orders'] );

		// Cancel any pending imports, import, and check import status.
		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/cancel' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		$request  = new WP_REST_Request( 'POST', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/status' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $report['is_importing'] );
		$this->assertEquals( 0, $report['customers']['imported'] );
		$this->assertEquals( 1, $report['customers']['total'] );
		$this->assertEquals( 0, $report['orders']['imported'] );
		$this->assertEquals( 4, $report['orders']['total'] );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Test import status after processing.
		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/status' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( false, $report['is_importing'] );
		$this->assertEquals( 1, $report['customers']['imported'] );
		$this->assertEquals( 1, $report['customers']['total'] );
		$this->assertEquals( 4, $report['orders']['imported'] );
		$this->assertEquals( 4, $report['orders']['total'] );

		// Test totals with skip existing param.
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/totals' );
		$request->set_query_params( array( 'skip_existing' => 1 ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 0, $report['customers'] );
		$this->assertEquals( 0, $report['orders'] );
	}
}
