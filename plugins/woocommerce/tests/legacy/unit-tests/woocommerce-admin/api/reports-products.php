<?php
/**
 * Reports Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Reports Products REST API Test Class
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */
class WC_Admin_Tests_API_Reports_Products extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/products';

	/**
	 * Setup test reports products data.
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
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$product_report = reset( $reports );

		$this->assertEquals( $product->get_id(), $product_report['product_id'] );
		$this->assertEquals( 4, $product_report['items_sold'] );
		$this->assertEquals( 1, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );
	}

	/**
	 * Test getting reports with the `products` param.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_products_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( 25 );
		$product_2->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'products' => $product->get_id() . ',' . $product_2->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$product_report = reset( $reports );

		$this->assertEquals( $product->get_id(), $product_report['product_id'] );
		$this->assertEquals( 4, $product_report['items_sold'] );
		$this->assertEquals( 1, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );

		$product_report = next( $reports );

		$this->assertEquals( $product_2->get_id(), $product_report['product_id'] );
		$this->assertEquals( null, $product_report['items_sold'] );
		$this->assertEquals( null, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );
	}

	/**
	 * @testdox Should only report the products matching the `search` param.
	 */
	public function test_get_reports_search_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$sold_match   = $this->create_product( 'Kingston Widget' );
		$unsold_match = $this->create_product( 'Kingston Gadget' );
		$sold_other   = $this->create_product( 'Unrelated Thing' );

		$this->create_completed_order( $sold_match );
		$this->create_completed_order( $sold_other );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->dispatch_report( array( 'search' => 'Kingston' ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$reports_by_id = array_column( $reports, null, 'product_id' );

		$this->assertArrayHasKey( $sold_match->get_id(), $reports_by_id, 'A matching product with sales should be reported' );
		$this->assertArrayHasKey( $unsold_match->get_id(), $reports_by_id, 'A matching product without sales should be reported' );
		$this->assertArrayNotHasKey( $sold_other->get_id(), $reports_by_id, 'A product that does not match the search should be left out' );

		$this->assertEquals( 4, $reports_by_id[ $sold_match->get_id() ]['items_sold'] );
		$this->assertEquals( 1, $reports_by_id[ $sold_match->get_id() ]['orders_count'] );
		$this->assertSame( 0, $reports_by_id[ $unsold_match->get_id() ]['items_sold'] );
	}

	/**
	 * @testdox Should not cap the `search` param at the first 100 matching products.
	 *
	 * The client used to resolve the search itself and pass back at most 100 product IDs, so any
	 * match past that was missing from the report.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/50786
	 */
	public function test_get_reports_search_param_is_not_capped_at_100_products() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// These only need to match the search, so the full product CRUD would be wasted work.
		for ( $i = 0; $i < 104; $i++ ) {
			wp_insert_post(
				array(
					'post_title'  => sprintf( 'Kingston Widget %03d', $i ),
					'post_type'   => 'product',
					'post_status' => 'publish',
				)
			);
		}

		$sold = $this->create_product( 'Kingston Widget 999' );
		$this->create_completed_order( $sold );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->dispatch_report(
			array(
				'search'   => 'Kingston',
				'per_page' => 100,
				'orderby'  => 'items_sold',
				'order'    => 'desc',
			)
		);
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 105, $headers['X-WP-Total'], 'Every matching product should be counted' );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
		$this->assertCount( 100, $reports );

		$top_seller = reset( $reports );
		$this->assertEquals( $sold->get_id(), $top_seller['product_id'], 'The only product with sales should sort first' );
		$this->assertEquals( 4, $top_seller['items_sold'] );

		$second_page = $this->dispatch_report(
			array(
				'search'   => 'Kingston',
				'per_page' => 100,
				'page'     => 2,
			)
		);

		$this->assertEquals( 200, $second_page->get_status() );
		$this->assertCount( 5, $second_page->get_data() );
	}

	/**
	 * @testdox Should order products tied on the sorting column by ID, so paging stays stable.
	 *
	 * A product without sales ties with every other one on every column the report can be ordered
	 * by, and the database is free to resolve a tie differently for each page, so a product comes
	 * back on two pages while another is never reached.
	 */
	public function test_get_reports_orders_products_tied_on_the_sorting_column_by_id() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// These only need to match the search, so the full product CRUD would be wasted work.
		$without_sales = array();
		for ( $i = 0; $i < 12; $i++ ) {
			$without_sales[] = wp_insert_post(
				array(
					'post_title'  => sprintf( 'Kingston Widget %03d', $i ),
					'post_type'   => 'product',
					'post_status' => 'publish',
				)
			);
		}

		$with_sales = $this->create_product( 'Kingston Widget 999' );
		$this->create_completed_order( $with_sales );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		sort( $without_sales );

		// The only product with sales leads, and the rest tie on every column being tested.
		$expected = array_merge( array( $with_sales->get_id() ), $without_sales );

		// Both filters resolve to the same set of products through the same code path.
		$filters = array(
			'search'   => array( 'search' => 'Kingston' ),
			'products' => array( 'products' => implode( ',', $expected ) ),
		);

		foreach ( $filters as $filter_name => $filter ) {
			foreach ( array( 'items_sold', 'net_revenue', 'date' ) as $orderby ) {
				$paged_through = array();

				for ( $page = 1; $page <= 3; $page++ ) {
					$response = $this->dispatch_report(
						array_merge(
							$filter,
							array(
								'per_page' => 5,
								'page'     => $page,
								'orderby'  => $orderby,
								'order'    => 'desc',
							)
						)
					);

					$this->assertEquals( 200, $response->get_status() );

					$paged_through = array_merge( $paged_through, array_column( $response->get_data(), 'product_id' ) );
				}

				$this->assertEquals(
					$expected,
					$paged_through,
					"Filtering by {$filter_name} and ordering by {$orderby} should page through every product once, ties in ID order"
				);
			}
		}
	}

	/**
	 * @testdox Should break a tie on the sorting column by numeric ID, not by ID as text.
	 *
	 * The virtual table the report joins its filtered IDs through types that column as text, so
	 * without a cast product 100 sorts before product 99 and a page boundary lands mid-run.
	 */
	public function test_get_reports_breaks_ties_by_numeric_product_id() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Rows come from the filtered ID list, so these do not have to be products that exist. They
		// straddle a digit boundary, which is where a text sort and a numeric one disagree.
		$response = $this->dispatch_report(
			array(
				'products' => '1000001,999998,1000000,999999,999997',
				'per_page' => 10,
				'orderby'  => 'items_sold',
				'order'    => 'desc',
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array( 999997, 999998, 999999, 1000000, 1000001 ),
			array_column( $response->get_data(), 'product_id' )
		);
	}

	/**
	 * @testdox Should match products by SKU as well as by title.
	 */
	public function test_get_reports_search_param_matches_sku() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$match    = $this->create_product( 'Unrelated Thing', 'KINGSTON-1' );
		$no_match = $this->create_product( 'Another Thing', 'OTHER-1' );

		$this->create_completed_order( $match );
		$this->create_completed_order( $no_match );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$reports = $this->dispatch_report( array( 'search' => 'KINGSTON-1' ) )->get_data();

		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( $match->get_id(), $reports[0]['product_id'] );
	}

	/**
	 * @testdox Should treat a multi word `search` term as a single term.
	 *
	 * WordPress splits a string argument on whitespace as well as commas, which would turn one
	 * multi word search into several single word ones.
	 */
	public function test_get_reports_search_param_keeps_multi_word_terms_intact() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$match    = $this->create_product( 'Blue Widget' );
		$no_match = $this->create_product( 'Blue Gadget' );

		$this->create_completed_order( $match );
		$this->create_completed_order( $no_match );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$reports = $this->dispatch_report( array( 'search' => 'Blue Widget' ) )->get_data();

		$this->assertEquals( 1, count( $reports ) );
		$this->assertEquals( $match->get_id(), $reports[0]['product_id'] );
	}

	/**
	 * @testdox Should narrow the `products` param with the `search` param rather than replace it.
	 */
	public function test_get_reports_search_param_intersects_with_products_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$in_both       = $this->create_product( 'Kingston Widget' );
		$search_only   = $this->create_product( 'Kingston Gadget' );
		$products_only = $this->create_product( 'Unrelated Thing' );

		$this->create_completed_order( $in_both );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$reports = $this->dispatch_report(
			array(
				'search'   => 'Kingston',
				'products' => $in_both->get_id() . ',' . $products_only->get_id(),
			)
		)->get_data();

		$reported_ids = array_column( $reports, 'product_id' );

		$this->assertEquals( array( $in_both->get_id() ), $reported_ids );
		$this->assertNotContains( $search_only->get_id(), $reported_ids );
		$this->assertNotContains( $products_only->get_id(), $reported_ids );
	}

	/**
	 * @testdox Should return an empty report when the `search` param matches nothing.
	 */
	public function test_get_reports_search_param_without_matches() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$product = $this->create_product( 'Kingston Widget' );
		$this->create_completed_order( $product );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->dispatch_report( array( 'search' => 'nothing matches this' ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( array(), $response->get_data() );
		$this->assertEquals( 0, $response->get_headers()['X-WP-Total'] );
	}

	/**
	 * @testdox Should report nothing when the `search` param is combined with a filter no product satisfies.
	 */
	public function test_get_reports_search_param_with_a_filter_no_product_satisfies() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$match = $this->create_product_with_id_1( 'Kingston Widget' );
		$this->create_completed_order( $match );

		$empty_category = wp_insert_term( 'Empty Category', 'product_cat' );
		$other_category = wp_insert_term( 'Other Category', 'product_cat' );

		$other_product = $this->create_product( 'Unrelated Thing' );
		wp_set_object_terms( $other_product->get_id(), array( $other_category['term_id'] ), 'product_cat' );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$empty_category_report = $this->dispatch_report(
			array(
				'search'     => 'Kingston',
				'categories' => (string) $empty_category['term_id'],
			)
		);

		$this->assertEquals( 200, $empty_category_report->get_status() );
		$this->assertSame( array(), $empty_category_report->get_data(), 'A category holding no product leaves the search nothing to match' );

		$disjoint_filters_report = $this->dispatch_report(
			array(
				'search'     => 'Kingston',
				'categories' => (string) $other_category['term_id'],
				'products'   => (string) $match->get_id(),
				'match'      => 'all',
			)
		);

		$this->assertEquals( 200, $disjoint_filters_report->get_status() );
		$this->assertSame( array(), $disjoint_filters_report->get_data(), 'A category and a product filter with no product in common leave the search nothing to match' );
	}

	/**
	 * @testdox Should leave out a product a scope on the product query excludes.
	 *
	 * A searched report joins every match onto the sales data, so a product a plugin scopes out
	 * would otherwise come back as a row of its own, name included.
	 */
	public function test_get_reports_search_param_honours_a_product_query_scope() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$vendor    = $this->factory->user->create( array( 'role' => 'editor' ) );
		$in_scope  = $this->create_product( 'Kingston Widget' );
		$out_scope = $this->create_product( 'Kingston Gadget' );

		wp_update_post(
			array(
				'ID'          => $in_scope->get_id(),
				'post_author' => $vendor,
			)
		);

		$this->create_completed_order( $in_scope );
		$this->create_completed_order( $out_scope );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$scope = function ( $query ) use ( $vendor ) {
			if ( 'product' === $query->get( 'post_type' ) ) {
				$query->set( 'author', $vendor );
			}
		};

		add_action( 'pre_get_posts', $scope );
		$response = $this->dispatch_report( array( 'search' => 'Kingston' ) );
		remove_action( 'pre_get_posts', $scope );

		$reports_by_id = array_column( $response->get_data(), null, 'product_id' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( $in_scope->get_id(), $reports_by_id );
		$this->assertArrayNotHasKey( $out_scope->get_id(), $reports_by_id, 'A product outside the scope should not be reported' );
		$this->assertEquals( 1, $response->get_headers()['X-WP-Total'], 'The row count should leave out the products the scope excludes' );
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

		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'net_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}

	/**
	 * Creates a simple product.
	 *
	 * @param string $name Product name.
	 * @param string $sku  Optional. Product SKU.
	 * @return WC_Product_Simple
	 */
	private function create_product( $name, $sku = '' ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( 25 );

		if ( '' !== $sku ) {
			$product->set_sku( $sku );
		}

		$product->save();

		return $product;
	}

	/**
	 * Creates a simple product with product ID 1.
	 *
	 * The filters resolve to `-1` when no product satisfies them and `absint()` reads that as
	 * product ID 1, so the wrong product is only reported when one has that ID.
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

	/**
	 * Creates a completed order containing four units of the given product.
	 *
	 * @param WC_Product $product Product to order.
	 * @return WC_Order
	 */
	private function create_completed_order( $product ) {
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		// $25 x 4.
		$order->set_total( 100 );
		$order->save();

		return $order;
	}

	/**
	 * Dispatches a request to the reports endpoint.
	 *
	 * @param array $query_params Query parameters.
	 * @return WP_REST_Response
	 */
	private function dispatch_report( $query_params ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( $query_params );

		return $this->server->dispatch( $request );
	}
}
