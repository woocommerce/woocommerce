<?php
declare( strict_types = 1 );

/**
 * REST API Product Categories Controller Tests
 *
 * @package WooCommerce\Tests\RestApi
 * @since 9.4.0
 */

/**
 * WC_REST_Product_Categories_Controller_Test class.
 */
class WC_REST_Product_Categories_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * @var int
	 */
	protected $user;

	/**
	 * Setup test data.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user );
	}

	/**
	 * Helper method to create a test category.
	 *
	 * @param string $name Category name.
	 * @return array Category data.
	 */
	private function create_test_category( string $name ): array {
		$category = wp_insert_term( $name, 'product_cat' );
		return $category;
	}

	/**
	 * Helper method to create a test product.
	 *
	 * @return WC_Product_Simple Product object.
	 */
	private function create_test_product(): WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		return $product;
	}

	/**
	 * Helper method to make a GET request to the categories endpoint.
	 *
	 * @param string|int $endpoint Optional specific endpoint.
	 * @param array      $params Optional query parameters.
	 * @return WP_REST_Response Response object.
	 */
	private function make_categories_request( $endpoint = '', array $params = array() ): WP_REST_Response {
		$url = '/wc/v3/products/categories';
		if ( $endpoint ) {
			$url .= '/' . $endpoint;
		}

		$request = new WP_REST_Request( 'GET', $url );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Helper method to find test category data in response.
	 *
	 * @param array $response_data Response data array.
	 * @param int   $category_id Category ID to find.
	 * @return array|null Category data or null if not found.
	 */
	private function find_category_in_response( array $response_data, int $category_id ): ?array {
		foreach ( $response_data as $category_data ) {
			if ( $category_data['id'] === $category_id ) {
				return $category_data;
			}
		}
		return null;
	}

	/**
	 * Test getting categories with correct count.
	 */
	public function test_get_categories_with_correct_count() {
		// Create a category.
		$category = $this->create_test_category( 'Test Category' );

		// Create a product and assign it to the category.
		$product = $this->create_test_product();
		wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

		// Make the request.
		$response = $this->make_categories_request();

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Find our test category in the response.
		$test_category_data = $this->find_category_in_response( $data, $category['term_id'] );
		$this->assertNotNull( $test_category_data, 'Test category should be found in response' );

		// Assert category data.
		$this->assertEquals( $category['term_id'], $test_category_data['id'] );
		$this->assertEquals( 'Test Category', $test_category_data['name'] );
		$this->assertEquals( 'test-category', $test_category_data['slug'] );
		$this->assertEquals( 1, $test_category_data['count'], 'Category should have count of 1' );

		// Clean up.
		wp_delete_post( $product->get_id(), true );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * Test getting categories with zero count when no products assigned.
	 */
	public function test_get_categories_with_zero_count() {
		// Create a category without any products.
		$category = $this->create_test_category( 'Empty Category' );

		// Make the request.
		$response = $this->make_categories_request();

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Find our test category in the response.
		$test_category_data = $this->find_category_in_response( $data, $category['term_id'] );
		$this->assertNotNull( $test_category_data, 'Test category should be found in response' );

		// Assert category data.
		$this->assertEquals( $category['term_id'], $test_category_data['id'] );
		$this->assertEquals( 'Empty Category', $test_category_data['name'] );
		$this->assertEquals( 0, $test_category_data['count'], 'Category should have count of 0' );

		// Clean up.
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * Test getting categories respects product visibility settings.
	 */
	public function test_get_categories_respects_product_visibility() {
		// Create a category.
		$category = $this->create_test_category( 'Visibility Test Category' );

		// Create a product and assign it to the category.
		$product = $this->create_test_product();
		wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

		// Initially should have count of 1.
		$response = $this->make_categories_request();
		$data     = $response->get_data();

		// Find our test category in the response.
		$test_category_data = $this->find_category_in_response( $data, $category['term_id'] );
		$this->assertNotNull( $test_category_data, 'Test category should be found in response' );
		$this->assertEquals( 1, $test_category_data['count'], 'Category should initially have count of 1' );

		// Set product to out of stock and enable hide out of stock setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Now should have count of 0.
		$response = $this->make_categories_request();
		$data     = $response->get_data();

		// Find our test category in the response.
		$test_category_data = $this->find_category_in_response( $data, $category['term_id'] );
		$this->assertNotNull( $test_category_data, 'Test category should be found in response' );
		$this->assertEquals( 0, $test_category_data['count'], 'Category should have count of 0 when product is out of stock and hidden' );

		// Category specific request should have count of 1.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();
		$this->assertEquals( 1, $data['count'], 'Category should have count of 1 when product is out of stock and hidden' );

		// Reset the setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );

		// Clean up.
		wp_delete_post( $product->get_id(), true );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * Test getting categories with include parameter.
	 */
	public function test_get_categories_with_include_parameter() {
		// Create multiple categories.
		$category1 = $this->create_test_category( 'Category 1' );
		$category2 = $this->create_test_category( 'Category 2' );
		$category3 = $this->create_test_category( 'Category 3' );

		// Assign products to categories 1 and 3.
		$product1 = $this->create_test_product();
		wp_set_object_terms( $product1->get_id(), array( $category1['term_id'] ), 'product_cat' );

		$product3 = $this->create_test_product();
		wp_set_object_terms( $product3->get_id(), array( $category3['term_id'] ), 'product_cat' );

		// Make the request with include parameter.
		$response = $this->make_categories_request( '', array( 'include' => array( $category1['term_id'], $category3['term_id'] ) ) );

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Should have two categories.
		$this->assertCount( 2, $data );

		// Check that the counts are correct.
		$category1_data = $this->find_category_in_response( $data, $category1['term_id'] );
		$category3_data = $this->find_category_in_response( $data, $category3['term_id'] );

		$this->assertNotNull( $category1_data, 'Category 1 should be included' );
		$this->assertNotNull( $category3_data, 'Category 3 should be included' );
		$this->assertEquals( 1, $category1_data['count'], 'Category 1 should have count of 1' );
		$this->assertEquals( 1, $category3_data['count'], 'Category 3 should have count of 1' );

		// Clean up.
		wp_delete_post( $product1->get_id(), true );
		wp_delete_post( $product3->get_id(), true );
		wp_delete_term( $category1['term_id'], 'product_cat' );
		wp_delete_term( $category2['term_id'], 'product_cat' );
		wp_delete_term( $category3['term_id'], 'product_cat' );
	}

	/**
	 * Test that category counts are updated when products are added/removed.
	 */
	public function test_category_counts_update_when_products_change() {
		// Create a category.
		$category = $this->create_test_category( 'Dynamic Count Category' );

		// Initially should have count of 0.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Category should initially have count of 0' );

		// Create a product and assign it to the category.
		$product = $this->create_test_product();
		wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

		// Now should have count of 1.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();
		$this->assertEquals( 1, $data['count'], 'Category should have count of 1 after adding product' );

		// Remove the product from the category.
		wp_set_object_terms( $product->get_id(), array(), 'product_cat' );

		// Now should have count of 0 again.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Category should have count of 0 after removing product' );

		// Attach the product to the category again.
		wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

		// Now should have count of 1 again.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();

		// Delete the product.
		wp_delete_post( $product->get_id() );

		// Now should have count of 0 again.
		$response = $this->make_categories_request( $category['term_id'] );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Category should have count of 0 after deleting product' );

		// Clean up.
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product categories v3 item schema contains expected properties.
	 */
	public function test_get_item_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/products/categories' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'parent', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'display', $properties );
		$this->assertArrayHasKey( 'image', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'count', $properties );
	}

	/**
	 * @testdox Creating a product category in v3 with an empty slug succeeds.
	 */
	public function test_create_with_empty_slug() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/categories' );
		$request->set_body_params(
			array(
				'name' => 'Test category',
				'slug' => '',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
	}

	/**
	 * @testdox hide_empty pagination keeps empty parents that have non-empty children.
	 */
	public function test_hide_empty_pagination_includes_empty_parents_with_nonempty_children() {
		$fixture   = $this->create_empty_parent_with_nonempty_child( '67934 Paginate ' . uniqid() );
		$parent_id = (int) $fixture['parent']['term_id'];
		$child_id  = (int) $fixture['child']['term_id'];

		$full_response = $this->make_categories_request(
			'',
			array(
				'hide_empty' => true,
				'include'    => array( $parent_id, $child_id ),
				'per_page'   => 100,
				'page'       => 1,
			)
		);
		$full_data     = $full_response->get_data();
		$full_headers  = $full_response->get_headers();

		$this->assertEquals( 200, $full_response->get_status() );
		$this->assertCount( 2, $full_data );
		$this->assertNotNull( $this->find_category_in_response( $full_data, $parent_id ) );
		$this->assertNotNull( $this->find_category_in_response( $full_data, $child_id ) );
		$this->assertSame( 2, (int) $full_headers['X-WP-Total'] );
		$this->assertSame( 1, (int) $full_headers['X-WP-TotalPages'] );

		$page_one = $this->make_categories_request(
			'',
			array(
				'hide_empty' => true,
				'include'    => array( $parent_id, $child_id ),
				'orderby'    => 'name',
				'order'      => 'asc',
				'per_page'   => 1,
				'page'       => 1,
			)
		);
		$page_two = $this->make_categories_request(
			'',
			array(
				'hide_empty' => true,
				'include'    => array( $parent_id, $child_id ),
				'orderby'    => 'name',
				'order'      => 'asc',
				'per_page'   => 1,
				'page'       => 2,
			)
		);

		$page_one_data = $page_one->get_data();
		$page_two_data = $page_two->get_data();
		$returned_ids  = array_map( static fn( $term ) => (int) $term['id'], array_merge( $page_one_data, $page_two_data ) );

		$this->assertEquals( 200, $page_one->get_status() );
		$this->assertEquals( 200, $page_two->get_status() );
		$this->assertCount( 1, $page_one_data );
		$this->assertCount( 1, $page_two_data, 'Page 2 must remain reachable when an empty parent has a non-empty child.' );
		$this->assertSame( 2, (int) $page_one->get_headers()['X-WP-Total'] );
		$this->assertSame( 2, (int) $page_one->get_headers()['X-WP-TotalPages'] );
		$this->assertSame( 2, (int) $page_two->get_headers()['X-WP-Total'] );
		$this->assertSame( 2, (int) $page_two->get_headers()['X-WP-TotalPages'] );
		$this->assertEqualsCanonicalizing( array( $parent_id, $child_id ), $returned_ids );

		wp_delete_post( $fixture['product']->get_id(), true );
		wp_delete_term( $child_id, 'product_cat' );
		wp_delete_term( $parent_id, 'product_cat' );
	}

	/**
	 * @testdox hide_empty parent=0 pagination keeps empty top-level parents that have non-empty children.
	 */
	public function test_hide_empty_parent_zero_pagination_includes_empty_parents() {
		$first      = $this->create_empty_parent_with_nonempty_child( '67934 TopA ' . uniqid() );
		$second     = $this->create_empty_parent_with_nonempty_child( '67934 TopB ' . uniqid() );
		$parent_ids = array(
			(int) $first['parent']['term_id'],
			(int) $second['parent']['term_id'],
		);

		$full_response = $this->make_categories_request(
			'',
			array(
				'hide_empty' => true,
				'parent'     => 0,
				'include'    => $parent_ids,
				'per_page'   => 100,
				'page'       => 1,
			)
		);
		$full_data     = $full_response->get_data();

		$this->assertEquals( 200, $full_response->get_status() );
		$this->assertCount( 2, $full_data );
		$this->assertSame( 2, (int) $full_response->get_headers()['X-WP-Total'] );
		$this->assertSame( 1, (int) $full_response->get_headers()['X-WP-TotalPages'] );

		$page_two = $this->make_categories_request(
			'',
			array(
				'hide_empty' => true,
				'parent'     => 0,
				'include'    => $parent_ids,
				'orderby'    => 'name',
				'order'      => 'asc',
				'per_page'   => 1,
				'page'       => 2,
			)
		);
		$page_two_data = $page_two->get_data();

		$this->assertEquals( 200, $page_two->get_status() );
		$this->assertCount( 1, $page_two_data, 'Page 2 of parent=0 must return the remaining empty parent.' );
		$this->assertSame( 2, (int) $page_two->get_headers()['X-WP-Total'] );
		$this->assertSame( 2, (int) $page_two->get_headers()['X-WP-TotalPages'] );
		$this->assertContains( (int) $page_two_data[0]['id'], $parent_ids );

		wp_delete_post( $first['product']->get_id(), true );
		wp_delete_post( $second['product']->get_id(), true );
		wp_delete_term( (int) $first['child']['term_id'], 'product_cat' );
		wp_delete_term( (int) $second['child']['term_id'], 'product_cat' );
		wp_delete_term( $parent_ids[0], 'product_cat' );
		wp_delete_term( $parent_ids[1], 'product_cat' );
	}

	/**
	 * Create an empty parent category whose child has a product assigned.
	 *
	 * @param string $prefix Unique name prefix.
	 * @return array{parent: array, child: array, product: WC_Product_Simple}
	 */
	private function create_empty_parent_with_nonempty_child( string $prefix ): array {
		$parent = wp_insert_term( $prefix . ' Parent', 'product_cat' );
		$this->assertIsArray( $parent );

		$child = wp_insert_term(
			$prefix . ' Child',
			'product_cat',
			array( 'parent' => $parent['term_id'] )
		);
		$this->assertIsArray( $child );

		$product = $this->create_test_product();
		wp_set_object_terms( $product->get_id(), array( $child['term_id'] ), 'product_cat' );

		return array(
			'parent'  => $parent,
			'child'   => $child,
			'product' => $product,
		);
	}
}
