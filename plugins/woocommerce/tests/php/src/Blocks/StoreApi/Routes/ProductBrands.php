<?php
/**
 * Controller Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Product Brands Controller Tests.
 */
class ProductBrands extends ControllerTestCase {

	/**
	 * Setup test review data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product_brand = $fixtures->get_product_brand(
			array(
				'name' => 'Test Brand 1',
			)
		);

		$this->child_brand = $fixtures->get_product_brand(
			array(
				'name'   => 'Child Brand',
				'parent' => $this->product_brand['term_id'],
			)
		);

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'regular_price' => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'regular_price' => 100,
					'brand_ids'     => array( $this->product_brand['term_id'] ),
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 3',
					'regular_price' => 50,
					'brand_ids'     => array( $this->child_brand['term_id'] ),
				)
			),
		);
	}

	/**
	 * Test getting brands.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands' ) );
		$data     = $response->get_data();

		// Assert correct response format.
		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 2, count( $data ), 'Unexpected item count.' );

		// Assert response items contain the correct properties.
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'slug', $data[0] );
		$this->assertArrayHasKey( 'description', $data[0] );
		$this->assertArrayHasKey( 'parent', $data[0] );
		$this->assertArrayHasKey( 'count', $data[0] );
	}

	/**
	 * Test getting only top-level brands using parent=0 parameter.
	 */
	public function test_get_items_with_parent_zero() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands' );
		$request->set_param( 'parent', 0 );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 1, count( $data ), 'Expected only top-level brands.' );
		$this->assertSame( 'Test Brand 1', $data[0]['name'] );
		$this->assertSame( 0, $data[0]['parent'] );
	}

	/**
	 * Test getting child brands using parent parameter with parent brand ID.
	 */
	public function test_get_items_with_parent_id() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands' );
		$request->set_param( 'parent', $this->product_brand['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 1, count( $data ), 'Expected only child brands of specified parent.' );
		$this->assertSame( 'Child Brand', $data[0]['name'] );
		$this->assertSame( $this->product_brand['term_id'], $data[0]['parent'] );
	}

	/**
	 * Test that parent parameter with non-existent ID returns empty results.
	 */
	public function test_get_items_with_parent_nonexistent() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands' );
		$request->set_param( 'parent', 9 );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 0, count( $data ), 'Expected no brands for non-existent parent.' );
	}

	/**
	 * Test that parent parameter is registered in collection params.
	 */
	public function test_collection_params_include_parent() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/products/brands' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$params   = $data['endpoints'][0]['args'];

		$this->assertArrayHasKey( 'parent', $params );
		$this->assertSame( 'integer', $params['parent']['type'] );
	}

	/**
	 * Test getting brands from a specific product.
	 */
	public function test_get_items_with_product_id_param() {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands/' . $this->product_brand['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 'Test Brand 1', $data['name'] );
	}

	/**
	 * @testdox Should preserve a cached zero product count in a single brand response.
	 */
	public function test_get_item_preserves_cached_zero_count(): void {
		update_term_meta( $this->product_brand['term_id'], 'product_count_product_brand', 0 );

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands/' . $this->product_brand['term_id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertSame( 0, $data['count'], 'The cached zero count should be preserved.' );
	}

	/**
	 * @testdox Should paginate brands after excluding visibility-aware empty terms.
	 */
	public function test_hide_empty_pagination_excludes_cached_zero_counts(): void {
		$fixtures            = new FixtureData();
		$second_hidden_brand = $fixtures->get_product_brand(
			array(
				'name' => 'Another Hidden Brand',
			)
		);
		$fixtures->get_simple_product(
			array(
				'name'          => 'Another Hidden Brand Product',
				'regular_price' => 10,
				'brand_ids'     => array( $second_hidden_brand['term_id'] ),
			)
		);

		update_term_meta( $this->product_brand['term_id'], 'product_count_product_brand', 1 );
		update_term_meta( $this->child_brand['term_id'], 'product_count_product_brand', 0 );
		update_term_meta( $second_hidden_brand['term_id'], 'product_count_product_brand', 0 );
		delete_transient( 'wc_term_counts' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/brands' );
		$request->set_param( 'hide_empty', true );
		$request->set_param( 'per_page', 1 );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertCount( 1, $data, 'The page should contain one visible brand.' );
		$this->assertSame( $this->product_brand['term_id'], $data[0]['id'], 'The brand with a zero cached count should be excluded.' );
		$this->assertSame( 1, (int) $headers['X-WP-Total'], 'The total should exclude brands with a zero cached count.' );
		$this->assertSame( 1, (int) $headers['X-WP-TotalPages'], 'The total pages should match the filtered total.' );
	}

	/**
	 * Test getting reviews from a specific category.
	 */
	public function test_get_items_with_category_id_param() {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[1]->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected status code.' );
		$this->assertCount( 1, $data['brands'], 'Unexpected item count.' );
		$this->assertSame( 'Test Brand 1', $data['brands'][0]->name );
	}
}
