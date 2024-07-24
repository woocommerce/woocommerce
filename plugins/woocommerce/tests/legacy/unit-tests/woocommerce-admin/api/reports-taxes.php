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
	 * Tax option.
	 *
	 * @var string
	 */
	protected $original_tax_option;

	/**
	 * Setup test reports taxes data.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_tax_option = get_option( 'woocommerce_calc_taxes' );
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
		parent::tearDown();

		update_option( 'woocommerce_calc_taxes', $this->original_tax_option );
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

		$this->create_sample_taxes();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'taxes'    => '1,2,3',
				'per_page' => '2',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		// Results are ordered by tax_rate_id desc by default.
		$expected = array(
			3 => array(
				'tax_rate_id'  => 3,
				'tax_rate'     => 9.0,
				'country'      => 'ES',
				'state'        => 'B',
				'total_tax'    => 9.9 * 2,
				'order_tax'    => 9.0 * 2,
				'shipping_tax' => 0.9 * 2,
				'orders_count' => 2,
			),
			2 => array(
				'tax_rate_id'  => 2,
				'tax_rate'     => 8.0,
				'country'      => 'CA',
				'state'        => 'ON',
				'total_tax'    => 8.8,
				'order_tax'    => 8,
				'shipping_tax' => 0.8,
				'orders_count' => 1,
			),

		);

		foreach ( $reports as $tax_report ) {
			$expected_report = $expected[ $tax_report['tax_rate_id'] ];
			foreach ( array_keys( $expected_report ) as $key ) {
				$this->assertEquals( $expected_report[ $key ], $tax_report[ $key ] );
			}
		}

		// Get next page.
		$request->set_query_params(
			array(
				'taxes'    => '1,2,3',
				'per_page' => '2',
				'page'     => '2',
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$expected = array(
			1 => array(
				'tax_rate_id'  => 1,
				'tax_rate'     => 7,
				'country'      => 'US',
				'state'        => 'NY',
				'total_tax'    => 7,
				'order_tax'    => 5,
				'shipping_tax' => 2,
				'orders_count' => 1,
			),
		);

		foreach ( $reports as $tax_report ) {
			$expected_report = $expected[ $tax_report['tax_rate_id'] ];
			foreach ( array_keys( $expected_report ) as $key ) {
				$this->assertEquals( $expected_report[ $key ], $tax_report[ $key ] );
			}
		}
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

	/**
	 * Create sample taxes.
	 */
	protected function create_sample_taxes() {
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

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 3,
				'tax_rate'          => '9',
				'tax_rate_country'  => 'ES',
				'tax_rate_state'    => 'B',
				'tax_rate_name'     => 'TestTax 3',
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

		$tax_item_es = new WC_Order_Item_Tax();
		$tax_item_es->set_rate( 3 );
		$tax_item_es->set_tax_total( 15 );
		$tax_item_es->set_shipping_tax_total( 0 );

		$order_es = WC_Helper_Order::create_order( 1, $product );
		$order_es->set_shipping_state( 'B' );
		$order_es->set_shipping_country( 'ES' );
		$order_es->add_item( $tax_item_es );
		$order_es->set_status( 'completed' );
		$order_es->set_total( 100 ); // $25 x 4.
		$order_es->save();
		$order_es->calculate_totals( true );

		$tax_item_es_2 = new WC_Order_Item_Tax();
		$tax_item_es_2->set_rate( 3 );
		$tax_item_es_2->set_tax_total( 15 );
		$tax_item_es_2->set_shipping_tax_total( 0 );

		$order_es_2 = WC_Helper_Order::create_order( 1, $product );
		$order_es_2->set_shipping_state( 'B' );
		$order_es_2->set_shipping_country( 'ES' );
		$order_es_2->add_item( $tax_item_es_2 );
		$order_es_2->set_status( 'completed' );
		$order_es_2->set_total( 100 ); // $25 x 4.
		$order_es_2->save();
		$order_es_2->calculate_totals( true );

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

		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order_es->get_id(),
				'tax_rate_id'  => 3,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);

		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order_es_2->get_id(),
				'tax_rate_id'  => 3,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);

		WC_Helper_Queue::run_all_pending();
	}
}
