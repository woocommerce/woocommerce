<?php
/**
 * Tests for the reports orders totals REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * WC_Tests_API_Reports_Orders_Totals.
 */
class WC_Tests_API_Reports_Orders_Totals extends WC_REST_Unit_Test_Case {

	use HPOSToggleTrait;

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
		$this->assertArrayHasKey( '/wc/v3/reports/orders/totals', $routes );
	}

	/**
	 * Test getting order totals.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/orders/totals' ) );
		$report   = $response->get_data();
		$totals   = wp_count_posts( 'shop_order' );
		$data     = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {
			if ( ! isset( $totals->$slug ) ) {
				continue;
			}

			$data[] = array(
				'slug'  => str_replace( 'wc-', '', $slug ),
				'name'  => $name,
				'total' => (int) $totals->$slug,
			);
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( wc_get_order_statuses() ), count( $report ) );
		$this->assertEquals( $data, $report );
	}

	/**
	 * Test getting order totals with HPOS enabled and some orders pending sync.
	 *
	 * @since 8.9.0
	 */
	public function test_get_reports_with_hpos_enabled_and_sync_off() {
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();

		wp_set_current_user( $this->user );

		// Create some orders with HPOS enabled.
		$order_counts = array(
			'wc-pending'    => 3,
			'wc-processing' => 2,
			'wc-on-hold'    => 1,
		);
		foreach ( $order_counts as $status => $count ) {
			for ( $i = 0; $i < $count; $i++ ) {
				$order = OrderHelper::create_order();
				$order->set_status( $status );
				$order->save();
			}
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/orders/totals' ) );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( wc_get_order_statuses() ), count( $report ) );
		foreach ( $report as $data ) {
			if ( array_key_exists( 'wc-' . $data['slug'], $order_counts ) ) {
				$this->assertEquals( $order_counts[ 'wc-' . $data['slug'] ], $data['total'], 'Status: ' . $data['slug'] );
			} else {
				$this->assertEquals( 0, $data['total'], 'Status: ' . $data['slug'] );
			}
		}
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/orders/totals' ) );
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
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/orders/totals' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'total', $properties );
	}
}
