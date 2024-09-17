<?php
/**
 * Reports Downloads REST API Test
 *
 * @package WooCommerce\Admin\Tests\API.
 */

/**
 * WC_Admin_Tests_API_Reports_Downloads
 */
class WC_Admin_Tests_API_Reports_Downloads extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/downloads';

	/**
	 * Setup test reports downloads data.
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
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting report.
	 */
	public function test_get_report() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( WC_ABSPATH . 'assets/images/help.png' );
		$prod_download->set_id( '1' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		$download = new WC_Customer_Download();
		$download->set_user_id( $this->user );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( $prod_download->get_id() );
		$download->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '1.2.3.4' );
		$id = $object->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$download_report = reset( $reports );

		$this->assertEquals( 1, $download_report['download_id'] );
		$this->assertEquals( $product->get_id(), $download_report['product_id'] );
		$this->assertEquals( $order->get_id(), $download_report['order_id'] );
		$this->assertEquals( $order->get_order_number(), $download_report['order_number'] );
		$this->assertEquals( $this->user, $download_report['user_id'] );
		$this->assertEquals( '1.2.3.4', $download_report['ip_address'] );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
		$this->assertEquals( WC_ABSPATH . 'assets/images/help.png', $download_report['file_path'] );
	}

	/**
	 * Does some test setup so we can filter with different options in later tests.
	 */
	public function filter_setup() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();
		$time = time();

		// First set of data.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( WC_ABSPATH ) . 'woocommerce/assets/images/help.png' );
		$prod_download->set_id( '2' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->set_regular_price( 25 );
		$product->save();
		$product_1 = $product->get_id();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 25 );
		$order->save();
		$order_1 = $order->get_id();

		$download = new WC_Customer_Download();
		$download->set_user_id( 1 );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( $prod_download->get_id() );
		$download->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( 1 );
		$object->set_user_ip_address( '1.2.3.4' );
		$id = $object->save();

		// Second set of data.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( WC_ABSPATH ) . 'woocommerce/assets/images/help.png' );
		$prod_download->set_id( '3' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product 2' );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->set_regular_price( 10 );
		$product->save();
		$product_2 = $product->get_id();

		$order = WC_Helper_Order::create_order( 2, $product );
		$order->set_status( 'completed' );
		$order->set_total( 10 );
		$order->save();
		$order_2 = $order->get_id();

		$download = new WC_Customer_Download();
		$download->set_user_id( 2 );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( $prod_download->get_id() );
		$download->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( 2 );
		$object->set_user_ip_address( '5.4.3.2.1' );
		$object->set_timestamp( gmdate( 'Y-m-d H:00:00', $time - ( 2 * DAY_IN_SECONDS ) ) );
		$id = $object->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		return array(
			'time'      => $time,
			'product_1' => $product_1,
			'product_2' => $product_2,
			'order_1'   => $order_1,
			'order_2'   => $order_2,
		);
	}

	/**
	 * Test getting report with date filter.
	 */
	public function test_get_report_with_date_filter() {
		$test_info = $this->filter_setup();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		// Test date filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before' => gmdate( 'Y-m-d H:00:00', $test_info['time'] + DAY_IN_SECONDS ),
				'after'  => gmdate( 'Y-m-d H:00:00', $test_info['time'] - DAY_IN_SECONDS ),
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
	}

	/**
	 * Test getting report with product filter.
	 */
	public function test_get_report_with_product_filter() {
		$test_info = $this->filter_setup();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		// Test includes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_includes' => $test_info['product_1'],
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );

		// Test excludes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_excludes' => $test_info['product_1'],
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
	}

	/**
	 * Test getting report with order filter.
	 */
	public function test_get_report_with_order_filter() {
		$test_info = $this->filter_setup();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		// Test includes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'order_includes' => $test_info['order_1'],
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );

		// Test excludes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'order_excludes' => $test_info['order_1'],
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
	}

	/**
	 * Test getting report with user filter.
	 */
	public function test_get_report_with_user_filter() {
		$test_info = $this->filter_setup();
		global $wpdb;

		$customer_id = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup WHERE user_id = %d",
				1
			)
		);

		// Test includes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'customer_includes' => $customer_id,
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
		$this->assertEquals( 1, $download_report['user_id'] );

		// Test excludes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'customer_excludes' => $customer_id,
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
		$this->assertEquals( 2, $download_report['user_id'] );
	}

	/**
	 * Test getting report with ip address filter.
	 */
	public function test_get_report_with_ip_address_filter() {
		$test_info = $this->filter_setup();

		// Test includes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'ip_address_includes' => '1.2.3.4',
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );

		// Test excludes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'ip_address_excludes' => '1.2.3.4',
			)
		);
		$response        = $this->server->dispatch( $request );
		$reports         = $response->get_data();
		$download_report = reset( $reports );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( 'help.png', $download_report['file_name'] );
	}

	/**
	 * Test deleted product in report result set.
	 */
	public function test_get_report_with_deleted_product() {
		$test_info = $this->filter_setup();

		// Delete the first test product.
		\WC_Helper_Product::delete_product( $test_info['product_1'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		// Verify the first product's download info is blank.
		$this->assertEquals( '', $reports[0]['file_name'] );
		$this->assertEquals( '', $reports[0]['file_path'] );
		$this->assertEquals( 'help.png', $reports[1]['file_name'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
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

		$this->assertEquals( 12, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'download_id', $properties );
		$this->assertArrayHasKey( 'file_name', $properties );
		$this->assertArrayHasKey( 'file_path', $properties );
		$this->assertArrayHasKey( 'order_id', $properties );
		$this->assertArrayHasKey( 'order_number', $properties );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'username', $properties );
		$this->assertArrayHasKey( 'ip_address', $properties );
	}
}
