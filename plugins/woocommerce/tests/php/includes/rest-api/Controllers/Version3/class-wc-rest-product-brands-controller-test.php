<?php
declare( strict_types = 1 );

/**
 * REST API Product Brands Controller Tests
 *
 * @package WooCommerce\Tests\RestApi
 * @since 9.4.0
 */

/**
 * WC_REST_Product_Brands_Controller_Test class.
 */
class WC_REST_Product_Brands_Controller_Test extends WC_REST_Unit_Test_Case {

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
	 * Test getting brands with correct count.
	 */
	public function test_get_brands_with_correct_count() {
		// Create a brand.
		$brand = wp_insert_term( 'Test Brand', 'product_brand' );
		$this->assertNotWPError( $brand );

		// Create a product and assign it to the brand.
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );

		// Make the request.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands' );
		$response = $this->server->dispatch( $request );

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Should have one brand.
		$this->assertCount( 1, $data );

		// Check the brand data.
		$brand_data = $data[0];
		$this->assertEquals( $brand['term_id'], $brand_data['id'] );
		$this->assertEquals( 'Test Brand', $brand_data['name'] );
		$this->assertEquals( 'test-brand', $brand_data['slug'] );
		$this->assertEquals( 1, $brand_data['count'], 'Brand should have count of 1' );
	}

	/**
	 * Test getting brands with zero count when no products assigned.
	 */
	public function test_get_brands_with_zero_count() {
		// Create a brand without any products.
		$brand = wp_insert_term( 'Empty Brand', 'product_brand' );
		$this->assertNotWPError( $brand );

		// Make the request.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands' );
		$response = $this->server->dispatch( $request );

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Should have one brand.
		$this->assertCount( 1, $data );

		// Check the brand data.
		$brand_data = $data[0];
		$this->assertEquals( $brand['term_id'], $brand_data['id'] );
		$this->assertEquals( 'Empty Brand', $brand_data['name'] );
		$this->assertEquals( 0, $brand_data['count'], 'Brand should have count of 0' );
	}


	/**
	 * Test getting brands respects product visibility settings.
	 */
	public function test_get_brands_respects_product_visibility() {
		// Create a brand.
		$brand = wp_insert_term( 'Visibility Test Brand', 'product_brand' );
		$this->assertNotWPError( $brand );

		// Create a product and assign it to the brand.
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );

		// Initially should have count of 1.
		$request    = new WP_REST_Request( 'GET', '/wc/v3/products/brands' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$brand_data = $data[0];
		$this->assertEquals( 1, $brand_data['count'], 'Brand should initially have count of 1' );

		// Set product to out of stock and enable hide out of stock setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Now should have count of 0.
		$request    = new WP_REST_Request( 'GET', '/wc/v3/products/brands' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$brand_data = $data[0];
		$this->assertEquals( 0, $brand_data['count'], 'Brand should have count of 0 when product is out of stock and hidden' );

		// Reset the setting.
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
	}

	/**
	 * Test getting brands with include parameter.
	 */
	public function test_get_brands_with_include_parameter() {
		// Create multiple brands.
		$brand1 = wp_insert_term( 'Brand 1', 'product_brand' );
		$brand2 = wp_insert_term( 'Brand 2', 'product_brand' );
		$brand3 = wp_insert_term( 'Brand 3', 'product_brand' );

		$this->assertNotWPError( $brand1 );
		$this->assertNotWPError( $brand2 );
		$this->assertNotWPError( $brand3 );

		// Assign products to brands 1 and 3.
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->save();
		wp_set_object_terms( $product1->get_id(), array( $brand1['term_id'] ), 'product_brand' );

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->save();
		wp_set_object_terms( $product3->get_id(), array( $brand3['term_id'] ), 'product_brand' );

		// Make the request with include parameter.
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/brands' );
		$request->set_param( 'include', array( $brand1['term_id'], $brand3['term_id'] ) );
		$response = $this->server->dispatch( $request );

		// Check response.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Should have two brands.
		$this->assertCount( 2, $data );

		// Check that the counts are correct.
		$brand1_data = null;
		$brand3_data = null;
		foreach ( $data as $brand_data ) {
			if ( $brand_data['id'] === $brand1['term_id'] ) {
				$brand1_data = $brand_data;
			} elseif ( $brand_data['id'] === $brand3['term_id'] ) {
				$brand3_data = $brand_data;
			}
		}

		$this->assertNotNull( $brand1_data, 'Brand 1 should be included' );
		$this->assertNotNull( $brand3_data, 'Brand 3 should be included' );
		$this->assertEquals( 1, $brand1_data['count'], 'Brand 1 should have count of 1' );
		$this->assertEquals( 1, $brand3_data['count'], 'Brand 3 should have count of 1' );
	}

	/**
	 * Test that brand counts are updated when products are added/removed.
	 */
	public function test_brand_counts_update_when_products_change() {
		// Create a brand.
		$brand = wp_insert_term( 'Dynamic Count Brand', 'product_brand' );
		$this->assertNotWPError( $brand );

		// Initially should have count of 0.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands/' . $brand['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Brand should initially have count of 0' );

		// Create a product and assign it to the brand.
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );

		// Now should have count of 1.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands/' . $brand['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 1, $data['count'], 'Brand should have count of 1 after adding product' );

		// Remove the product from the brand.
		wp_set_object_terms( $product->get_id(), array(), 'product_brand' );

		// Now should have count of 0 again.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands/' . $brand['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Brand should have count of 0 after removing product' );

		// Attach the product to the brand again.
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );

		// Now should have count of 1 again.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands/' . $brand['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Delete the product.
		wp_delete_post( $product->get_id() );

		// Now should have count of 0 again.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/brands/' . $brand['term_id'] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 0, $data['count'], 'Brand should have count of 0 after deleting product' );
	}
}
