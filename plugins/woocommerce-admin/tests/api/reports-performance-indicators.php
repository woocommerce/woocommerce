<?php
/**
 * Reports Performance indicators REST API Tests
 *
 * @package WooCommerce Admin\Tests\API.
 */

/**
 * WC_Tests_API_Reports_Performance_Indicators
 */
class WC_Tests_API_Reports_Performance_Indicators extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/performance-indicators';

	/**
	 * Setup tests.
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
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting indicators.
	 */
	public function test_get_indicators() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data. We'll create an order and a download.
		$prod_download = new WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( __FILE__ ) . '/assets/images/help.png' );
		$prod_download->set_id( 1 );

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

		$time = time();
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => date( 'Y-m-d 23:59:59', $time ),
				'after'    => date( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
				'stats' => 'orders/orders_count,downloads/download_count,test/bogus_stat',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( 'orders/orders_count', $reports[0]['stat'] );
		$this->assertEquals( 1, $reports[0]['value'] );

		$this->assertEquals( 'downloads/download_count', $reports[1]['stat'] );
		$this->assertEquals( 2, $reports[1]['value'] );
	}

	/**
	 * Test getting indicators with an empty request.
	 */
	public function test_get_indicators_empty_request() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$time = time();
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => date( 'Y-m-d 23:59:59', $time ),
				'after'    => date( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test getting without valid permissions.
	 */
	public function test_get_indicators_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test schema.
	 */
	public function test_indicators_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'stat', $properties );
		$this->assertArrayHasKey( 'value', $properties );
	}
}
