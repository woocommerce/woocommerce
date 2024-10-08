<?php
/**
 * Reports Taxes Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

/**
 * WC_Admin_Tests_API_Reports_Taxes_Stats
 */
class WC_Admin_Tests_API_Reports_Taxes_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/taxes/stats';

	/**
	 * Setup test reports taxes data.
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
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$tax = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '7',
				'tax_rate_name'     => 'TestTax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->set_tax_class( 'TestTax' );
		$product->save();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->calculate_taxes();
		$order->save();

		// Add refunds to line items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$refund    = array(
				'amount'     => 1,
				'reason'     => 'Testing line item refund',
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 1,
						'refund_tax'   => array( $tax => 1 ),
					),
				),
			);
			$wc_refund = wc_create_refund( $refund );
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$tax_report = reset( $reports );

		$this->assertEquals( 1, $tax_report['tax_codes'] );
		$this->assertEquals( 6.7, $tax_report['total_tax'] ); // 110 * 0.07 (tax rate) - 1 (refund)
		$this->assertEquals( 6, $tax_report['order_tax'] ); // 100 * 0.07 (tax rate) - 1 (refund)
		$this->assertEquals( 0.7, $tax_report['shipping_tax'] );
		$this->assertEquals( 1, $tax_report['orders_count'] );
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

		$totals = $properties['totals']['properties'];

		$this->assertEquals( 6, count( $totals ) );
		$this->assertArrayHasKey( 'order_tax', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'shipping_tax', $totals );
		$this->assertArrayHasKey( 'tax_codes', $totals );
		$this->assertArrayHasKey( 'total_tax', $totals );
		$this->assertArrayHasKey( 'segments', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 6, count( $subtotals ) );
		$this->assertArrayHasKey( 'order_tax', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'shipping_tax', $subtotals );
		$this->assertArrayHasKey( 'tax_codes', $subtotals );
		$this->assertArrayHasKey( 'total_tax', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );

	}
}
