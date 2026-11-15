<?php
/**
 * Reports Products Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Products_Stats
 */
class WC_Admin_Tests_API_Reports_Products_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/products/stats';

	/**
	 * Setup test reports products stats data.
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
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$time = time();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval' => 'day',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$expected_reports = array(
			'totals'    => array(
				'items_sold'       => 4,
				'net_revenue'      => 100.0,
				'orders_count'     => 1,
				'products_count'   => 1,
				'variations_count' => 1,
				'segments'         => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d', $time ),
					'date_start'     => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_start_gmt' => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_end'       => gmdate( 'Y-m-d 23:59:59', $time ),
					'date_end_gmt'   => gmdate( 'Y-m-d 23:59:59', $time ),
					'subtotals'      => (object) array(
						'items_sold'       => 4,
						'net_revenue'      => 100.0,
						'orders_count'     => 1,
						'products_count'   => 1,
						'variations_count' => 1,
						'segments'         => array(),
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_reports, $reports );
	}

	/**
	 * @testdox Should narrow the totals to the products matching the `search` param.
	 */
	public function test_get_reports_search_param() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time = time();

		foreach ( array( 'Kingston Widget', 'Unrelated Thing' ) as $name ) {
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( 25 );
			$product->save();

			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( OrderStatus::COMPLETED );
			// $25 x 4.
			$order->set_total( 100 );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval' => 'day',
				'search'   => 'Kingston',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'items_sold'       => 4,
				'net_revenue'      => 100.0,
				'orders_count'     => 1,
				'products_count'   => 1,
				'variations_count' => 1,
				'segments'         => array(),
			),
			$reports['totals'],
			'Only the products matching the search should be aggregated'
		);
	}

	/**
	 * @testdox Should report no totals when the `search` param is combined with a filter no product satisfies.
	 */
	public function test_get_reports_search_param_with_a_filter_no_product_satisfies() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time = time();

		$match = $this->create_product_with_id_1( 'Kingston Widget' );

		$order = WC_Helper_Order::create_order( 1, $match );
		$order->set_status( OrderStatus::COMPLETED );
		// $25 x 4.
		$order->set_total( 100 );
		$order->save();

		$empty_category = wp_insert_term( 'Empty Category', 'product_cat' );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'     => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'      => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval'   => 'day',
				'search'     => 'Kingston',
				'categories' => (string) $empty_category['term_id'],
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'items_sold'       => 0,
				'net_revenue'      => 0.0,
				'orders_count'     => 0,
				'products_count'   => 0,
				'variations_count' => 0,
				'segments'         => array(),
			),
			$reports['totals'],
			'A category holding no product leaves the search nothing to match'
		);
	}

	/**
	 * @testdox Should segment by the products matching the `search` param, and no others.
	 *
	 * The segment list is filled in with a zeroed entry per product it covers, so leaving the
	 * search out of it puts every product in the store in the response.
	 */
	public function test_get_reports_search_param_narrows_the_product_segments() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time     = time();
		$products = array();

		foreach ( array( 'Kingston Widget', 'Kingston Gadget', 'Unrelated Thing' ) as $name ) {
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( 25 );
			$product->save();

			$products[ $name ] = $product->get_id();
		}

		$order = WC_Helper_Order::create_order( 1, wc_get_product( $products['Kingston Widget'] ) );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'    => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'     => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval'  => 'day',
				'search'    => 'Kingston',
				'segmentby' => 'product',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$segment_ids = wp_list_pluck( $reports['totals']['segments'], 'segment_id' );
		sort( $segment_ids );

		$expected = array( $products['Kingston Gadget'], $products['Kingston Widget'] );
		sort( $expected );

		$this->assertEquals(
			$expected,
			$segment_ids,
			'A product the search does not match should not come back as a segment'
		);
	}

	/**
	 * @testdox Should segment by the products the `search` param matches inside the category.
	 *
	 * This is the Categories report's single category view, which segments by product. The term and
	 * the category narrow each other, so a product only one of them covers is not a segment.
	 */
	public function test_get_reports_search_param_narrows_the_product_segments_within_a_category() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time     = time();
		$category = wp_insert_term( 'Widgets', 'product_cat' );
		$products = array();

		foreach ( array( 'Kingston Widget', 'Kingston Gadget', 'Unrelated Widget' ) as $name ) {
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( 25 );
			$product->save();

			$products[ $name ] = $product->get_id();
		}

		// Everything but the gadget is in the category, so only the widget satisfies both.
		wp_set_object_terms( $products['Kingston Widget'], array( $category['term_id'] ), 'product_cat' );
		wp_set_object_terms( $products['Unrelated Widget'], array( $category['term_id'] ), 'product_cat' );

		$order = WC_Helper_Order::create_order( 1, wc_get_product( $products['Kingston Widget'] ) );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'     => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'      => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval'   => 'day',
				'search'     => 'Kingston',
				'categories' => (string) $category['term_id'],
				'segmentby'  => 'product',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array( $products['Kingston Widget'] ),
			wp_list_pluck( $reports['totals']['segments'], 'segment_id' ),
			'Only a product both the search and the category cover should come back as a segment'
		);
	}

	/**
	 * @testdox Should report no product segments when the `search` param matches nothing.
	 */
	public function test_get_reports_search_param_with_no_match_has_no_product_segments() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		$time = time();

		$product = new WC_Product_Simple();
		$product->set_name( 'Kingston Widget' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'    => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'     => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval'  => 'day',
				'search'    => 'nothing matches this',
				'segmentby' => 'product',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(),
			$reports['totals']['segments'],
			'An empty set of matches should not read as no restriction at all'
		);
	}

	/**
	 * @testdox Should register the `search` collection param.
	 */
	public function test_search_collection_param_is_registered() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', $this->endpoint ) );
		$args     = $response->get_data()['endpoints'][0]['args'];

		$this->assertArrayHasKey( 'search', $args );
		$this->assertEquals( 'array', $args['search']['type'] );
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
		$this->assertEquals( 4, count( $totals ) );
		$this->assertArrayHasKey( 'net_revenue', $totals );
		$this->assertArrayHasKey( 'items_sold', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
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
		$this->assertEquals( 4, count( $subtotals ) );
		$this->assertArrayHasKey( 'net_revenue', $subtotals );
		$this->assertArrayHasKey( 'items_sold', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}

	/**
	 * Creates a simple product with product ID 1.
	 *
	 * The filters resolve to `-1` when no product satisfies them and `absint()` reads that as
	 * product ID 1, so the wrong product is only aggregated when one has that ID.
	 *
	 * @param string $name Product name.
	 * @return WC_Product_Simple
	 */
	private function create_product_with_id_1( $name ) {
		wp_delete_post( 1, true );

		$this->assertSame(
			1,
			wp_insert_post(
				array(
					'import_id'   => 1,
					'post_title'  => $name,
					'post_type'   => 'product',
					'post_status' => 'publish',
				)
			)
		);

		$product = wc_get_product( 1 );
		$product->set_regular_price( 25 );
		$product->save();

		return $product;
	}
}
