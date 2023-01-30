<?php
/**
 * Reports Orders Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC_Admin_Tests_API_Reports_Orders_Stats
 */

/**
 * Class WC_Admin_Tests_API_Reports_Orders_Stats
 */
class WC_Admin_Tests_API_Reports_Orders_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/orders/stats';

	/**
	 * Setup test reports orders data.
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
		wp_set_current_user( $this->user );

		// @todo Update after report interface is done.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) ); // totals and intervals.
		// @todo Update results after implement report interface.
		// $this->assertEquals( array(), $reports ); // @codingStandardsIgnoreLine.
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 10, count( $totals ) );
		$this->assertArrayHasKey( 'net_revenue', $totals );
		$this->assertArrayHasKey( 'avg_order_value', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'avg_items_per_order', $totals );
		$this->assertArrayHasKey( 'num_items_sold', $totals );
		$this->assertArrayHasKey( 'coupons', $totals );
		$this->assertArrayHasKey( 'coupons_count', $totals );
		$this->assertArrayHasKey( 'total_customers', $totals );
		$this->assertArrayHasKey( 'products', $totals );
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
		$this->assertEquals( 9, count( $subtotals ) );
		$this->assertArrayHasKey( 'net_revenue', $subtotals );
		$this->assertArrayHasKey( 'avg_order_value', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'avg_items_per_order', $subtotals );
		$this->assertArrayHasKey( 'num_items_sold', $subtotals );
		$this->assertArrayHasKey( 'coupons', $subtotals );
		$this->assertArrayHasKey( 'coupons_count', $subtotals );
		$this->assertArrayHasKey( 'total_customers', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}

	/**
	 * Test filtering by product attribute(s).
	 */
	public function test_product_attributes_filter() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.5', '<' ) ) {
			$this->markTestSkipped( 'Skipped in older versions of WordPress due to a bug in WP when validating arrays.' );
		}

		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Create a variable product.
		$variable_product   = WC_Helper_Product::create_variation_product( new WC_Product_Variable() );
		$product_variations = $variable_product->get_children();
		$order_variation_1  = wc_get_product( $product_variations[1] ); // Variation: size = large.
		$order_variation_2  = wc_get_product( $product_variations[3] ); // Variation: size = huge, colour = red, number = 2.

		// Create orders for variations.
		$variation_order_1 = WC_Helper_Order::create_order( $this->user, $order_variation_1 );
		$variation_order_1->set_status( 'completed' );
		$variation_order_1->save();

		$variation_order_2 = WC_Helper_Order::create_order( $this->user, $order_variation_2 );
		$variation_order_2->set_status( 'completed' );
		$variation_order_2->save();

		// Create more orders for simple products.
		for ( $i = 0; $i < 10; $i++ ) {
			$order = WC_Helper_Order::create_order( $this->user );
			$order->set_status( 'completed' );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'per_page' => 15 ) );
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		// Sanity check before filtering by attribute.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 12, $response_orders['totals']['orders_count'] );

		// Filter by the "size" attribute, with value "large".
		$size_attr_id = wc_attribute_taxonomy_id_by_name( 'pa_size' );
		$small_term   = get_term_by( 'slug', 'large', 'pa_size' );
		$request      = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'attribute_is' => array(
					array( $size_attr_id, $small_term->term_id ),
				),
			)
		);
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $response_orders['totals']['orders_count'] );
		$this->assertEquals( $variation_order_1->get_total(), $response_orders['totals']['total_sales'] );

		// Filter by excluding the "size" attribute, with value "large".
		$size_attr_id = wc_attribute_taxonomy_id_by_name( 'pa_size' );
		$small_term   = get_term_by( 'slug', 'large', 'pa_size' );
		$request      = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'attribute_is_not' => array(
					array( $size_attr_id, $small_term->term_id ),
				),
			)
		);
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $response_orders['totals']['orders_count'] );
		// This should be the second variation order.
		$this->assertEquals( $variation_order_2->get_total(), $response_orders['totals']['total_sales'] );
	}
}
