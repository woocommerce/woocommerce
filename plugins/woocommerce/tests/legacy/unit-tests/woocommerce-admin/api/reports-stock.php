<?php
/**
 * Reports Stock REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Stock
 */
class WC_Admin_Tests_API_Reports_Stock extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/stock';

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

		// Populate all of the data.
		$low_stock = new WC_Product_Simple();
		$low_stock->set_name( 'Test low stock' );
		$low_stock->set_regular_price( 5 );
		$low_stock->set_manage_stock( true );
		$low_stock->set_stock_quantity( 1 );
		$low_stock->set_stock_status( ProductStockStatus::IN_STOCK );
		$low_stock->save();

		$out_of_stock = new WC_Product_Simple();
		$out_of_stock->set_name( 'Test out of stock' );
		$out_of_stock->set_regular_price( 5 );
		$out_of_stock->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$out_of_stock->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( $low_stock->get_id(), $reports[0]['id'] );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $reports[0]['stock_status'] );
		$this->assertEquals( 1, $reports[0]['stock_quantity'] );
		$this->assertArrayHasKey( '_links', $reports[0] );
		$this->assertArrayHasKey( 'product', $reports[0]['_links'] );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'type', ProductStockStatus::LOW_STOCK );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
	}

	/**
	 * A variable parent holds no sellable stock of its own, so only its variations belong in the report.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/32134
	 */
	public function test_variable_parent_products_are_excluded() {
		wp_set_current_user( $this->user );

		$variable = $this->create_variable_product( 25 );

		// Stock managed at product level, with none left, is what made the parent claim to be out of
		// stock while its variations were in stock.
		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 0 );
		$variable->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$reported_ids = wp_list_pluck( $reports, 'id' );
		$this->assertNotContains( $variable->get_id(), $reported_ids, 'The variable parent should not be reported.' );
		foreach ( $variable->get_children() as $variation_id ) {
			$this->assertContains( $variation_id, $reported_ids, 'Each variation should still be reported.' );
		}
	}

	/**
	 * A variable product with no variations has nothing else representing it, so it stays in the report.
	 */
	public function test_variable_product_without_variations_is_included() {
		wp_set_current_user( $this->user );

		$variable = new WC_Product_Variable();
		$variable->set_name( 'Variable without variations' );
		$variable->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', (string) $variable->get_id() );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $variable->get_id(), $reports[0]['id'] );
	}

	/**
	 * Stores that want the previous listing back can opt out of the exclusion.
	 */
	public function test_variable_parent_products_can_be_restored_by_filter() {
		wp_set_current_user( $this->user );

		$variable = $this->create_variable_product( 25 );

		add_filter( 'woocommerce_analytics_stock_report_exclude_variable_parents', '__return_false' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', (string) $variable->get_id() );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		remove_filter( 'woocommerce_analytics_stock_report_exclude_variable_parents', '__return_false' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $variable->get_id(), $reports[0]['id'] );
	}

	/**
	 * Create a published variable product with two variations that manage their own stock.
	 *
	 * @param int $variation_stock Stock quantity to give each variation.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product( $variation_stock ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'Small', 'Large' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$variable = new WC_Product_Variable();
		$variable->set_name( 'Test variable product' );
		$variable->set_attributes( array( $attribute ) );
		$variable->save();

		foreach ( array( 'Small', 'Large' ) as $option ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $variable->get_id() );
			$variation->set_attributes( array( 'size' => $option ) );
			$variation->set_regular_price( '10' );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( $variation_stock );
			$variation->save();
		}

		return new WC_Product_Variable( $variable->get_id() );
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

		$this->assertEquals( 7, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'sku', $properties );
		$this->assertArrayHasKey( 'stock_status', $properties );
		$this->assertArrayHasKey( 'stock_quantity', $properties );
		$this->assertArrayHasKey( 'manage_stock', $properties );
	}
}
