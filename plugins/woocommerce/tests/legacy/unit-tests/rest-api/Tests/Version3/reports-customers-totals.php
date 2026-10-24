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
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/reports/customers/totals', $routes );
	}

	/**
	 * Fetch the endpoint and index the totals by slug.
	 *
	 * @return array
	 */
	private function get_totals_by_slug() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/customers/totals' ) );

		$this->assertEquals( 200, $response->get_status() );

		return wp_list_pluck( $response->get_data(), 'total', 'slug' );
	}

	/**
	 * Test getting the customer totals.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/customers/totals' ) );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $report ) );
		$this->assertEquals( 'paying', $report[0]['slug'] );
		$this->assertEquals( 'Paying customer', $report[0]['name'] );
		$this->assertEquals( 'non_paying', $report[1]['slug'] );
		$this->assertEquals( 'Non-paying customer', $report[1]['name'] );
	}

	/**
	 * Only customers whose paying_customer meta is exactly "1" count as paying, and
	 * administrators and shop managers stay out of both totals.
	 */
	public function test_get_reports_counts_paying_customers() {
		wp_set_current_user( $this->user );

		// Read the totals first so the users added below have to invalidate them.
		$before = $this->get_totals_by_slug();

		$paying = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $paying, 'paying_customer', 1 );

		// Stored as a string and compared as one, so a padded value is not a match.
		$padded = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $padded, 'paying_customer', '01' );

		$not_paying = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_meta( $not_paying, 'paying_customer', 0 );

		// No paying_customer meta at all.
		$this->factory->user->create( array( 'role' => 'customer' ) );

		$paying_manager = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		update_user_meta( $paying_manager, 'paying_customer', 1 );

		$paying_admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		update_user_meta( $paying_admin, 'paying_customer', 1 );

		$after = $this->get_totals_by_slug();

		// Only the one customer with paying_customer set to 1.
		$this->assertSame( 1, $after['paying'] - $before['paying'] );

		// The other three customers; the shop manager and the administrator are excluded by role.
		$this->assertSame( 3, $after['non_paying'] - $before['non_paying'] );
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
