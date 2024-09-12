<?php
/**
 * Reports Customers REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;

/**
 * Reports Customers REST API Test Class
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group run-in-separate-process
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */
class WC_Admin_Tests_API_Reports_Customers extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/customers';

	/**
	 * Setup test reports products data.
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
	 * Asserts the report item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_report_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'user_id', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'username', $schema );
		$this->assertArrayHasKey( 'country', $schema );
		$this->assertArrayHasKey( 'city', $schema );
		$this->assertArrayHasKey( 'state', $schema );
		$this->assertArrayHasKey( 'postcode', $schema );
		$this->assertArrayHasKey( 'date_registered', $schema );
		$this->assertArrayHasKey( 'date_registered_gmt', $schema );
		$this->assertArrayHasKey( 'date_last_active', $schema );
		$this->assertArrayHasKey( 'date_last_active_gmt', $schema );
		$this->assertArrayHasKey( 'orders_count', $schema );
		$this->assertArrayHasKey( 'total_spend', $schema );
		$this->assertArrayHasKey( 'avg_order_value', $schema );
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

		$this->assertCount( 15, $properties );
		$this->assert_report_item_schema( $properties );
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
	 * Test calling update_registered_customer() with a bad user id.
	 *
	 * @since 3.5.0
	 */
	public function test_update_registered_customer_with_bad_user_id() {
		$result = CustomersDataStore::update_registered_customer( 2 );
		$this->assertFalse( $result );
	}

	/**
	 * Test creation of various user roles
	 *
	 * @since 3.5.0
	 */
	public function test_user_creation() {
		wp_set_current_user( $this->user );
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'testadmin',
				'user_pass'  => null,
				'role'       => 'administrator',
			)
		);

		// Admin user without orders should not be shown.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'per_page' => 10 ) );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );

		// Creating an order with admin should return the admin.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( $admin_id, $product );
		$order->set_status( 'processing' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'per_page' => 10 ) );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $admin_id, $reports[0]['user_id'] );

		// Creating a customer should show up regardless of orders.
		$customer = WC_Helper_Customer::create_customer( 'customer', 'password', 'customer@example.com' );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'per_page' => 10,
				'order'    => 'asc',
				'orderby'  => 'username',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );
		$this->assertEquals( $customer->get_id(), $reports[0]['user_id'] );
		$this->assertEquals( $admin_id, $reports[1]['user_id'] );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		global $wpdb;

		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$test_customers = array();

		$customer_names = array( 'Alice', 'Betty', 'Catherine', 'Dan', 'Eric', 'Fred', 'Greg', 'Henry', 'Ivan', 'Justin' );

		// Create 10 test customers.
		for ( $i = 1; $i <= 10; $i++ ) {
			$name     = $customer_names[ $i - 1 ];
			$email    = 'customer+' . strtolower( $name ) . '@example.com';
			$customer = WC_Helper_Customer::create_customer( "customer{$i}", 'password', $email );
			$customer->set_first_name( $name );
			$customer->save();
			$test_customers[] = $customer;
		}

		// Create a test product for use in an order.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Place an order for the first test customer.
		$order = WC_Helper_Order::create_order( $test_customers[0]->get_id(), $product );
		$order->set_status( 'processing' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'per_page' => 5,
				'order'    => 'asc',
				'orderby'  => 'username',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $reports );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertEquals( 10, $headers['X-WP-Total'] );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
		$this->assertEquals( $test_customers[0]->get_id(), $reports[0]['user_id'] );
		$this->assertEquals( 1, $reports[0]['orders_count'] );
		$this->assertEquals( 100, $reports[0]['total_spend'] );
		$this->assert_report_item_schema( $reports[0] );

		// Test name and last_order parameters.
		$request->set_query_params(
			array(
				'search'           => 'Alice',
				'last_order_after' => gmdate( 'Y-m-d' ) . 'T00:00:00Z',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );

		$this->assertEquals( $test_customers[0]->get_id(), $reports[0]['user_id'] );
		$this->assertEquals( 1, $reports[0]['orders_count'] );
		$this->assertEquals( 100, $reports[0]['total_spend'] );

		$customer_id = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup WHERE user_id = %d",
				$reports[0]['user_id']
			)
		);

		// Test customers param.
		$request->set_query_params(
			array(
				'customers' => $customer_id,
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $test_customers[0]->get_id(), $reports[0]['user_id'] );
	}

	/**
	 * @testdox Test the search and searchby parameters.
	 */
	public function test_customer_search() {
		wp_set_current_user( $this->user );

		$customer = WC_Helper_Customer::create_customer( 'onlyatest', 'password', 'onlyatest@example.com' );
		$customer->set_first_name( 'Jay' );
		$customer->set_last_name( 'Ramathorn' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'jaytest', 'password', 'justatest@example.com' );
		$customer->set_first_name( 'Jason' );
		$customer->set_last_name( 'Roto' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'womack2001', 'password', 'mac@jaybird.local' );
		$customer->set_first_name( 'Steve' );
		$customer->set_last_name( 'Letme' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'sotero', 'password', 'bananas@example.com' );
		$customer->set_first_name( 'Carl' );
		$customer->set_last_name( 'Foster' );
		$customer->save();

		$order = WC_Helper_Order::create_order( 0 ); // Order with guest customer (no account).
		$order->set_billing_email( 'rjayfarva@ramrod.local' );
		$order->set_billing_last_name( 'Arjay' );
		$order->save();

		// Ensure order customer data is synced to lookup table.
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$query_params = array(
			'force_cache_refresh' => true,
			'order'               => 'asc',
			'orderby'             => 'name',
			'order_before'        => '',
			'order_after'         => '',
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $reports ); // No search string, so all customers should return.

		$query_params['search']   = 'Jay';
		$query_params['searchby'] = 'name';
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );

		$query_params['searchby'] = 'username';
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );

		$query_params['searchby'] = 'email';
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 2, $reports );

		$query_params['searchby'] = 'all';
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 4, $reports );

		$query_params['search'] = 'Not A Customer';
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );
	}

	/**
	 * Test getting reports with filter_empty parameter
	 */
	public function test_get_reports_with_filter_empty() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Test empty reports.
		$request = new WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );

		// Test filter_empty param by name.
		$request->set_query_params(
			array(
				'filter_empty' => array( 'name' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 0, $reports );

		$customer = WC_Helper_Customer::create_customer( 'customer_1', 'password', 'customer_1@example.com' );
		$customer->set_billing_city( '' );
		$customer->set_first_name( 'customer_andrei_1' );
		$customer->save();
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request->set_query_params(
			array(
				'filter_empty' => array( 'city', 'email' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 0, $reports );

		$request->set_query_params(
			array(
				'filter_empty' => array( 'email' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 1, $reports );

		// Test filter_empty param by email and search.
		$request->set_query_params(
			array(
				'filter_empty' => array( 'email' ),
				'search'       => 'andrei',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 1, $reports );

		// Test filter_empty param by state and postcode non empty.
		$customer = WC_Helper_Customer::create_customer( 'customer_2', 'password', 'customer_2@example.com' );
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request->set_query_params(
			array(
				'filter_empty' => array( 'email', 'state' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 2, $reports );

		// Test filter_empty param by country.
		$request->set_query_params(
			array(
				'filter_empty' => array( 'country' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 2, $reports );

		// Test filter_empty param by city.
		$request->set_query_params(
			array(
				'filter_empty' => array( 'city' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 1, $reports );

		// Test filter_empty param by state.
		$request->set_query_params(
			array(
				'filter_empty' => array( 'state' ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$this->assertCount( 2, $reports );
	}

	/**
	 * Test customer user profile name priority.
	 */
	public function test_customer_user_profile_name() {
		wp_set_current_user( $this->user );

		$customer = wp_insert_user(
			array(
				'first_name' => 'Tyrion',
				'last_name'  => 'Lanister',
				'user_login' => 'daenerys',
				'user_pass'  => null,
				'role'       => 'customer',
			)
		);

		$order = WC_Helper_Order::create_order( $customer );
		$order->set_billing_first_name( 'Jon' );
		$order->set_billing_last_name( 'Snow' );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( 'Tyrion Lanister', $reports[0]['name'] );
	}


	/**
	 * Test customer billing name priority.
	 */
	public function test_customer_billing_name() {
		wp_set_current_user( $this->user );

		// Test shipping name and empty billing name on a guest account.
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_first_name( 'Jon' );
		$order->set_billing_last_name( 'Snow' );
		$order->set_shipping_first_name( 'IgnoredFirstName' );
		$order->set_shipping_last_name( 'IgnoredLastName' );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( 'Jon Snow', $reports[0]['name'] );
	}

	/**
	 * Test customer shipping name priority.
	 */
	public function test_customer_shipping_name() {
		wp_set_current_user( $this->user );

		// Test shipping name and empty billing name on a guest account.
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_first_name( '' );
		$order->set_billing_last_name( '' );
		$order->set_shipping_first_name( 'Daenerys' );
		$order->set_shipping_last_name( 'Targaryen' );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( 'Daenerys Targaryen', $reports[0]['name'] );
	}

	/**
	 * Test that bad order params don't cause PHP errors when retrieving customers.
	 */
	public function test_customer_retrieval_from_order_bad_order() {
		$this->assertFalse( CustomersDataStore::get_existing_customer_id_from_order( false ) );
		$this->assertFalse( CustomersDataStore::get_or_create_customer_from_order( false ) );
	}

	/**
	 * Test user deletion.
	 */
	public function test_user_deletion() {
		wp_set_current_user( $this->user );

		// Creating a customer should show up regardless of orders.
		$customer = WC_Helper_Customer::create_customer( 'deleteme', 'password', 'deleteme@example.com' );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'per_page' => 1,
			)
		);
		$response  = $this->server->dispatch( $request );
		$customers = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $customers );
		$this->assertEquals( $customer->get_id(), $customers[0]['user_id'] );

		// Delete the user associated with the customer.
		wp_delete_user( $customer->get_id() );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Verify they are gone.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'per_page'  => 1,
				'customers' => array( $customer->get_id() ),
			)
		);
		$response  = $this->server->dispatch( $request );
		$customers = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $customers );
	}

	/**
	 * Test sync order update with customer info.
	 */
	public function test_sync_order_customer() {
		wp_set_current_user( $this->user );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// update order info.
		$order->set_billing_city( 'Random' );
		$order->set_billing_state( 'FL' );
		$order->set_billing_postcode( '54321' );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$result = CustomersDataStore::sync_order_customer( $order->get_id() );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$this->assertNotEquals( -1, $result );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( 'Random' === $reports[0]['city'] );
		$this->assertTrue( 'FL' === $reports[0]['state'] );
		$this->assertTrue( '54321' === $reports[0]['postcode'] );
	}

	/**
	 * Test sync order update with customer latest order info.
	 */
	public function test_sync_latest_order_customer() {
		wp_set_current_user( $this->user );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();
		$order2 = WC_Helper_Order::create_order( 0 );
		$order2->set_status( 'completed' );
		$order2->set_total( 100 );
		$order2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_id  = CustomersDataStore::get_existing_customer_id_from_order( $order );
		$customer2_id = CustomersDataStore::get_existing_customer_id_from_order( $order2 );
		$this->assertEquals( $customer_id, $customer2_id );
		// update order info.
		$order->set_billing_city( 'Random' );
		$order->set_billing_state( 'FL' );
		$order->set_billing_postcode( '54321' );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$result = CustomersDataStore::sync_order_customer( $order->get_id() );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Didn't update anything.
		$this->assertTrue( -1 === $result );
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$customer_index = array_search( 'admin@example.org', array_column( $reports, 'email' ), true );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotEquals( 'Random', $reports[ $customer_index ]['city'] );
		$this->assertNotEquals( 'FL', $reports[ $customer_index ]['state'] );
		$this->assertNotEquals( '54321', $reports[ $customer_index ]['postcode'] );
	}

	/**
	 * Test sync order update with customer latest order info.
	 */
	public function test_sync_latest_order_customer_with_multiple_customers() {
		wp_set_current_user( $this->user );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();
		$order2 = WC_Helper_Order::create_order( 0 );
		$order2->set_status( 'completed' );
		$order2->set_total( 100 );
		$order2->save();
		$order3 = WC_Helper_Order::create_order( 0 );
		$order3->set_status( 'completed' );
		$order3->set_total( 100 );
		$order3->set_billing_email( 'different@example.org' );
		$order3->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_id  = CustomersDataStore::get_existing_customer_id_from_order( $order );
		$customer2_id = CustomersDataStore::get_existing_customer_id_from_order( $order2 );
		$customer3_id = CustomersDataStore::get_existing_customer_id_from_order( $order3 );
		$this->assertEquals( $customer_id, $customer2_id );
		$this->assertNotEquals( $customer_id, $customer3_id );
		// update order info.
		$order3->set_billing_city( 'Random' );
		$order3->set_billing_state( 'FL' );
		$order3->set_billing_postcode( '54321' );
		$order3->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$result = CustomersDataStore::sync_order_customer( $order3->get_id() );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Didn't update anything.
		$this->assertNotEquals( -1, $result );
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$first_customer_index  = array_search( 'admin@example.org', array_column( $reports, 'email' ), true );
		$second_customer_index = array_search( 'different@example.org', array_column( $reports, 'email' ), true );
		// First customer.
		$this->assertEquals( 'admin@example.org', $reports[ $first_customer_index ]['email'] );
		$this->assertNotEquals( 'Random', $reports[ $first_customer_index ]['city'] );
		$this->assertNotEquals( 'FL', $reports[ $first_customer_index ]['state'] );
		$this->assertNotEquals( '54321', $reports[ $first_customer_index ]['postcode'] );
		// Latest customer that should be updated.
		$this->assertEquals( 'different@example.org', $reports[ $second_customer_index ]['email'] );
		$this->assertEquals( 'Random', $reports[ $second_customer_index ]['city'] );
		$this->assertEquals( 'FL', $reports[ $second_customer_index ]['state'] );
		$this->assertEquals( '54321', $reports[ $second_customer_index ]['postcode'] );
	}

	/**
	 * Test get_last_order.
	 */
	public function test_get_last_order() {
		wp_set_current_user( $this->user );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();
		$order2 = WC_Helper_Order::create_order( 0 );
		$order2->set_status( 'completed' );
		$order2->set_total( 100 );
		$order2->save();
		$order3 = WC_Helper_Order::create_order( 0 );
		$order3->set_status( 'completed' );
		$order3->set_total( 100 );
		$order3->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_id  = CustomersDataStore::get_existing_customer_id_from_order( $order );
		$customer2_id = CustomersDataStore::get_existing_customer_id_from_order( $order2 );
		$customer3_id = CustomersDataStore::get_existing_customer_id_from_order( $order3 );
		$this->assertEquals( $customer_id, $customer2_id );
		$this->assertEquals( $customer_id, $customer3_id );

		$latest_order = CustomersDataStore::get_last_order( $customer_id );

		$this->assertEquals( $latest_order->get_id(), $order3->get_id() );

		$order->set_date_created( time() + 60 );
		$order->save();
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$latest_order = CustomersDataStore::get_last_order( $customer_id );

		$this->assertEquals( $latest_order->get_id(), $order->get_id() );
	}
}
