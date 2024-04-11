<?php
/**
 * Reports Taxes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

/**
 * WC_Admin_Tests_API_Reports_Taxes
 */
class WC_Admin_Tests_API_Reports_Taxes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/taxes';

	/**
	 * Setup test reports taxes data.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Clean up after each test. DB changes are reverted in parent::tearDown().
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );

		parent::tearDown();
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
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$tax_rate_id = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 1,
				'tax_rate'          => '7',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'NY',
				'tax_rate_name'     => 'TestTax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( 5 );
		$tax_item->set_shipping_tax_total( 2 );

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->add_item( $tax_item );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// @todo Remove this once order data is synced to wc_order_tax_lookup
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order->get_id(),
				'tax_rate_id'  => 1,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);

		WC_Helper_Queue::run_all_pending();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$tax_report = reset( $reports );

		$this->assertEquals( 1, $tax_report['tax_rate_id'] );
		// $this->assertEquals( 'TestTax', $tax_report['name'] );
		$this->assertEquals( 7, $tax_report['tax_rate'] );
		$this->assertEquals( 'US', $tax_report['country'] );
		$this->assertEquals( 'NY', $tax_report['state'] );
		$this->assertEquals( 7, $tax_report['total_tax'] );
		$this->assertEquals( 5, $tax_report['order_tax'] );
		$this->assertEquals( 2, $tax_report['shipping_tax'] );
		$this->assertEquals( 1, $tax_report['orders_count'] );
	}

	/**
	 * Test getting reports with the `taxes` report.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_taxes_param() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 1,
				'tax_rate'          => '7',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'NY',
				'tax_rate_name'     => 'TestTax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 2,
				'tax_rate'          => '8',
				'tax_rate_country'  => 'CA',
				'tax_rate_state'    => 'ON',
				'tax_rate_name'     => 'TestTax 2',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( 1 );
		$tax_item->set_tax_total( 5 );
		$tax_item->set_shipping_tax_total( 2 );

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->add_item( $tax_item );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		$tax_item_ca = new WC_Order_Item_Tax();
		$tax_item_ca->set_rate( 2 );
		$tax_item_ca->set_tax_total( 15 );
		$tax_item_ca->set_shipping_tax_total( 0 );

		$order_ca = WC_Helper_Order::create_order( 1, $product );
		$order_ca->set_shipping_state( 'ON' );
		$order_ca->set_shipping_country( 'CA' );
		$order_ca->add_item( $tax_item_ca );
		$order_ca->set_status( 'completed' );
		$order_ca->set_total( 100 ); // $25 x 4.
		$order_ca->save();
		$order_ca->calculate_totals( true );

		// @todo Remove this once order data is synced to wc_order_tax_lookup
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order->get_id(),
				'tax_rate_id'  => 1,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order_ca->get_id(),
				'tax_rate_id'  => 2,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);

		WC_Helper_Queue::run_all_pending();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'taxes' => '1,2',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$tax_report = reset( $reports );

		$this->assertEquals( 2, $tax_report['tax_rate_id'] );
		// $this->assertEquals( 'TestTax 2', $tax_report['name'] );
		$this->assertEquals( 8.0, $tax_report['tax_rate'] );
		$this->assertEquals( 'CA', $tax_report['country'] );
		$this->assertEquals( 'ON', $tax_report['state'] );
		$this->assertEquals( 8.8, $tax_report['total_tax'] );
		$this->assertEquals( 8, $tax_report['order_tax'] );
		$this->assertEquals( 0.8, $tax_report['shipping_tax'] );
		$this->assertEquals( 1, $tax_report['orders_count'] );

		$tax_report = next( $reports );

		$this->assertEquals( 1, $tax_report['tax_rate_id'] );
		// $this->assertEquals( 'TestTax', $tax_report['name'] );
		$this->assertEquals( 7, $tax_report['tax_rate'] );
		$this->assertEquals( 'US', $tax_report['country'] );
		$this->assertEquals( 'NY', $tax_report['state'] );
		$this->assertEquals( 7, $tax_report['total_tax'] );
		$this->assertEquals( 5, $tax_report['order_tax'] );
		$this->assertEquals( 2, $tax_report['shipping_tax'] );
		$this->assertEquals( 1, $tax_report['orders_count'] );
	}

	/**
	 * Test getting reports with param `orderby=rate`.
	 *
	 * @since 3.5.0
	 */
	/*
	public function test_get_reports_orderby_tax_rate() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 1,
				'tax_rate'          => '7',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'GA',
				'tax_rate_name'     => 'TestTax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 2,
				'tax_rate'          => '10',
				'tax_rate_country'  => 'CA',
				'tax_rate_state'    => 'ON',
				'tax_rate_name'     => 'TestTax 2',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'order'   => 'asc',
				'orderby' => 'rate',
				'taxes'   => '1,2',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( 1, $reports[0]['tax_rate_id'] );
		$this->assertEquals( 7, $reports[0]['tax_rate'] );

		$this->assertEquals( 2, $reports[1]['tax_rate_id'] );
		$this->assertEquals( 10, $reports[1]['tax_rate'] );
	}*/

	/**
	 * Test getting reports with param `orderby=tax_code`.
	 *
	 * @since 3.5.0
	 */
	/*
	public function test_get_reports_orderby_tax_code() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 1,
				'tax_rate'          => '7',
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'GA',
				'tax_rate_name'     => 'TestTax',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 2,
				'tax_rate'          => '10',
				'tax_rate_country'  => 'CA',
				'tax_rate_state'    => 'ON',
				'tax_rate_name'     => 'TestTax 2',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'order'   => 'asc',
				'orderby' => 'tax_code',
				'taxes'   => '1,2',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( 2, $reports[0]['tax_rate_id'] );

		$this->assertEquals( 1, $reports[1]['tax_rate_id'] );
	} */

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

		$this->assertEquals( 10, count( $properties ) );
		$this->assertArrayHasKey( 'tax_rate_id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'tax_rate', $properties );
		$this->assertArrayHasKey( 'country', $properties );
		$this->assertArrayHasKey( 'state', $properties );
		$this->assertArrayHasKey( 'priority', $properties );
		$this->assertArrayHasKey( 'total_tax', $properties );
		$this->assertArrayHasKey( 'order_tax', $properties );
		$this->assertArrayHasKey( 'shipping_tax', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
	}
}
