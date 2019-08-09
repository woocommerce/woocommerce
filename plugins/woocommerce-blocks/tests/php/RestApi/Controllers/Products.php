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
 * Blocks Product Controller REST API Test
 *
 * @since 3.6.0
 */
class Products extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/blocks';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user        = $this->factory->user->create(
			array(
				'role' => 'author',
			)
		);
		$this->contributor = $this->factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
		$this->subscriber  = $this->factory->user->create(
			array(
				'role' => 'subscriber',
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
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_category_ids( array( $parent['term_id'] ) );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_category_ids( array( $single['term_id'] ) );
		$this->products[1]->save();

		$this->products[2] = ProductHelper::create_simple_product( false );
		$this->products[2]->set_category_ids( array( $child['term_id'], $single['term_id'] ) );
		$this->products[2]->save();

		$this->products[3] = ProductHelper::create_simple_product( false );
		$this->products[3]->set_category_ids( array( $parent['term_id'], $single['term_id'] ) );
		$this->products[3]->save();
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.6.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/blocks/products', $routes );
		$this->assertArrayHasKey( '/wc/blocks/products/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting products.
	 *
	 * @group failing
	 *
	 * @since 3.6.0
	 */
	public function test_get_products() {
		wp_set_current_user( $this->user );
		ProductHelper::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		$product  = ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/blocks/products' ) );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 6, count( $products ) );
	}

	/**
	 * Test getting products as an contributor.
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_as_contributor() {
		wp_set_current_user( $this->contributor );
		ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/blocks/products' ) );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test getting products as an subscriber.
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_as_subscriber() {
		wp_set_current_user( $this->subscriber );
		ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/blocks/products' ) );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test getting products with custom ordering.
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_order_by_price() {
		wp_set_current_user( $this->user );
		ProductHelper::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		$product = ProductHelper::create_simple_product( false ); // Prevent saving, since we save here.
		// Customize the price, otherwise both are 10.
		$product->set_props(
			array(
				'regular_price' => 15,
				'price'         => 15,
			)
		);
		$product->save();

		$request = new WP_REST_Request( 'GET', '/wc/blocks/products' );
		$request->set_param( 'orderby', 'price' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, count( $products ) );

		$this->assertEquals( 'Dummy Product', $products[1]['name'] );
		$this->assertEquals( '10', $products[1]['price'] );
	}

	/**
	 * Test product_visibility queries.
	 *
	 * @since 3.6.0
	 */
	public function test_product_visibility() {
		wp_set_current_user( $this->user );
		$visible_product = ProductHelper::create_simple_product();
		$visible_product->set_name( 'Visible Product' );
		$visible_product->set_catalog_visibility( 'visible' );
		$visible_product->save();

		$catalog_product = ProductHelper::create_simple_product();
		$catalog_product->set_name( 'Catalog Product' );
		$catalog_product->set_catalog_visibility( 'catalog' );
		$catalog_product->save();

		$search_product = ProductHelper::create_simple_product();
		$search_product->set_name( 'Search Product' );
		$search_product->set_catalog_visibility( 'search' );
		$search_product->save();

		$hidden_product = ProductHelper::create_simple_product();
		$hidden_product->set_name( 'Hidden Product' );
		$hidden_product->set_catalog_visibility( 'hidden' );
		$hidden_product->save();

		$query_params = array(
			'catalog_visibility' => 'visible',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc/blocks/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 5, count( $products ) );
		$this->assertEquals( 'Visible Product', $products[0]['name'] );

		$query_params = array(
			'catalog_visibility' => 'catalog',
			'orderby'            => 'id',
			'order'              => 'asc',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc/blocks/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );

		$query_params = array(
			'catalog_visibility' => 'search',
			'orderby'            => 'id',
			'order'              => 'asc',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc/blocks/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );

		$query_params = array(
			'catalog_visibility' => 'hidden',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc/blocks/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $products ) );
		$this->assertEquals( 'Hidden Product', $products[0]['name'] );
	}

	/**
	 * Test product category intersection: Any product in either Single or Child (3).
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_in_any_categories_child() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['child']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc/blocks/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'category_operator', 'in' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response_products ) );
	}

	/**
	 * Test product category intersection: Any product in both Single and Child (1).
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_in_all_categories_child() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['child']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc/blocks/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'category_operator', 'and' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}

	/**
	 * Test product category intersection: Any product in both Single and Parent (1).
	 *
	 * @since 3.6.0
	 */
	public function test_get_products_in_all_categories_parent() {
		wp_set_current_user( $this->user );

		$cats = $this->categories['parent']['term_id'] . ',' . $this->categories['single']['term_id'];

		$request = new WP_REST_Request( 'GET', '/wc/blocks/products' );
		$request->set_param( 'category', $cats );
		$request->set_param( 'category_operator', 'and' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}
}
