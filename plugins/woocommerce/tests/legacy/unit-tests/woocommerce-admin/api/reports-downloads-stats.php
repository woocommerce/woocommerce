<?php
/**
 * Reports Downloads Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API.
 */

/**
 * WC_Admin_Tests_API_Reports_Downloads_Stats
 */
class WC_Admin_Tests_API_Reports_Downloads_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/downloads/stats';

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
		$prod_download->set_file( plugin_dir_url( WC_ABSPATH ) . 'woocommerce/assets/images/help.png' );
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

		$time    = time();
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
				'interval' => 'day',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$totals = array(
			'download_count' => 1,
		);
		$this->assertEquals( $totals, $reports['totals'] );

		$today_interval = array(
			'interval'       => gmdate( 'Y-m-d', $time ),
			'date_start'     => gmdate( 'Y-m-d 00:00:00', $time ),
			'date_start_gmt' => gmdate( 'Y-m-d 00:00:00', $time ),
			'date_end'       => gmdate( 'Y-m-d 23:59:59', $time ),
			'date_end_gmt'   => gmdate( 'Y-m-d 23:59:59', $time ),
			'subtotals'      => (object) array(
				'download_count' => 1,
			),
		);
		$this->assertEquals( $today_interval, $reports['intervals'][0] );

		$this->assertEquals( 8, count( $reports['intervals'] ) );
		$this->assertEquals( 0, $reports['intervals'][1]['subtotals']->download_count );

		// Test sorting by download_count.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
				'interval' => 'day',
				'orderby'  => 'download_count',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
	}

	/**
	 * Test getting report with user filter.
	 */
	public function test_get_report_with_user_filter() {
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

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 25 );
		$order->save();
		$order_1 = $order->get_id();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$download = new WC_Customer_Download();
		$download->set_user_id( 1 );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( $prod_download->get_id() );
		$download->save();

		for ( $i = 1; $i < 3; $i++ ) {
			$object = new WC_Customer_Download_Log();
			$object->set_permission_id( $download->get_id() );
			$object->set_user_id( 1 );
			$object->set_user_ip_address( '1.2.3.4' );
			$id = $object->save();
		}

		$order = WC_Helper_Order::create_order( 2, $product );
		$order->set_status( 'completed' );
		$order->set_total( 10 );
		$order->save();

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
		$object->save();

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
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $reports['totals']['download_count'] );

		// Test excludes filtering.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'customer_excludes' => $customer_id,
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $reports['totals']['download_count'] );
	}

	/**
	 * Test getting report ordering.
	 */
	public function test_get_report_orderby() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( WC_ABSPATH ) . 'woocommerce/assets/images/help.png' );
		$prod_download->set_id( '3' );

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
		$object->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '1.2.3.4' );
		$object->save();

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '1.2.3.4' );
		$object->save();

		$three_days_from_now = time() - ( 3 * DAY_IN_SECONDS );

		$object = new WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '1.2.3.4' );
		$object->set_timestamp( $three_days_from_now );
		$object->save();

		$time           = time();
		$seven_days_ago = $time - ( 7 * DAY_IN_SECONDS );

		// Test sorting by download_count.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d H:00:00', $seven_days_ago ),
				'interval' => 'day',
				'orderby'  => 'download_count',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 3, $reports['intervals'][0]['subtotals']->download_count );
		$this->assertEquals( gmdate( 'Y-m-d', $time ), $reports['intervals'][0]['interval'] );

		$this->assertEquals( 1, $reports['intervals'][1]['subtotals']->download_count );
		$this->assertEquals( gmdate( 'Y-m-d', $three_days_from_now ), $reports['intervals'][1]['interval'] );

		// Test sorting by date.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d H:00:00', $seven_days_ago ),
				'interval' => 'day',
				'orderby'  => 'date',
				'order'    => 'asc',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 0, $reports['intervals'][0]['subtotals']->download_count );
		$this->assertEquals( gmdate( 'Y-m-d', $seven_days_ago ), $reports['intervals'][0]['interval'] );

		$this->assertEquals( 3, $reports['intervals'][7]['subtotals']->download_count );
		$this->assertEquals( gmdate( 'Y-m-d', $time ), $reports['intervals'][7]['interval'] );
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 1, count( $totals ) );
		$this->assertArrayHasKey( 'download_count', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 1, count( $subtotals ) );
		$this->assertArrayHasKey( 'download_count', $subtotals );
	}
}
