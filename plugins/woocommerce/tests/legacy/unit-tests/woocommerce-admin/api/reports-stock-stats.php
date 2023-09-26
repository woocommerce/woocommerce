<?php
/**
 * Reports Stock Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * Class WC_Admin_Tests_API_Reports_Stock_Stats
 */
class WC_Admin_Tests_API_Reports_Stock_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/stock/stats';

	/**
	 * Setup test reports stock data.
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
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$number_of_low_stock = 3;
		for ( $i = 1; $i <= $number_of_low_stock; $i++ ) {
			$low_stock = new WC_Product_Simple();
			$low_stock->set_name( "Test low stock {$i}" );
			$low_stock->set_regular_price( 5 );
			$low_stock->set_manage_stock( true );
			$low_stock->set_stock_quantity( 1 );
			$low_stock->set_stock_status( 'instock' );
			$low_stock->save();
		}

		$number_of_out_of_stock = 6;
		for ( $i = 1; $i <= $number_of_out_of_stock; $i++ ) {
			$out_of_stock = new WC_Product_Simple();
			$out_of_stock->set_name( "Test out of stock {$i}" );
			$out_of_stock->set_regular_price( 5 );
			$out_of_stock->set_stock_status( 'outofstock' );
			$out_of_stock->save();
		}

		$number_of_in_stock = 10;
		for ( $i = 1; $i <= $number_of_in_stock; $i++ ) {
			$in_stock = new WC_Product_Simple();
			$in_stock->set_name( "Test in stock {$i}" );
			$in_stock->set_regular_price( 25 );
			$in_stock->save();
		}

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'totals', $reports );
		$this->assertEquals( 19, $reports['totals']['products'] );
		$this->assertEquals( 6, $reports['totals']['outofstock'] );
		$this->assertEquals( 0, $reports['totals']['onbackorder'] );
		$this->assertEquals( 3, $reports['totals']['lowstock'] );
		$this->assertEquals( 13, $reports['totals']['instock'] );

		// Test backorder and cache update.
		$backorder_stock = new WC_Product_Simple();
		$backorder_stock->set_name( 'Test backorder' );
		$backorder_stock->set_regular_price( 5 );
		$backorder_stock->set_stock_status( 'onbackorder' );
		$backorder_stock->save();

		// Clear caches.
		delete_transient( 'wc_admin_stock_count_lowstock' );
		delete_transient( 'wc_admin_stock_count_outofstock' );
		delete_transient( 'wc_admin_stock_count_onbackorder' );
		delete_transient( 'wc_admin_stock_count_lowstock' );
		delete_transient( 'wc_admin_stock_count_instock' );
		delete_transient( 'wc_admin_product_count' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 20, $reports['totals']['products'] );
		$this->assertEquals( 6, $reports['totals']['outofstock'] );
		$this->assertEquals( 1, $reports['totals']['onbackorder'] );
		$this->assertEquals( 3, $reports['totals']['lowstock'] );
		$this->assertEquals( 13, $reports['totals']['instock'] );
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

		$this->assertCount( 1, $properties );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertCount( 5, $properties['totals']['properties'] );
		$this->assertArrayHasKey( 'products', $properties['totals']['properties'] );
		$this->assertArrayHasKey( 'outofstock', $properties['totals']['properties'] );
		$this->assertArrayHasKey( 'onbackorder', $properties['totals']['properties'] );
		$this->assertArrayHasKey( 'lowstock', $properties['totals']['properties'] );
		$this->assertArrayHasKey( 'instock', $properties['totals']['properties'] );
	}
}
