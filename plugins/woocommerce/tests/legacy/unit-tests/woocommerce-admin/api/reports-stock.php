<?php
/**
 * Reports Stock REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

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
		$low_stock->set_stock_status( 'instock' );
		$low_stock->save();

		$out_of_stock = new WC_Product_Simple();
		$out_of_stock->set_name( 'Test out of stock' );
		$out_of_stock->set_regular_price( 5 );
		$out_of_stock->set_stock_status( 'outofstock' );
		$out_of_stock->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( $low_stock->get_id(), $reports[0]['id'] );
		$this->assertEquals( 'instock', $reports[0]['stock_status'] );
		$this->assertEquals( 1, $reports[0]['stock_quantity'] );
		$this->assertArrayHasKey( '_links', $reports[0] );
		$this->assertArrayHasKey( 'product', $reports[0]['_links'] );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'type', 'lowstock' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
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
