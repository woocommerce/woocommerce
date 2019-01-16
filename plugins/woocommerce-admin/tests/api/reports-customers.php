<?php
/**
 * Reports Customers REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

/**
 * Reports Customers REST API Test Class
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Reports_Customers extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/reports/customers';

	/**
	 * Setup test reports products data.
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
	 * Asserts the report item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_report_item_schema( $schema ) {
		$this->assertArrayHasKey( 'customer_id', $schema );
		$this->assertArrayHasKey( 'user_id', $schema );
		$this->assertArrayHasKey( 'name', $schema );
		$this->assertArrayHasKey( 'username', $schema );
		$this->assertArrayHasKey( 'country', $schema );
		$this->assertArrayHasKey( 'city', $schema );
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

		$this->assertCount( 14, $properties );
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
		$result = WC_Admin_Reports_Customers_Data_Store::update_registered_customer( 2 );
		$this->assertFalse( $result );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$test_customers = array();

		// Create 10 test customers.
		for ( $i = 1; $i <= 10; $i++ ) {
			$test_customers[] = WC_Helper_Customer::create_customer( "customer{$i}", 'password', "customer{$i}@example.com" );
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

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
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

		// Test name parameter (case with no matches).
		$request->set_query_params(
			array(
				'name' => 'Nota Customername',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $reports );

		// Test name and last_order parameters.
		$request->set_query_params(
			array(
				'name'             => 'Justin',
				'last_order_after' => date( 'Y-m-d' ) . 'T00:00:00Z',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $test_customers[0]->get_id(), $reports[0]['user_id'] );
		$this->assertEquals( 1, $reports[0]['orders_count'] );
		$this->assertEquals( 100, $reports[0]['total_spend'] );
	}
}
