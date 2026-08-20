<?php
/**
 * Reports Stock Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Stock_Stats
 */
class WC_Admin_Tests_API_Reports_Stock_Stats extends WC_REST_Unit_Test_Case {
	use WC_Stock_Report_Fixtures;

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

		$this->clear_stock_count_caches();

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
	 * @testdox Variations owning the stock replace their parent in the totals.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/32134
	 */
	public function test_a_parent_owning_no_stock_is_left_out_of_the_totals() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$this->create_stock_report_variable_product(
			'Variations manage stock, parent does not',
			array(
				array(
					'manage_stock'   => true,
					'stock_quantity' => 25,
				),
				array(
					'manage_stock'   => true,
					'stock_quantity' => 25,
				),
			)
		);

		$this->clear_stock_count_caches();

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// The two variations, without their parent.
		$this->assertEquals( 2, $reports['totals']['products'] );
		$this->assertEquals( 2, $reports['totals'][ ProductStockStatus::IN_STOCK ] );
	}

	/**
	 * @testdox A parent managing stock at product level replaces its variations in the totals.
	 */
	public function test_a_stock_managing_parent_replaces_its_variations_in_the_totals() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();
		update_option( 'woocommerce_notify_low_stock_amount', 5 );

		list( $variable ) = $this->create_stock_report_variable_product( 'Parent manages stock, variations inherit', array( array(), array() ) );
		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 3 );
		$variable->save();

		$this->clear_stock_count_caches();

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// The parent, without the variations that only repeat its quantity.
		$this->assertEquals( 1, $reports['totals']['products'] );
		$this->assertEquals( 1, $reports['totals'][ ProductStockStatus::IN_STOCK ] );
		$this->assertEquals( 1, $reports['totals'][ ProductStockStatus::LOW_STOCK ] );
	}

	/**
	 * @testdox Variations of an unpublished parent are counted, since the parent itself is not.
	 */
	public function test_variations_of_an_unpublished_stock_managing_parent_are_counted() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		list( $variable ) = $this->create_stock_report_variable_product( 'Draft parent manages stock, variations inherit', array( array(), array() ) );
		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 8 );
		$variable->set_status( ProductStatus::DRAFT );
		$variable->save();

		$this->clear_stock_count_caches();

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// The two variations. Their parent is out of the report on its status alone.
		$this->assertEquals( 2, $reports['totals']['products'] );
		$this->assertEquals( 2, $reports['totals'][ ProductStockStatus::IN_STOCK ] );
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
