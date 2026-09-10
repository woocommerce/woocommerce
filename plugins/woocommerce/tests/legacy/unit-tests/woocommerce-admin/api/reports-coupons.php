<?php
/**
 * Reports Coupons REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Coupons
 */
class WC_Admin_Tests_API_Reports_Coupons extends WC_REST_Unit_Test_Case {
	/** Coupon usage counts remain valid when the discount currencies cannot be combined. */
	public function test_mixed_currency_coupon_export(): void {
		$previous_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'EUR' );
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon( 'currency-' . wp_generate_uuid4() );
		$orders  = array();
		try {
			foreach ( array( 'EUR', 'GBP' ) as $currency ) {
				$order    = wc_create_order();
				$orders[] = $order;
				$order->set_currency( $currency );
				$order->add_product( $product, 1 );
				$this->assertTrue( $order->apply_coupon( $coupon ) );
				$order->calculate_totals();
				$order->set_status( 'completed' );
				$order->save();
				\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order->get_id() );
				\Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_order_coupons( $order->get_id() );
			}
			wp_set_current_user( $this->user );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
			$request = new WP_REST_Request( 'GET', $this->endpoint );
			$request->set_param( 'coupons', array( $coupon->get_id() ) );
			$request->set_param( 'extended_info', true );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$rows = $response->get_data();
			$this->assertCount( 1, $rows );
			$this->assertSame( $coupon->get_id(), $rows[0]['coupon_id'] );
			$this->assertSame( 2, $rows[0]['orders_count'] );
			$this->assertSame( 1, $rows[0]['reporting_missing_orders'] );
			$export = ( new \Automattic\WooCommerce\Admin\API\Reports\Coupons\Controller() )->prepare_item_for_export( $rows[0] );
			$this->assertSame( 'Unavailable', $export['amount'] );
			$check_csv = function ( string $expected_amount, string $expected_count ) use ( $coupon ) {
				$exporter = new \Automattic\WooCommerce\Admin\ReportCSVExporter(
					'coupons',
					array(
						'coupons' => (string) $coupon->get_id(),
						'orderby' => 'orders_count',
					)
				);
				$exporter->set_filename( 'wc-coupons-currency-' . wp_generate_uuid4() );
				$path = \Automattic\WooCommerce\Admin\ReportCSVExporter::get_reports_directory() . $exporter->get_filename();
				try {
					$exporter->generate_file();
					$this->assertTrue( $exporter->export_file_exists() );
					ob_start();
					$exporter->stream_export_file();
					$csv   = ob_get_clean();
					$lines = array_map( 'str_getcsv', preg_split( '/\r?\n/', trim( $csv ) ) );
					$this->assertCount( 2, $lines );
					$this->assertSame( $coupon->get_code(), $lines[1][0] );
					$this->assertSame( $expected_count, $lines[1][1] );
					if ( 'Unavailable' === $expected_amount ) {
						$this->assertSame( $expected_amount, $lines[1][2] );
					} else {
						$this->assertTrue( is_numeric( $lines[1][2] ) );
						$this->assertEquals( (float) $expected_amount, (float) $lines[1][2] );
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
			$this->assertSame( 1, $rows[0]['orders_count'] );
			$export = ( new \Automattic\WooCommerce\Admin\API\Reports\Coupons\Controller() )->prepare_item_for_export( $rows[0] );
			$this->assertEquals( 1.0, $export['amount'] );
			$check_csv( '1.0', '1' );
		} finally {
			foreach ( $orders as $order ) {
				$order->delete( true );
			}
			$product->delete( true );
			$coupon->delete( true );
			update_option( 'woocommerce_currency', $previous_currency );
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
		}
	}

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/coupons';

	/**
	 * Setup test reports products data.
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
	 */
	public function test_register_routes() {
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting basic reports.
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order without coupon.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( OrderStatus::COMPLETED );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( OrderStatus::COMPLETED );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$coupon_reports = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $coupon_reports ) );

		$this->assertEquals( $coupon_2->get_id(), $coupon_reports[0]['coupon_id'] );
		$this->assertEquals( 1 * $coupon_2_amount, $coupon_reports[0]['amount'] );
		$this->assertEquals( 1, $coupon_reports[0]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[0] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[0]['_links'] );

		$this->assertEquals( $coupon_1->get_id(), $coupon_reports[1]['coupon_id'] );
		$this->assertEquals( 2 * $coupon_1_amount, $coupon_reports[1]['amount'] );
		$this->assertEquals( 2, $coupon_reports[1]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[1] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[1]['_links'] );
	}

	/**
	 * Test getting basic reports with the `coupons` param.
	 */
	public function test_get_reports_coupons_param() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( OrderStatus::COMPLETED );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'coupons' => $coupon_1->get_id() . ',' . $coupon_2->get_id(),
			)
		);
		$response       = $this->server->dispatch( $request );
		$coupon_reports = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $coupon_reports ) );

		$this->assertEquals( $coupon_2->get_id(), $coupon_reports[0]['coupon_id'] );
		$this->assertEquals( 0, $coupon_reports[0]['amount'] );
		$this->assertEquals( 0, $coupon_reports[0]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[0] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[0]['_links'] );

		$this->assertEquals( $coupon_1->get_id(), $coupon_reports[1]['coupon_id'] );
		$this->assertEquals( $coupon_1_amount, $coupon_reports[1]['amount'] );
		$this->assertEquals( 1, $coupon_reports[1]['orders_count'] );
		$this->assertArrayHasKey( '_links', $coupon_reports[1] );
		$this->assertArrayHasKey( 'coupon', $coupon_reports[1]['_links'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 */
	public function test_reports_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 5, count( $properties ) );
		$this->assertSame( 'integer', $properties['reporting_missing_orders']['type'] );
		$this->assertArrayHasKey( 'coupon_id', $properties );
		$this->assertArrayHasKey( 'amount', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
