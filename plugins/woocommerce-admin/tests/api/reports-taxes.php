<?php
/**
 * Reports Taxes REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

/**
 * WC_Tests_API_Reports_Taxes
 */
class WC_Tests_API_Reports_Taxes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/reports/taxes';

	/**
	 * Setup test reports taxes data.
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

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// @todo Remove this once order data is synced to wc_order_tax_lookup
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => 1,
				'tax_rate_id'  => 1,
				'date_created' => date( 'Y-m-d H:i:s' ),
				'shipping_tax' => 2,
				'order_tax'    => 5,
				'total_tax'    => 7,
			)
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$tax_report = reset( $reports );

		$this->assertEquals( 1, $tax_report['tax_rate_id'] );
		$this->assertEquals( 'TestTax', $tax_report['name'] );
		$this->assertEquals( 7, $tax_report['tax_rate'] );
		$this->assertEquals( 'US', $tax_report['country'] );
		$this->assertEquals( 'GA', $tax_report['state'] );
		$this->assertEquals( 7, $tax_report['total_tax'] );
		$this->assertEquals( 5, $tax_report['order_tax'] );
		$this->assertEquals( 2, $tax_report['shipping_tax'] );
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
