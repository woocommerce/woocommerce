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
	 * @testdox A failed async category image update restores the previous thumbnail instead of leaving the category without an image.
	 */
	public function test_failed_async_category_image_restores_previous_thumbnail() {
		$attachment_id = self::factory()->attachment->create(
			array(
				'file'           => WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png',
				'post_mime_type' => 'image/png',
			)
		);

		// Seed a category with an existing thumbnail.
		$category_id = wp_insert_term( 'Async category', 'product_cat' )['term_id'];
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );

		// Schedule an async update pointing at an invalid attachment id.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/categories/' . $category_id );
		$request->set_body_params(
			array(
				'image'        => array( 'id' => 999999 ),
				'images_async' => true,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The controller should have queued the async category-image task through Action Scheduler.
		$this->assertTrue(
			as_has_scheduled_action( 'wc_rest_process_pending_category_image', array( $category_id ), 'woocommerce-rest-api-images' ),
			'An images_async category update should queue the pending-image task.'
		);

		// The old thumbnail is cleared while the job is pending.
		$this->assertEquals( '', get_term_meta( $category_id, 'thumbnail_id', true ) );

		// Run the queued task through the scheduler; it fails to apply the invalid image and restores the previous thumbnail.
		\WC_Helper_Queue::run_all_pending( 'woocommerce-rest-api-images' );

		$this->assertEquals( $attachment_id, get_term_meta( $category_id, 'thumbnail_id', true ), 'The previous thumbnail should be restored after a failed async job.' );
		$this->assertEmpty( get_term_meta( $category_id, '_wc_rest_pending_image', true ), 'Pending image meta should be cleared after processing.' );
	}

	/**
	 * @testdox A stale failed async category image job does not clobber a newer thumbnail applied after it.
	 */
	public function test_stale_failed_async_category_image_does_not_restore_over_newer_image() {
		$original_attachment_id = self::factory()->attachment->create(
			array(
				'file'           => WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png',
				'post_mime_type' => 'image/png',
			)
		);

		$category_id = wp_insert_term( 'Stale async category', 'product_cat' )['term_id'];
		update_term_meta( $category_id, 'thumbnail_id', $original_attachment_id );

		// A new image is applied by a later synchronous request while the earlier async job is still queued.
		$new_attachment_id = self::factory()->attachment->create(
			array(
				'file'           => WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png',
				'post_mime_type' => 'image/png',
			)
		);

		// Schedule the async job (points at an invalid attachment, so it will fail).
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/categories/' . $category_id );
		$request->set_body_params(
			array(
				'image'        => array( 'id' => 999999 ),
				'images_async' => true,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue(
			as_has_scheduled_action( 'wc_rest_process_pending_category_image', array( $category_id ), 'woocommerce-rest-api-images' ),
			'An images_async category update should queue the pending-image task.'
		);

		// A newer synchronous update applies a different image and leaves the pending marker in place.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/categories/' . $category_id );
		$request->set_body_params(
			array(
				'image' => array( 'id' => $new_attachment_id ),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// The stale async task is still queued; it fails but must not clobber the newer thumbnail.
		\WC_Helper_Queue::run_all_pending( 'woocommerce-rest-api-images' );

		$this->assertEquals( $new_attachment_id, get_term_meta( $category_id, 'thumbnail_id', true ), 'A stale failed job should not clobber a newer thumbnail.' );
		$this->assertNotEmpty( get_term_meta( $category_id, '_wc_rest_image_processing_errors', true ), 'The failed job should still record the processing error.' );

		wp_delete_attachment( $original_attachment_id, true );
		wp_delete_attachment( $new_attachment_id, true );
	}
}
