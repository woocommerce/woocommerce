<?php
/**
 * Reports Performance indicators REST API Tests
 *
 * @package WooCommerce\Admin\Tests\API.
 */

/**
 * WC_Admin_Tests_API_Reports_Performance_Indicators
 */
class WC_Admin_Tests_API_Reports_Performance_Indicators extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/performance-indicators';

	/**
	 * Setup tests.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Mock the Jetpack endpoints and permissions.
		$wp_user = get_userdata( $this->user );
		$wp_user->add_cap( 'view_stats' );
		$this->mock_jetpack_modules();

		add_filter( 'rest_post_dispatch', array( $this, 'mock_rest_responses' ), 10, 3 );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
		$this->assertArrayHasKey( $this->endpoint . '/allowed', $routes );
	}

	/**
	 * Test getting indicators.
	 */
	public function test_get_indicators() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data. We'll create an order and a download.
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

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$time    = time();
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before' => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'  => gmdate( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
				'stats'  => 'orders/orders_count,downloads/download_count,test/bogus_stat,jetpack/stats/views',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $reports ) );

		$this->assertEquals( 'orders/orders_count', $reports[0]['stat'] );
		$this->assertEquals( 'Orders', $reports[0]['label'] );
		$this->assertEquals( 1, $reports[0]['value'] );
		$this->assertEquals( 'orders_count', $reports[0]['chart'] );
		$this->assertEquals( '/analytics/orders', $response->data[0]['_links']['report'][0]['href'] );

		$this->assertEquals( 'downloads/download_count', $reports[1]['stat'] );
		$this->assertEquals( 'Downloads', $reports[1]['label'] );
		$this->assertEquals( 2, $reports[1]['value'] );
		$this->assertEquals( 'download_count', $reports[1]['chart'] );
		$this->assertEquals( '/analytics/downloads', $response->data[1]['_links']['report'][0]['href'] );

		$this->assertEquals( 'jetpack/stats/views', $reports[2]['stat'] );
		$this->assertEquals( 'Views', $reports[2]['label'] );
		$this->assertEquals( 10, $reports[2]['value'] );
		$this->assertEquals( 'views', $reports[2]['chart'] );
		$this->assertEquals( get_rest_url( null, '/jetpack/v4/module/stats/data' ), $response->data[2]['_links']['api'][0]['href'] );
	}

	/**
	 * Test getting indicators with an empty request.
	 */
	public function test_get_indicators_empty_request() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$time    = time();
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before' => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'  => gmdate( 'Y-m-d H:00:00', $time - ( 7 * DAY_IN_SECONDS ) ),
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

		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'stat', $properties );
		$this->assertArrayHasKey( 'chart', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'format', $properties );
		$this->assertArrayHasKey( 'value', $properties );
	}

	/**
	 * Test schema for /allowed indicators endpoint.
	 */
	public function test_indicators_schema_allowed() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint . '/allowed' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'stat', $properties );
		$this->assertArrayHasKey( 'chart', $properties );
		$this->assertArrayHasKey( 'label', $properties );
	}

	/**
	 * Test the ability to aggregate Jetpack stats based on before and after dates.
	 */
	public function test_jetpack_stats_query_args() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before' => '2020-01-05 23:59:59',
				'after'  => '2020-01-01 00:00:00',
				'stats'  => 'jetpack/stats/views',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$this->assertEquals( 'jetpack/stats/views', $reports[0]['stat'] );
		$this->assertEquals( 'Views', $reports[0]['label'] );
		$this->assertEquals( 18, $reports[0]['value'] );
		$this->assertEquals( 'views', $reports[0]['chart'] );
		$this->assertEquals( get_rest_url( null, '/jetpack/v4/module/stats/data' ), $response->data[0]['_links']['api'][0]['href'] );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before' => '2020-01-02 23:59:59',
				'after'  => '2020-01-01 00:00:00',
				'stats'  => 'jetpack/stats/views',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$this->assertEquals( 'jetpack/stats/views', $reports[0]['stat'] );
		$this->assertEquals( 'Views', $reports[0]['label'] );
		$this->assertEquals( 4, $reports[0]['value'] );
		$this->assertEquals( 'views', $reports[0]['chart'] );
		$this->assertEquals( get_rest_url( null, '/jetpack/v4/module/stats/data' ), $response->data[0]['_links']['api'][0]['href'] );
	}

	/**
	 * Test the ability to aggregate Jetpack stats based on default arguments.
	 */
	public function test_jetpack_stats_default_query_args() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'stats' => 'jetpack/stats/views',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$this->assertEquals( 'jetpack/stats/views', $reports[0]['stat'] );
		$this->assertEquals( 'Views', $reports[0]['label'] );
		$this->assertEquals( 10, $reports[0]['value'] );
		$this->assertEquals( 'views', $reports[0]['chart'] );
		$this->assertEquals( get_rest_url( null, '/jetpack/v4/module/stats/data' ), $response->data[0]['_links']['api'][0]['href'] );
	}

	/**
	 * Mock the Jetpack REST API responses since we're not really connected.
	 *
	 * @param WP_Rest_Response $response Response from the server.
	 * @param WP_Rest_Server   $rest_server WP Rest Server.
	 * @param WP_REST_Request  $request Request made to the server.
	 *
	 * @return WP_Rest_Response
	 */
	public function mock_rest_responses( $response, $rest_server, $request ) {
		if ( 'GET' === $request->get_method() && '/jetpack/v4/module/stats/data' === $request->get_route() ) {
			$general                 = new \stdClass();
			$general->visits         = new \stdClass();
			$general->visits->fields = array(
				'date',
				'views',
				'visits',
			);
			$general->visits->data   = array(
				array(
					'2020-01-01',
					1,
					0,
				),
				array(
					'2020-01-02',
					3,
					0,
				),
				array(
					'2020-01-03',
					1,
					0,
				),
				array(
					'2020-01-04',
					8,
					0,
				),
				array(
					'2020-01-05',
					5,
					0,
				),
				array(
					gmdate( 'Y-m-d' ),
					10,
					0,
				),
			);
			$response->set_status( 200 );
			$response->set_data(
				array( 'general' => $general )
			);
		}

		return $response;
	}

	/**
	 * Mock the Jetpack stats module as active.
	 */
	public function mock_jetpack_modules() {
		$api_init        = \Automattic\WooCommerce\Admin\API\Init::instance();
		$controller_name = 'Automattic\WooCommerce\Admin\API\Reports\PerformanceIndicators\Controller';

		$api_init->$controller_name->set_active_jetpack_modules( array( 'stats' ) );
	}
}
