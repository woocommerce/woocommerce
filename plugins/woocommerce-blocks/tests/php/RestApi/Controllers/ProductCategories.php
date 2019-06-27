<?php
/**
 * REST API Controller Tests.
 *
 * @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\RestApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case;
use \WC_Helper_Product as ProductHelper;

/**
 * Product Categories Controller  REST API Test
 *
 * @since 3.6.0
 */
class ProductCategories extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/blocks';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 3.6.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->contributor = $this->factory->user->create(
			array(
				'role' => 'contributor',
			)
		);

		// Create 3 product categories.
		$parent           = wp_insert_term( 'Parent Category', 'product_cat' );
		$child            = wp_insert_term(
			'Child Category',
			'product_cat',
			array( 'parent' => $parent['term_id'] )
		);
		$single           = wp_insert_term( 'Standalone Category', 'product_cat' );
		$this->categories = array(
			'parent' => $parent,
			'child'  => $child,
			'single' => $single,
		);

		// Create two products for the parent category.
		$this->products    = array();
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_category_ids( array( $parent['term_id'] ) );
		$this->products[0]->save();

		$this->products[3] = ProductHelper::create_simple_product( false );
		$this->products[3]->set_category_ids( array( $parent['term_id'], $single['term_id'] ) );
		$this->products[3]->save();
	}

	/**
	 * Test getting product categories.
	 *
	 * @since 3.6.0
	 */
	public function test_get_product_categories() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/categories' );

		$response   = $this->server->dispatch( $request );
		$categories = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $categories ) ); // Three created and `uncategorized`.
	}

	/**
	 * Test getting invalid product category.
	 *
	 * @since 3.6.0
	 */
	public function test_get_invalid_product_category() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/categories/007' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test un-authorized getting product category.
	 *
	 * @since 3.6.0
	 */
	public function test_get_unauthed_product_category() {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/categories/' . $this->categories['parent']['term_id'] );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting category as contributor.
	 *
	 * @since 3.6.0
	 */
	public function test_get_attribute_terms_contributor() {
		wp_set_current_user( $this->contributor );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/categories/' . $this->categories['parent']['term_id'] );

		$response = $this->server->dispatch( $request );
		$category = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $category['name'], 'Parent Category' );
		$this->assertEquals( $category['parent'], 0 );
		$this->assertEquals( $category['count'], 2 );
	}
}
