<?php
/**
 * Controller Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Product Categories Controller Tests.
 */
class ProductCategories extends ControllerTestCase {

	/**
	 * The product category term.
	 *
	 * @var array
	 */
	private $product_category;

	/**
	 * The products.
	 *
	 * @var array
	 */
	private $products;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product_category = $fixtures->get_product_category(
			array(
				'name' => 'Test Category',
			)
		);

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'regular_price' => 10,
					'stock_status'  => 'instock',
					'category_ids'  => array( $this->product_category['term_id'] ),
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'regular_price' => 20,
					'stock_status'  => 'instock',
					'category_ids'  => array( $this->product_category['term_id'] ),
				)
			),
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		delete_option( 'woocommerce_hide_out_of_stock_items' );
	}

	/**
	 * @testdox Should return correct category data.
	 */
	public function test_get_items(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories' );
		$request->set_param( 'hide_empty', false );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertGreaterThanOrEqual( 1, count( $data ), 'Expected at least one category.' );

		$test_category = null;
		foreach ( $data as $category ) {
			if ( $category['id'] === $this->product_category['term_id'] ) {
				$test_category = $category;
				break;
			}
		}

		$this->assertNotNull( $test_category, 'Test category not found in response.' );
		$this->assertArrayHasKey( 'id', $test_category );
		$this->assertArrayHasKey( 'name', $test_category );
		$this->assertArrayHasKey( 'slug', $test_category );
		$this->assertArrayHasKey( 'description', $test_category );
		$this->assertArrayHasKey( 'count', $test_category );
	}

	/**
	 * @testdox Should return a single category.
	 */
	public function test_get_item(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $this->product_category['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 'Test Category', $data['name'] );
	}

	/**
	 * @testdox Should exclude out of stock products from category count when hide out of stock option is enabled.
	 */
	public function test_category_count_excludes_out_of_stock_when_visibility_option_enabled(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $this->product_category['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 2, $data['count'], 'Expected count of 2 when both products are in stock.' );

		$this->products[0]->set_stock_status( 'outofstock' );
		$this->products[0]->save();

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		wc_recount_all_terms();

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $this->product_category['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 1, $data['count'], 'Expected count of 1 when one product is out of stock and visibility option is enabled.' );
	}

	/**
	 * @testdox Should include out of stock products in category count when hide out of stock option is disabled.
	 */
	public function test_category_count_includes_out_of_stock_when_visibility_option_disabled(): void {
		$this->products[0]->set_stock_status( 'outofstock' );
		$this->products[0]->save();

		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		wc_recount_all_terms();

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $this->product_category['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 2, $data['count'], 'Expected count of 2 when visibility option is disabled, even if one product is out of stock.' );
	}
}
