<?php
/**
 * @package WooCommerce\Tests\API
 */

/**
 * Product Controller "products by categories" REST API Test
 *
 * @since 1.2.0
 */
class WC_Tests_API_Products_By_Categories_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-blocks/v1';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'author',
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

		// Create two products, one with 'parent', and one with 'single'.
		$this->products    = array();
		$this->products[0] = WC_Helper_Product::create_simple_product( false );
		$this->products[0]->set_category_ids( array( $parent['term_id'] ) );
		$this->products[0]->save();

		$this->products[1] = WC_Helper_Product::create_simple_product( false );
		$this->products[1]->set_category_ids( array( $single['term_id'] ) );
		$this->products[1]->save();

		$this->products[2] = WC_Helper_Product::create_simple_product( false );
		$this->products[2]->set_category_ids( array( $child['term_id'], $single['term_id'] ) );
		$this->products[2]->save();

		$this->products[3] = WC_Helper_Product::create_simple_product( false );
		$this->products[3]->set_category_ids( array( $parent['term_id'], $single['term_id'] ) );
		$this->products[3]->save();
	}

	/**
	 * Test product category intersection: Any product in either Single or Parent (4).
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_in_any_categories_parent() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['parent']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc-blocks/v1/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'cat_operator', 'IN' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $response_products ) );
	}

	/**
	 * Test product category intersection: Any product in either Single or Child (3).
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_in_any_categories_child() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['child']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc-blocks/v1/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'cat_operator', 'IN' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response_products ) );
	}

	/**
	 * Test product category intersection: Any product in both Single and Child (1).
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_in_all_categories_child() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['child']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc-blocks/v1/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'cat_operator', 'AND' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}

	/**
	 * Test product category intersection: Any product in both Single and Parent (1).
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_in_all_categories_parent() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['parent']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc-blocks/v1/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'cat_operator', 'AND' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}
}
