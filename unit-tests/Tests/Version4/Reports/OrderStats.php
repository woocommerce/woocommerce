<?php
/**
 * Reports Orders Stats REST API Test
 *
 * @package WooCommerce\Tests\API
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4\Reports;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Unit_Test_Case;
use \WP_REST_Request;

/**
 * Class OrderStats
 */
class OrderStats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/orders/stats';

	/**
	 * Setup test reports orders data.
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
		wp_set_current_user( $this->user );

		// @todo Update after report interface is done.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) ); // totals and intervals.
		// @todo Update results after implement report interface.
		// $this->assertEquals( array(), $reports ); // @codingStandardsIgnoreLine.
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

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 11, count( $totals ) );
		$this->assertArrayHasKey( 'net_revenue', $totals );
		$this->assertArrayHasKey( 'avg_order_value', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'avg_items_per_order', $totals );
		$this->assertArrayHasKey( 'num_items_sold', $totals );
		$this->assertArrayHasKey( 'coupons', $totals );
		$this->assertArrayHasKey( 'coupons_count', $totals );
		$this->assertArrayHasKey( 'num_returning_customers', $totals );
		$this->assertArrayHasKey( 'num_new_customers', $totals );
		$this->assertArrayHasKey( 'products', $totals );
		$this->assertArrayHasKey( 'segments', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 10, count( $subtotals ) );
		$this->assertArrayHasKey( 'net_revenue', $subtotals );
		$this->assertArrayHasKey( 'avg_order_value', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'avg_items_per_order', $subtotals );
		$this->assertArrayHasKey( 'num_items_sold', $subtotals );
		$this->assertArrayHasKey( 'coupons', $subtotals );
		$this->assertArrayHasKey( 'coupons_count', $subtotals );
		$this->assertArrayHasKey( 'num_returning_customers', $subtotals );
		$this->assertArrayHasKey( 'num_new_customers', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}
}
