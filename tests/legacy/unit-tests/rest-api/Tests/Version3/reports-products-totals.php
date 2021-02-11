<?php
/**
 * Tests for the reports products totals REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class WC_Tests_API_Reports_Products_Totals extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
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
		$this->assertArrayHasKey( '/wc/v3/reports/products/totals', $routes );
	}

	/**
	 * Test getting all product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Install::create_terms();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/products/totals' ) );
		$report   = $response->get_data();
		$types    = wc_get_product_types();
		$terms    = get_terms(
			array(
				'taxonomy'   => 'product_type',
				'hide_empty' => false,
			)
		);
		$data     = array();

		foreach ( $terms as $product_type ) {
			if ( ! isset( $types[ $product_type->name ] ) ) {
				continue;
			}

			$data[] = array(
				'slug'  => $product_type->name,
				'name'  => $types[ $product_type->name ],
				'total' => (int) $product_type->count,
			);
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $types ), count( $report ) );
		$this->assertEquals( $data, $report );
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/products/totals' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test the product review schema.
	 *
	 * @since 3.5.0
	 */
	public function test_product_review_schema() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/products/totals' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'total', $properties );
	}
}
