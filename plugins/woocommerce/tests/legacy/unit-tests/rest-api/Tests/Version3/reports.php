<?php
/**
 * Tests for the reports REST API.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

/**
 * WC_Tests_API_Reports.
 */
class WC_Tests_API_Reports extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server and administrator.
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
	 * @testdox Should expose the complete ordered V3 report registry through the registered route.
	 */
	public function test_reports_index_contract(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/reports', $routes );

		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports' ) );
		$reports  = $response->get_data();

		$expected_slugs = array(
			'sales',
			'top_sellers',
			'orders/totals',
			'products/totals',
			'customers/totals',
			'coupons/totals',
			'reviews/totals',
			'categories/totals',
			'tags/totals',
			'attributes/totals',
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected_slugs, wp_list_pluck( $reports, 'slug' ) );
		$this->assertCount( count( $expected_slugs ), $reports );

		foreach ( $reports as $report ) {
			$this->assertIsString( $report['description'] );
			$this->assertNotSame( '', $report['description'] );
			$this->assertSame( rest_url( '/wc/v3/reports/' . $report['slug'] ), $report['_links']['self'][0]['href'] );
			$this->assertSame( rest_url( 'wc/v3/reports' ), $report['_links']['collection'][0]['href'] );
		}

		$schema_response = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', '/wc/v3/reports' ) );
		$properties      = $schema_response->get_data()['schema']['properties'];

		$this->assertSame( array( 'slug', 'description' ), array_keys( $properties ) );
		$this->assertSame( 'string', $properties['slug']['type'] );
		$this->assertSame( 'string', $properties['description']['type'] );

		wp_set_current_user( 0 );
		$anonymous_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports' ) );

		$this->assertSame( 401, $anonymous_response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $anonymous_response->get_data()['code'] );
	}
}
