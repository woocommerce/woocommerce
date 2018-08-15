<?php
/**
 * Reports Categories REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Reports_Categories extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/reports/categories';

	/**
	 * Setup test reports categories data.
	 *
	 * @since 3.5.0
	 */
	public function setUp() {
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
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

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
		$this->assertArrayHasKey( 'category_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'gross_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'products_count', $properties );
	}
}
