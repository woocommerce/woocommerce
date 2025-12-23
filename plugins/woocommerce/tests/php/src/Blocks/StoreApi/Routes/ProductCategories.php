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
		delete_transient( 'wc_term_counts' );
	}

	/**
	 * @testdox Category list count should reflect visibility-aware product count.
	 */
	public function test_category_list_count_uses_visibility_aware_count(): void {
		$term_id = $this->product_category['term_id'];

		$this->products[0]->set_stock_status( 'outofstock' );
		$this->products[0]->save();
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		wc_recount_all_terms();

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories' );
		$request->set_param( 'hide_empty', false );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$category = null;
		foreach ( $data as $cat ) {
			if ( $cat['id'] === $term_id ) {
				$category = $cat;
				break;
			}
		}

		$this->assertNotNull( $category, 'Category should exist in response.' );
		$this->assertSame( 1, $category['count'], 'Count should reflect visibility-aware value.' );
	}

	/**
	 * @testdox Single category count should reflect visibility-aware product count.
	 */
	public function test_single_category_count_uses_visibility_aware_count(): void {
		$term_id = $this->product_category['term_id'];

		$this->products[0]->set_stock_status( 'outofstock' );
		$this->products[0]->save();
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		wc_recount_all_terms();

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $term_id );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Response should be successful.' );
		$this->assertSame( 1, $data['count'], 'Single category count should reflect visibility-aware value.' );
	}
}
