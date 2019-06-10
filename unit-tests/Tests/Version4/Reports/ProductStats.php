<?php
/**
 * Reports Products Stats REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4\Reports;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Unit_Test_Case;
use \WP_REST_Request;
use \WooCommerce\RestApi\UnitTests\Helpers\ReportsHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\QueueHelper;

/**
 * Class ProductStats
 */
class ProductStats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/products/stats';

	/**
	 * Setup test reports products stats data.
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
		ReportsHelper::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$time = time();

		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		QueueHelper::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => date( 'Y-m-d 23:59:59', $time ),
				'after'    => date( 'Y-m-d 00:00:00', $time ),
				'interval' => 'day',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$expected_reports = array(
			'totals'    => array(
				'items_sold'       => 4,
				'net_revenue'      => 100.0,
				'orders_count'     => 1,
				'products_count'   => 1,
				'variations_count' => 1,
				'segments'         => array(),
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d', $time ),
					'date_start'     => date( 'Y-m-d 00:00:00', $time ),
					'date_start_gmt' => date( 'Y-m-d 00:00:00', $time ),
					'date_end'       => date( 'Y-m-d 23:59:59', $time ),
					'date_end_gmt'   => date( 'Y-m-d 23:59:59', $time ),
					'subtotals'      => (object) array(
						'items_sold'       => 4,
						'net_revenue'      => 100.0,
						'orders_count'     => 1,
						'products_count'   => 1,
						'variations_count' => 1,
						'segments'         => array(),
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_reports, $reports );
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
		$this->assertEquals( 4, count( $totals ) );
		$this->assertArrayHasKey( 'net_revenue', $totals );
		$this->assertArrayHasKey( 'items_sold', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
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
		$this->assertEquals( 4, count( $subtotals ) );
		$this->assertArrayHasKey( 'net_revenue', $subtotals );
		$this->assertArrayHasKey( 'items_sold', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}
}
