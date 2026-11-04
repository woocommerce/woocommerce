<?php
/**
 * Reports Variations Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Variations_Stats
 */
class WC_Admin_Tests_API_Reports_Variations_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/variations/stats';

	/**
	 * Setup test reports variations stats data.
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
	 * Create a variable product with one variation and a completed order for it.
	 *
	 * @param string $name  Product name.
	 * @param float  $price Variation regular price.
	 * @return array{product: WC_Product_Variable, variation: WC_Product_Variation}
	 */
	private function create_variable_product_with_order( $name, $price ) {
		$product = new WC_Product_Variable();
		$product->set_name( $name );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( $price );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( $price * 4 );
		$order->save();

		return array(
			'product'   => $product,
			'variation' => $variation,
		);
	}

	/**
	 * @testdox The products alias should filter variation stats the same way product_includes does.
	 */
	public function test_products_param_filters_totals_like_product_includes() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		$time = time();

		$first  = $this->create_variable_product_with_order( 'First Variable', 25 );
		$second = $this->create_variable_product_with_order( 'Second Variable', 50 );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$base_params = array(
			'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
			'after'    => gmdate( 'Y-m-d 00:00:00', $time ),
			'interval' => 'day',
		);

		$unfiltered_request = new WP_REST_Request( 'GET', $this->endpoint );
		$unfiltered_request->set_query_params( $base_params );
		$unfiltered = $this->server->dispatch( $unfiltered_request )->get_data();

		$this->assertEquals( 8, $unfiltered['totals']['items_sold'], 'Unfiltered stats should include both products.' );
		$this->assertEquals( 2, $unfiltered['totals']['orders_count'] );

		$products_request = new WP_REST_Request( 'GET', $this->endpoint );
		$products_request->set_query_params(
			array_merge(
				$base_params,
				array(
					'products' => array( $first['product']->get_id() ),
				)
			)
		);
		$products_response = $this->server->dispatch( $products_request );
		$products_filtered = $products_response->get_data();

		$includes_request = new WP_REST_Request( 'GET', $this->endpoint );
		$includes_request->set_query_params(
			array_merge(
				$base_params,
				array(
					'product_includes' => array( $first['product']->get_id() ),
				)
			)
		);
		$includes_filtered = $this->server->dispatch( $includes_request )->get_data();

		$this->assertEquals( 200, $products_response->get_status() );
		$this->assertEquals(
			4,
			$products_filtered['totals']['items_sold'],
			'products should limit stats to the selected parent product.'
		);
		$this->assertEquals( 1, $products_filtered['totals']['orders_count'] );
		$this->assertEquals(
			$includes_filtered['totals'],
			$products_filtered['totals'],
			'products should produce the same totals as product_includes.'
		);
		$this->assertNotEquals(
			$second['product']->get_id(),
			$first['product']->get_id()
		);
	}
}
