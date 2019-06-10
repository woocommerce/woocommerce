<?php
/**
 * Reports Products REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4\Reports;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\AbstractReportsTest;

/**
 * Reports Products REST API Test Class
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class Products extends AbstractReportsTest {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/products';

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
		// Populate all of the data.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		QueueHelper::run_all_pending();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$product_report = reset( $reports );

		$this->assertEquals( $product->get_id(), $product_report['product_id'] );
		$this->assertEquals( 4, $product_report['items_sold'] );
		$this->assertEquals( 1, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );
	}

	/**
	 * Test getting reports with the `products` param.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_products_param() {
		// Populate all of the data.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$product_2 = new \WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( 25 );
		$product_2->save();

		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		QueueHelper::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'products' => $product->get_id() . ',' . $product_2->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$product_report = reset( $reports );

		$this->assertEquals( $product->get_id(), $product_report['product_id'] );
		$this->assertEquals( 4, $product_report['items_sold'] );
		$this->assertEquals( 1, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );

		$product_report = next( $reports );

		$this->assertEquals( $product_2->get_id(), $product_report['product_id'] );
		$this->assertEquals( null, $product_report['items_sold'] );
		$this->assertEquals( null, $product_report['orders_count'] );
		$this->assertArrayHasKey( '_links', $product_report );
		$this->assertArrayHasKey( 'product', $product_report['_links'] );
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
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'product_id', $properties );
		$this->assertArrayHasKey( 'items_sold', $properties );
		$this->assertArrayHasKey( 'net_revenue', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
