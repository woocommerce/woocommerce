<?php
declare( strict_types=1 );

/**
 * Variations Controller tests for V3 REST API.
 */
class WC_REST_Variations_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Variations_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/variations', $routes );
	}

	/**
	 * Test getting variations.
	 */
	public function test_get_variations() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates a variable product with variations.
		$product = WC_Helper_Product::create_variation_product();

		// When.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$response = $this->server->dispatch( $request );

		// Then.
		$this->assertEquals( 200, $response->get_status() );

		$variations = $response->get_data();
		$this->assertIsArray( $variations );
		$this->assertGreaterThan( 0, count( $variations ) );

		$variation = $variations[0];
		$this->assertArrayHasKey( 'id', $variation );
		$this->assertArrayHasKey( 'name', $variation );
		$this->assertArrayHasKey( 'parent_id', $variation );
		$this->assertArrayHasKey( 'type', $variation );
		$this->assertEquals( 'variation', $variation['type'] );
		$this->assertEquals( $product->get_id(), $variation['parent_id'] );
	}

	/**
	 * Test variation search by attribute value.
	 */
	public function test_variation_search_by_attribute_value() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates a variable product, then creates a variation using "global" product attributes.
		$product    = WC_Helper_Product::create_variation_product();
		$data_store = $product->get_data_store();
		$data_store->create_all_product_variations( $product, 1 );
		$child_product_ids = $product->get_children();
		$variation_1       = wc_get_product( $child_product_ids[0] );

		// Creates a variation, using "local" attribute key/value pairs.
		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'regular_price' => 23,
			)
		);
		$variation_2->set_attributes( array( 'flavor' => 'melon' ) );
		$variation_2->save();

		// When searching for the "global" size attribute.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'small' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_1->get_id(), $variations[0]['id'] );

		// When searching for the "local" flavor attribute.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'melon' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_2->get_id(), $variations[0]['id'] );
	}

	/**
	 * Test variation search by SKU.
	 */
	public function test_variation_search_by_sku() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates a variable product with variations.
		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$variation->set_sku( 'test-variation-sku' );
		$variation->save();

		// When searching by SKU.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'test-variation-sku' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation->get_id(), $variations[0]['id'] );
	}

	/**
	 * Test getting variations without permissions.
	 */
	public function test_get_variations_without_permission() {
		// Given.
		wp_set_current_user( 0 );

		// When.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$response = $this->server->dispatch( $request );

		// Then.
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test the variations endpoint returns variations from multiple products.
	 */
	public function test_get_variations_from_multiple_products() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates two variable products with variations.
		$product1 = WC_Helper_Product::create_variation_product();
		$product2 = WC_Helper_Product::create_variation_product();

		// When.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$response = $this->server->dispatch( $request );

		// Then.
		$this->assertEquals( 200, $response->get_status() );

		$variations = $response->get_data();
		$this->assertIsArray( $variations );

		$parent_ids = array_unique( array_column( $variations, 'parent_id' ) );
		$this->assertContains( $product1->get_id(), $parent_ids );
		$this->assertContains( $product2->get_id(), $parent_ids );
	}

	/**
	 * Test variations endpoint supports pagination.
	 */
	public function test_get_variations_pagination() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates 3 variable products to have more variations.
		for ( $i = 0; $i < 3; $i++ ) {
			WC_Helper_Product::create_variation_product();
		}

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'per_page', 2 );
		$response = $this->server->dispatch( $request );

		// Then.
		$this->assertEquals( 200, $response->get_status() );

		$variations = $response->get_data();
		$this->assertCount( 2, $variations );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );
		$this->assertEquals( 18, $headers['X-WP-Total'] ); // 3 variable products * 6 variations per product.
		$this->assertEquals( 9, $headers['X-WP-TotalPages'] );
	}

	/**
	 * Test the variation schema.
	 */
	public function test_variation_schema() {
		// When.
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/variations' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		// Then.
		// Ensures expected properties are present.
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'permalink', $properties );
		$this->assertArrayHasKey( 'sku', $properties );
		$this->assertArrayHasKey( 'global_unique_id', $properties );
		$this->assertArrayHasKey( 'price', $properties );
		$this->assertArrayHasKey( 'regular_price', $properties );
		$this->assertArrayHasKey( 'sale_price', $properties );
		$this->assertArrayHasKey( 'date_on_sale_from', $properties );
		$this->assertArrayHasKey( 'date_on_sale_to', $properties );
		$this->assertArrayHasKey( 'on_sale', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'purchasable', $properties );
		$this->assertArrayHasKey( 'virtual', $properties );
		$this->assertArrayHasKey( 'downloadable', $properties );
		$this->assertArrayHasKey( 'downloads', $properties );
		$this->assertArrayHasKey( 'download_limit', $properties );
		$this->assertArrayHasKey( 'download_expiry', $properties );
		$this->assertArrayHasKey( 'tax_status', $properties );
		$this->assertArrayHasKey( 'tax_class', $properties );
		$this->assertArrayHasKey( 'manage_stock', $properties );
		$this->assertArrayHasKey( 'stock_quantity', $properties );
		$this->assertArrayHasKey( 'stock_status', $properties );
		$this->assertArrayHasKey( 'backorders', $properties );
		$this->assertArrayHasKey( 'weight', $properties );
		$this->assertArrayHasKey( 'dimensions', $properties );
		$this->assertArrayHasKey( 'shipping_class', $properties );
		$this->assertArrayHasKey( 'shipping_class_id', $properties );
		$this->assertArrayHasKey( 'image', $properties );
		$this->assertArrayHasKey( 'attributes', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'meta_data', $properties );

		// Additional properties from ProductVariations controller.
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'type', $properties );
	}
}
