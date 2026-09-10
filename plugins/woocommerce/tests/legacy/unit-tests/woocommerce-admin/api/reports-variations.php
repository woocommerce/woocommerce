<?php
/**
 * Reports Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Variations
 */
class WC_Admin_Tests_API_Reports_Variations extends WC_REST_Unit_Test_Case {
	/** Mixed currencies must not export an unqualified variation revenue sum. */
	public function test_mixed_currency_variation_export(): void {
		$previous_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'EUR' );
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->product->get_id() );
		$variation->set_regular_price( 25 );
		$variation->set_sku( 'currency-variation-' . wp_generate_uuid4() );
		$variation->save();
		$orders = array();
		try {
			foreach ( array( 'EUR', 'GBP' ) as $currency ) {
				$order    = wc_create_order();
				$orders[] = $order;
				$order->set_currency( $currency );
				$order->add_product( $variation, 1 );
				$order->calculate_totals();
				$order->set_status( 'completed' );
				$order->save();
				\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order->get_id() );
				\Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_order_products( $order->get_id() );
			}
			wp_set_current_user( $this->user );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
			$request = new WP_REST_Request( 'GET', $this->endpoint );
			$request->set_param( 'products', array( $this->product->get_id() ) );
			$request->set_param( 'extended_info', true );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$rows = $response->get_data();
			$this->assertCount( 1, $rows );
			$this->assertSame( $variation->get_id(), $rows[0]['variation_id'] );
			$this->assertSame( 2, $rows[0]['items_sold'] );
			$this->assertSame( 1, $rows[0]['reporting_missing_orders'] );
			$export = ( new \Automattic\WooCommerce\Admin\API\Reports\Variations\Controller() )->prepare_item_for_export( $rows[0] );
			$this->assertSame( 'Unavailable', $export['net_revenue'] );
			$check_csv = function ( string $expected_amount, string $expected_count ) use ( $variation ) {
				$exporter = new \Automattic\WooCommerce\Admin\ReportCSVExporter(
					'variations',
					array(
						'products' => (string) $this->product->get_id(),
						'orderby'  => 'items_sold',
					)
				);
				$exporter->set_filename( 'wc-variations-currency-' . wp_generate_uuid4() );
				$path = \Automattic\WooCommerce\Admin\ReportCSVExporter::get_reports_directory() . $exporter->get_filename();
				try {
					$exporter->generate_file();
					$this->assertTrue( $exporter->export_file_exists() );
					ob_start();
					$exporter->stream_export_file();
					$csv   = ob_get_clean();
					$lines = array_map( 'str_getcsv', preg_split( '/\r?\n/', trim( $csv ) ) );
					$this->assertCount( 2, $lines );
					$this->assertSame( $this->product->get_name(), $lines[1][0] );
					$this->assertSame( $variation->get_sku(), $lines[1][1] );
					$this->assertSame( $expected_count, $lines[1][2] );
					if ( 'Unavailable' === $expected_amount ) {
						$this->assertSame( $expected_amount, $lines[1][3] );
					} else {
						$this->assertTrue( is_numeric( $lines[1][3] ) );
						$this->assertEquals( (float) $expected_amount, (float) $lines[1][3] );
					}
				} finally {
					wp_delete_file( $path );
					wp_delete_file( $path . '.headers' );
				}
			};
			$check_csv( 'Unavailable', '2' );
			// A complete native-only cohort must recover its amount, not remain unavailable.
			$orders[1]->set_status( 'cancelled' );
			$orders[1]->save();
			\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $orders[1]->get_id() );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$rows = $response->get_data();
			$this->assertCount( 1, $rows );
			$this->assertSame( 0, $rows[0]['reporting_missing_orders'] );
			$this->assertSame( 1, $rows[0]['items_sold'] );
			$export = ( new \Automattic\WooCommerce\Admin\API\Reports\Variations\Controller() )->prepare_item_for_export( $rows[0] );
			$this->assertEquals( 25.0, $export['net_revenue'] );
			$check_csv( '25.0', '1' );
		} finally {
			foreach ( $orders as $order ) {
				$order->delete( true );
			}
			$variation->delete( true );
			update_option( 'woocommerce_currency', $previous_currency );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
		}
	}

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/variations';

	/**
	 * Setup test reports products data.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product = new WC_Product_Variable();
		$this->product->set_name( 'Variable Product' );
		$this->product->save();

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
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->product->get_id() );
		$variation->set_name( 'Test Variation' );
		$variation->set_regular_price( 25 );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$variation_report = reset( $reports );

		$this->assertEquals( $variation->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 4, $variation_report['items_sold'] );
		$this->assertEquals( 1, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );
	}

	/**
	 * Test to confirm that simple products are excluded from the variations reports by default
	 */
	public function test_simple_products_excluded_from_variations_reports_by_default() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = WC_Helper_Product::create_simple_product();

		$order = WC_Helper_Order::create_order( 1, $simple_product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 15 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_includes' => $simple_product->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $reports ) );
	}

	/**
	 * Test getting reports with the `variations` param.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_variations_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->product->get_id() );
		$variation->set_name( 'Test Variation' );
		$variation->set_regular_price( 25 );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_parent_id( $this->product->get_id() );
		$variation_2->set_name( 'Test Variation 2' );
		$variation_2->set_regular_price( 100 );
		$variation_2->set_attributes( array( 'color' => 'red' ) );
		$variation_2->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'product_includes' => $variation->get_parent_id(),
				'variations'       => $variation->get_id() . ',' . $variation_2->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$variation_report = reset( $reports );

		$this->assertEquals( $variation->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 4, $variation_report['items_sold'] );
		$this->assertEquals( 1, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );

		$variation_report = next( $reports );

		$this->assertEquals( $variation_2->get_id(), $variation_report['variation_id'] );
		$this->assertEquals( 0, $variation_report['items_sold'] );
		$this->assertEquals( 0, $variation_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $variation_report );
		$this->assertArrayHasKey( 'extended_info', $variation_report );
		$this->assertArrayHasKey( 'product', $variation_report['_links'] );
		$this->assertArrayHasKey( 'variation', $variation_report['_links'] );
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
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'variation_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'net_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
