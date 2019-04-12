<?php
/**
 * Reports Import REST API Test
 *
 * @package WooCommerce\Tests\API
 */

/**
 * Reports Import REST API Test Class
 *
 * @package WooCommerce\Tests\API
 */
class WC_Tests_API_Reports_Import extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/import';

	/**
	 * Setup test reports products data.
	 */
	public function setUp() {
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
	 * Test the import paramaters.
	 */
	public function test_import_params() {
		global $wpdb;
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_1 = WC_Helper_Order::create_order( 1, $product );
		$order_1->set_status( 'completed' );
		$order_1->set_date_created( time() - ( 3 * DAY_IN_SECONDS ) );
		$order_1->save();
		$order_2 = WC_Helper_Order::create_order( 1, $product );
		$order_2->set_total( 100 );
		$order_2->set_status( 'completed' );
		$order_2->save();

		// Delete order stats so we can test import API.
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'scheduled-action'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_stats" );

		// Use the days param to only process orders in the last day.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_query_params( array( 'days' => '1' ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		// Run pending thrice to process batch and order.
		WC_Helper_Queue::run_all_pending();
		WC_Helper_Queue::run_all_pending();
		WC_Helper_Queue::run_all_pending();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/reports/customers' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );
		$this->assertEquals( $this->customer, $reports[0]['user_id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/reports/orders' );
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
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'scheduled-action'" );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_query_params( array( 'skip_existing' => '1' ) );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );

		// Run pending thrice to process batch and order.
		WC_Helper_Queue::run_all_pending();
		WC_Helper_Queue::run_all_pending();
		WC_Helper_Queue::run_all_pending();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/reports/customers' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );
		$this->assertEquals( 'Steve User', $reports[0]['name'] );

		$request = new WP_REST_Request( 'GET', '/wc/v4/reports/orders' );
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

		// Cancel outstanding actions.
		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/cancel' );
		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $report['status'] );
		WC_Helper_Queue::run_all_pending();

		// @todo It may be better to compare against outstanding actions
		// pulled from the import API once #1850 is complete.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/reports/orders' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );
	}
}
