<?php
/**
 * Reports Stock Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Enums\ProductStockStatus;

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
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
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
		$this->create_stock_products( $number_of_low_stock, ProductStockStatus::IN_STOCK, 1 );

		$number_of_out_of_stock = 6;
		$this->create_stock_products( $number_of_out_of_stock, ProductStockStatus::OUT_OF_STOCK );

		$number_of_in_stock = 10;
		$this->create_stock_products( $number_of_in_stock, ProductStockStatus::IN_STOCK );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'totals', $reports );
		$this->assertEquals( 19, $reports['totals']['products'] );
		$this->assertEquals( 6, $reports['totals'][ ProductStockStatus::OUT_OF_STOCK ] );
		$this->assertEquals( 0, $reports['totals'][ ProductStockStatus::ON_BACKORDER ] );
		$this->assertEquals( 3, $reports['totals'][ ProductStockStatus::LOW_STOCK ] );
		$this->assertEquals( 13, $reports['totals'][ ProductStockStatus::IN_STOCK ] );

		// Test backorder and cache update. Save a real product so the
		// production lookup-table sync-on-save path is exercised as well.
		WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::ON_BACKORDER,
			)
		);

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
		$this->assertEquals( 6, $reports['totals'][ ProductStockStatus::OUT_OF_STOCK ] );
		$this->assertEquals( 1, $reports['totals'][ ProductStockStatus::ON_BACKORDER ] );
		$this->assertEquals( 3, $reports['totals'][ ProductStockStatus::LOW_STOCK ] );
		$this->assertEquals( 13, $reports['totals'][ ProductStockStatus::IN_STOCK ] );
	}

	/**
	 * Create published products with the lookup data consumed by the stock stats queries.
	 *
	 * @param int        $count          Number of products to create.
	 * @param string     $stock_status   Product stock status.
	 * @param float|null $stock_quantity Product stock quantity.
	 */
	private function create_stock_products( $count, $stock_status, $stock_quantity = null ) {
		global $wpdb;

		$product_ids = $this->factory->post->create_many(
			$count,
			array(
				'post_type' => 'product',
			)
		);
		$rows        = array();

		foreach ( $product_ids as $product_id ) {
			$rows[] = null === $stock_quantity
				? $wpdb->prepare( '(%d, NULL, %s)', $product_id, $stock_status )
				: $wpdb->prepare( '(%d, %f, %s)', $product_id, $stock_quantity, $stock_status );
		}

		$query  = $wpdb->prepare( 'INSERT INTO %i ( product_id, stock_quantity, stock_status ) VALUES ', $wpdb->wc_product_meta_lookup );
		$query .= implode( ', ', $rows );

		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Table and row values are prepared above.
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
		$this->assertArrayHasKey( ProductStockStatus::OUT_OF_STOCK, $properties['totals']['properties'] );
		$this->assertArrayHasKey( ProductStockStatus::ON_BACKORDER, $properties['totals']['properties'] );
		$this->assertArrayHasKey( ProductStockStatus::LOW_STOCK, $properties['totals']['properties'] );
		$this->assertArrayHasKey( ProductStockStatus::IN_STOCK, $properties['totals']['properties'] );
	}
}
