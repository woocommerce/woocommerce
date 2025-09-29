<?php
declare( strict_types=1 );

/**
 * Variations Controller tests for V3 REST API.
 */
class WC_REST_Variations_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * User ID with admin capabilities.
	 *
	 * @var int
	 */
	protected int $user;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
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
		$this->assertSame( 'variation', $variation['type'] );
		$this->assertSame( $product->get_id(), $variation['parent_id'] );
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
		$total       = (int) $headers['X-WP-Total'];
		$total_pages = (int) $headers['X-WP-TotalPages'];
		$this->assertSame( 18, $total ); // 3 variable products * 6 variations per product.
		$this->assertSame( 9, $total_pages );
	}

	/**
	 * Test variations endpoint supports modified_after parameter.
	 */
	public function test_get_variations_with_modified_after_parameter_returns_variations_modified_after_the_date() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates a variable product with 6 variations.
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];

		// Sets an explicit modified date in the past using direct database update.
		// This bypasses WooCommerce data store logic that overwrites custom dates.
		$past_time_string = '2023-01-01 10:00:00'; // MySQL DATETIME format.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => $past_time_string,
				'post_modified_gmt' => get_gmt_from_date( $past_time_string ),
			),
			array( 'ID' => $variation_id )
		);
		clean_post_cache( $variation_id );

		// When filtering by modified_after with a time before modification.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'modified_after', '2023-01-01T09:59:59' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then should include the variation.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 6, $variations ); // 6 variations per product from `create_variation_product`.
		$variation_ids = array_column( $variations, 'id' );
		$this->assertContains( $variation_id, $variation_ids );

		// When filtering by modified_after with a time after modification.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'modified_after', '2023-01-01T10:00:01' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then should not include the variation modified before the date.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $variations );
		$variation_ids = array_column( $variations, 'id' );
		$this->assertNotContains( $variation_id, $variation_ids );
	}

	/**
	 * Test variation search by global/local attribute key/value.
	 */
	public function test_variation_search_by_attribute() {
		// Given.
		wp_set_current_user( $this->user );

		// Creates a variable product, then creates a variation using "global" product attributes.
		$product           = WC_Helper_Product::create_variation_product();
		$child_product_ids = $product->get_children();
		$this->assertCount( 6, $child_product_ids );
		$variation_1 = wc_get_product( $child_product_ids[0] ); // 'size' => 'small' attribute.

		// Creates a variation, using "local" attribute key/value pairs.
		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'regular_price' => 23,
			)
		);
		$variation_2->set_attributes( array( 'material' => 'wool' ) );
		$variation_2->save();

		// When searching for the "global" attribute value.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'small' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_1->get_id(), $variations[0]['id'] );

		// When searching for the "global" attribute key.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'size' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, count( $variations ) );
		$this->assertContains( $variation_1->get_id(), array_column( $variations, 'id' ) );

		// When searching for the "local" attribute value.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'wool' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_2->get_id(), $variations[0]['id'] );

		// When searching for the "local" attribute key.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( 'search', 'material' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_2->get_id(), $variations[0]['id'] );
	}

	/**
	 * Test field filtering with _fields parameter.
	 */
	public function test_get_variations_with_fields_filtering() {
		// Given.
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_variation_product();

		// When requesting only 3 specific fields.
		$request = new WP_REST_Request( 'GET', '/wc/v3/variations' );
		$request->set_param( '_fields', 'id,sku,parent_id' );
		$response = $this->server->dispatch( $request );

		// Then.
		$this->assertSame( 200, $response->get_status() );
		$variations = $response->get_data();
		$this->assertGreaterThan( 0, count( $variations ) );

		$variation = $variations[0];

		// Should have requested fields.
		$this->assertArrayHasKey( 'id', $variation );
		$this->assertArrayHasKey( 'sku', $variation );
		$this->assertArrayHasKey( 'parent_id', $variation );
		$this->assertSame( $product->get_id(), $variation['parent_id'] );

		// Should not have other fields.
		$this->assertArrayNotHasKey( 'name', $variation );
		$this->assertArrayNotHasKey( 'description', $variation );
		$this->assertArrayNotHasKey( 'attributes', $variation );
		$this->assertArrayNotHasKey( 'date_created', $variation );
		$this->assertArrayNotHasKey( 'price', $variation );

		// The variation should have the exact number of requested fields.
		$this->assertCount( 3, $variation );
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

		// Additional property from variations controller.
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertEquals( 'integer', $properties['parent_id']['type'] );
	}
}
