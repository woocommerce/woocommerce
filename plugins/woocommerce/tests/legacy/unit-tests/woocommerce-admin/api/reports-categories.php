<?php
/**
 * Reports Categories REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\CategoryLookup;

/**
 * Class WC_Admin_Tests_API_Reports_Categories
 */
class WC_Admin_Tests_API_Reports_Categories extends WC_REST_Unit_Test_Case {
	/** A mixed-currency category keeps counts but cannot export a partial revenue amount. */
	public function test_mixed_currency_category_export(): void {
		$previous_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'EUR' );
		$product  = WC_Helper_Product::create_simple_product();
		$category = wp_insert_term( 'Currency fixture', 'product_cat' );
		$this->assertNotWPError( $category );
		$category_id    = $category['term_id'];
		$other_category = wp_insert_term( 'Unselected currency fixture', 'product_cat' );
		$this->assertNotWPError( $other_category );
		$other_category_id = $other_category['term_id'];
		wp_set_object_terms( $product->get_id(), array( $category_id, $other_category_id ), 'product_cat' );
		$orders = array();
		try {
			foreach ( array( 'EUR', 'GBP' ) as $currency ) {
				$order    = wc_create_order();
				$orders[] = $order;
				$order->set_currency( $currency );
				$order->add_product( $product, 1 );
				$order->calculate_totals();
				$order->set_status( 'completed' );
				$order->save();
				\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order->get_id() );
				\Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_order_products( $order->get_id() );
			}
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
			$data = ( new \Automattic\WooCommerce\Admin\API\Reports\Categories\DataStore() )->get_data(
				array(
					'category_includes' => array( $category_id ),
					'orderby'           => 'items_sold',
					'extended_info'     => true,
				)
			);
			$this->assertCount( 1, $data->data );
			$row = reset( $data->data );
			$this->assertSame( $category_id, $row['category_id'] );
			$this->assertSame( 2, $row['items_sold'] );
			$this->assertSame( 1, $row['reporting_missing_orders'] );
			$export = ( new \Automattic\WooCommerce\Admin\API\Reports\Categories\Controller() )->prepare_item_for_export( $row );
			$this->assertSame( 'Unavailable', $export['net_revenue'] );
			$exporter = new \Automattic\WooCommerce\Admin\ReportCSVExporter(
				'categories',
				array(
					'categories' => (string) $category_id,
					'orderby'    => 'items_sold',
				)
			);
			$exporter->set_filename( 'wc-category-currency-test-' . wp_generate_uuid4() );
			$path = \Automattic\WooCommerce\Admin\ReportCSVExporter::get_reports_directory() . $exporter->get_filename();
			try {
				$exporter->generate_file();
				$this->assertTrue( $exporter->export_file_exists() );
				ob_start();
				$exporter->stream_export_file();
				$csv = ob_get_clean();
				$this->assertSame( 1, substr_count( $csv, 'Unavailable' ), $csv );
				$lines = array_map( 'str_getcsv', preg_split( '/\r?\n/', trim( $csv ) ) );
				$this->assertCount( 2, $lines );
				$this->assertSame( 'Currency fixture', $lines[1][0] );
				$this->assertSame( '2', $lines[1][1] );
				$this->assertSame( 'Unavailable', $lines[1][2] );
			} finally {
				wp_delete_file( $path );
				wp_delete_file( $path . '.headers' );
			}
			$orders[1]->set_status( 'cancelled' );
			$orders[1]->save();
			\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $orders[1]->get_id() );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
			$native_exporter = new \Automattic\WooCommerce\Admin\ReportCSVExporter(
				'categories',
				array(
					'categories' => (string) $category_id,
					'orderby'    => 'items_sold',
				)
			);
			$native_exporter->set_filename( 'wc-category-native-test-' . wp_generate_uuid4() );
			$native_path = \Automattic\WooCommerce\Admin\ReportCSVExporter::get_reports_directory() . $native_exporter->get_filename();
			try {
				$native_exporter->generate_file();
				$this->assertTrue( $native_exporter->export_file_exists() );
				ob_start();
				$native_exporter->stream_export_file();
				$native_csv = ob_get_clean();
				$lines      = array_map( 'str_getcsv', preg_split( '/\r?\n/', trim( $native_csv ) ) );
				$this->assertCount( 2, $lines );
				$this->assertSame( 'Currency fixture', $lines[1][0] );
				$this->assertSame( '1', $lines[1][1] );
				$this->assertTrue( is_numeric( $lines[1][2] ) );
				$this->assertEquals( 10.0, (float) $lines[1][2] );
				$this->assertStringNotContainsString( 'Unavailable', $native_csv );
			} finally {
				wp_delete_file( $native_path );
				wp_delete_file( $native_path . '.headers' );
			}
		} finally {
			foreach ( $orders as $order ) {
				$order->delete( true );
			}
			$product->delete( true );
			wp_delete_term( $category_id, 'product_cat' );
			wp_delete_term( $other_category_id, 'product_cat' );
			update_option( 'woocommerce_currency', $previous_currency );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
		}
	}

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/categories';

	/**
	 * Setup test reports categories data.
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

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$category_report = reset( $reports );

		$this->assertEquals( $uncategorized_term->term_id, $category_report['category_id'] );
		$this->assertEquals( 4, $category_report['items_sold'] );
		$this->assertEquals( 1, $category_report['orders_count'] );
		$this->assertEquals( 1, $category_report['products_count'] );
		$this->assertArrayHasKey( '_links', $category_report );
		$this->assertArrayHasKey( 'category', $category_report['_links'] );
	}

	/**
	 * Test getting reports with the `categories` param.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_categories_param() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product 2' );
		$product->set_regular_price( 100 );
		$second_category_id = wp_create_category( 'Second Category' );
		$product->set_category_ids( array( $second_category_id ) );
		$product->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'categories' => $uncategorized_term->term_id . ',' . $second_category_id,
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$reports_by_category = array_column( $reports, null, 'category_id' );
		$this->assertArrayHasKey( $second_category_id, $reports_by_category );
		$this->assertArrayHasKey( $uncategorized_term->term_id, $reports_by_category );

		$category_report = $reports_by_category[ $second_category_id ];
		$this->assertEquals( 0, $category_report['items_sold'] );
		$this->assertEquals( 0, $category_report['orders_count'] );
		$this->assertEquals( 0, $category_report['products_count'] );
		$this->assertArrayHasKey( '_links', $category_report );
		$this->assertArrayHasKey( 'category', $category_report['_links'] );

		$category_report = $reports_by_category[ $uncategorized_term->term_id ];

		$this->assertEquals( 4, $category_report['items_sold'] );
		$this->assertEquals( 1, $category_report['orders_count'] );
		$this->assertEquals( 1, $category_report['products_count'] );
		$this->assertArrayHasKey( '_links', $category_report );
		$this->assertArrayHasKey( 'category', $category_report['_links'] );
	}

	/**
	 * Test getting reports with sorting.
	 *
	 * @since 3.9.0
	 */
	public function test_get_reports_categories_sort() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$customer = WC_Helper_Customer::create_customer( 'sortcustomer', 'wcadminuser2', 'sortcustomer@woo.local' );
		$order    = WC_Helper_Order::create_order( $customer->get_id(), $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product 2' );
		$product->set_regular_price( 100 );
		$second_category    = wp_insert_term( 'Second Category', 'product_cat' );
		$second_category_id = $second_category['term_id'];
		$product->set_category_ids( array( $second_category_id ) );
		$product->save();

		$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 400 ); // $100 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		$params             = array(
			'orderby'       => 'category',
			'order'         => 'desc',
			'interval'      => 'week',
			'extended_info' => true,
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( $params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$category_report = reset( $reports );

		$this->assertEquals( $uncategorized_term->term_id, $category_report['category_id'] );

		$category_report = next( $reports );

		$this->assertEquals( $second_category_id, $category_report['category_id'] );

		$params['order'] = 'asc';
		$request         = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( $params );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$category_report = reset( $reports );

		$this->assertEquals( $second_category_id, $category_report['category_id'] );

		$category_report = next( $reports );

		$this->assertEquals( $uncategorized_term->term_id, $category_report['category_id'] );
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

		$this->assertEquals( 7, count( $properties ) );
		$this->assertSame( 'integer', $properties['reporting_missing_orders']['type'] );
		$this->assertArrayHasKey( 'category_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'net_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'products_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
