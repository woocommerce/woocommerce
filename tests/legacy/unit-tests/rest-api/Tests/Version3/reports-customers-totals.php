<?php
/**
 * Tests for the reports customers totals REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class WC_Tests_API_Reports_Customers_Totals extends WC_REST_Unit_Test_Case {

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
		$this->assertArrayHasKey( '/wc/v3/reports/customers/totals', $routes );
	}

	/**
	 * Test getting all product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );

		$response        = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/customers/totals' ) );
		$report          = $response->get_data();
		$users_count     = count_users();
		$total_customers = 0;

		foreach ( $users_count['avail_roles'] as $role => $total ) {
			if ( in_array( $role, array( 'administrator', 'shop_manager' ), true ) ) {
				continue;
			}

			$total_customers += (int) $total;
		}

		$customers_query = new WP_User_Query(
			array(
				'role__not_in' => array( 'administrator', 'shop_manager' ),
				'number'       => 0,
				'fields'       => 'ID',
				'count_total'  => true,
				'meta_query'   => array( // WPCS: slow query ok.
					array(
						'key'     => 'paying_customer',
						'value'   => 1,
						'compare' => '=',
					),
				),
			)
		);

		$total_paying = (int) $customers_query->get_total();

		$data = array(
			array(
				'slug'  => 'paying',
				'name'  => __( 'Paying customer', 'woocommerce' ),
				'total' => $total_paying,
			),
			array(
				'slug'  => 'non_paying',
				'name'  => __( 'Non-paying customer', 'woocommerce' ),
				'total' => $total_customers - $total_paying,
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $report ) );
		$this->assertEquals( $data, $report );
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/customers/totals' ) );
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
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/customers/totals' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'total', $properties );
	}
}
