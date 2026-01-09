<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Variations Controller tests for V3 REST API.
 */
class WC_REST_Product_Variations_Controller_Tests extends WC_REST_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Products_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
	}

	/**
	 * @testdox The variation GET endpoint returns no Cost of Goods information for variations when the feature is disabled.
	 */
	public function test_cogs_values_not_received_for_variation_with_feature_disabled() {
		$this->disable_cogs_feature();

		$parent_product = WC_Helper_Product::create_variation_product();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/products/{$parent_product->get_id()}/variations/{$parent_product->get_children()[0]}" ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayNotHasKey( 'cost_of_goods_sold', $data );
	}

	/**
	 * @testdox The variation GET endpoint returns the expected Cost of Goods information for a variation when the feature is enabled.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_additive_flag Value of the "additive" flag to use.
	 */
	public function test_cogs_values_received_for_variation_with_feature_enabled( bool $set_additive_flag ) {
		$this->enable_cogs_feature();

		$parent_product = WC_Helper_Product::create_variation_product();
		$parent_product->set_cogs_value( 12.34 );
		$parent_product->save();

		$variation = wc_get_product( $parent_product->get_children()[0] );
		$variation->set_cogs_value( 56.78 );
		$variation->set_cogs_value_is_additive( $set_additive_flag );
		$variation->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/products/{$parent_product->get_id()}/variations/{$variation->get_id()}" ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$expected_total_value = $set_additive_flag ? 12.34 + 56.78 : 56.78;
		$expected             = array(
			'values'                    => array(
				array(
					'defined_value'   => 56.78,
					'effective_value' => 56.78,
				),
			),
			'defined_value_is_additive' => $set_additive_flag,
			'total_value'               => $expected_total_value,
		);

		$this->assertEquals( $expected, $data['cost_of_goods_sold'] );
	}

	/**
	 * @testdox The variation POST endpoint properly updates the Cost of Goods information for a variation.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_additive_flag The value of the "additive" flag to use.
	 */
	public function test_set_cogs_value_for_variation_via_post_request( bool $set_additive_flag ) {
		$this->enable_cogs_feature();

		$variation = wc_get_product( ( WC_Helper_Product::create_variation_product() )->get_children()[0] );
		$variation->set_cogs_value_is_additive( $set_additive_flag );
		$variation->save();
		$this->assertEquals( 0, $variation->get_cogs_value() );

		$request_body = array(
			'cost_of_goods_sold' => array(
				'values' => array(
					array(
						'defined_value' => 12.34,
					),
					array(
						'defined_value' => 56.78,
					),
				),
			),
		);

		$this->update_variation_via_post_request( $variation, $request_body );

		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 12.34 + 56.78, $variation->get_cogs_value() );
		$this->assertEquals( $set_additive_flag, $variation->get_cogs_value_is_additive() );
	}

	/**
	 * @testdox The variation POST endpoint properly updates the Cost of Goods information for a variation.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_additive_flag The value of the "additive" flag to use.
	 */
	public function test_set_cogs_additive_field_for_variation_via_post_request( bool $set_additive_flag ) {
		$this->enable_cogs_feature();

		$variation = wc_get_product( ( WC_Helper_Product::create_variation_product() )->get_children()[0] );
		$variation->set_cogs_value( 12.34 );
		$variation->set_cogs_value_is_additive( ! $set_additive_flag );
		$variation->save();

		$request_body = array(
			'cost_of_goods_sold' => array(
				'defined_value_is_additive' => $set_additive_flag,
			),
		);

		$this->update_variation_via_post_request( $variation, $request_body );

		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 12.34, $variation->get_cogs_value() );
		$this->assertEquals( $set_additive_flag, $variation->get_cogs_value_is_additive() );
	}

	/**
	 * Perform a REST POST request to update a variation.
	 *
	 * @param WC_Product_Variation $variation The variation to update.
	 * @param array                $request_body Data to be sent (JSON-encoded) as the body of the request.
	 */
	private function update_variation_via_post_request( WC_Product_Variation $variation, array $request_body ) {
		$request = new WP_REST_Request( 'POST', "/wc/v3/products/{$variation->get_parent_id()}/variations/{$variation->get_id()}" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $request_body ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test that creating a variation with attributes containing special characters in their slug
	 * properly saves the attributes.
	 *
	 * This test verifies the fix for issue #61791 where attributes with non-ASCII characters
	 * (like Persian) were not saved when creating variations via the REST API.
	 * @see https://github.com/woocommerce/woocommerce/issues/61791
	 */
	public function test_create_variation_with_persian_attribute_by_id() {
		// Create a variable product with Persian attribute names.
		$product         = WC_Helper_Product::create_variation_product();
		$color_attribute = WC_Helper_Product::create_product_attribute_object( 'رنگ', array( 'blue', 'green' ) );
		$product->set_attributes( array( $color_attribute ) );
		$product->save();

		// Create a variation via REST API.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'regular_price'  => '0',
				'sale_price'     => '0',
				'manage_stock'   => true,
				'stock_quantity' => 0,
				'attributes'     => array(
					array(
						'id'     => $color_attribute->get_id(),
						'option' => 'green',
					),
				),
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		// Verify the variation was created successfully.
		$this->assertEquals( 201, $response->get_status() );
		$this->assertNotEmpty( $variation['id'] );
		$this->assertEquals( 'variation', $variation['type'] );
		$this->assertEquals( $product->get_id(), $variation['parent_id'] );

		// Verify the attribute is properly set.
		$this->assertNotEmpty( $variation['attributes'], 'Attributes array should not be empty' );
		$this->assertCount( 1, $variation['attributes'], 'Variation should have 1 attribute' );
		$this->assertEquals( 'رنگ', $variation['attributes'][0]['name'], 'Variation should contain color attribute' );
		$this->assertEquals( 'green', $variation['attributes'][0]['option'], 'Variation should contain color option green' );

		// Verify the variation can be retrieved and has the attribute data.
		$get_request         = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation['id'] );
		$get_response        = $this->server->dispatch( $get_request );
		$retrieved_variation = $get_response->get_data();

		$this->assertEquals( 200, $get_response->get_status() );
		$this->assertNotEmpty( $retrieved_variation['attributes'], 'Retrieved variation should have attributes' );
		$this->assertCount( 1, $retrieved_variation['attributes'], 'Retrieved variation should have 1 attribute' );
		$this->assertEquals( 'رنگ', $retrieved_variation['attributes'][0]['name'], 'Variation should contain color attribute' );
		$this->assertEquals( 'pa_رنگ', $retrieved_variation['attributes'][0]['slug'], 'Variation should contain color attribute slug' );
		$this->assertEquals( 'green', $retrieved_variation['attributes'][0]['option'], 'Variation should contain color option green' );
	}

	/**
	 * Test that creating a variation with URL-encoded attribute option values
	 * properly decodes and saves the attributes.
	 *
	 * This test verifies the fix for URL-encoded attribute options (e.g., special characters
	 * like Persian/unicode) are properly decoded when sent via the REST API.
	 */
	public function test_create_variation_with_url_encoded_attribute_option() {
		// Create a variable product with a Persian attribute name and options.
		$product         = WC_Helper_Product::create_variation_product();
		$color_attribute = WC_Helper_Product::create_product_attribute_object( 'رنگ', array( 'آبی', 'سبز' ) );
		$product->set_attributes( array( $color_attribute ) );
		$product->save();

		// Create a variation via REST API with URL-encoded attribute option.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'regular_price'  => '0',
				'sale_price'     => '0',
				'manage_stock'   => true,
				'stock_quantity' => 0,
				'attributes'     => array(
					array(
						'id'     => $color_attribute->get_id(),
						'option' => 'سبز', // URL-encoded Persian word for "green".
					),
				),
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		// Verify the variation was created successfully.
		$this->assertEquals( 201, $response->get_status() );
		$this->assertNotEmpty( $variation['id'] );
		$this->assertEquals( 'variation', $variation['type'] );
		$this->assertEquals( $product->get_id(), $variation['parent_id'] );

		// Verify the attribute is properly set with decoded option.
		$this->assertNotEmpty( $variation['attributes'], 'Attributes array should not be empty' );
		$this->assertCount( 1, $variation['attributes'], 'Variation should have 1 attribute' );
		$this->assertEquals( 'رنگ', $variation['attributes'][0]['name'], 'Variation should contain color attribute' );
		$this->assertEquals( 'سبز', $variation['attributes'][0]['option'], 'Attribute option should be decoded from URL encoding' );

		// Verify the variation can be retrieved and has the attribute data.
		$get_request         = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation['id'] );
		$get_response        = $this->server->dispatch( $get_request );
		$retrieved_variation = $get_response->get_data();

		$this->assertEquals( 200, $get_response->get_status() );
		$this->assertNotEmpty( $retrieved_variation['attributes'], 'Retrieved variation should have attributes' );
		$this->assertCount( 1, $retrieved_variation['attributes'], 'Retrieved variation should have 1 attribute' );
		$this->assertEquals( 'رنگ', $retrieved_variation['attributes'][0]['name'], 'Variation should contain color attribute' );
		$this->assertEquals( 'pa_رنگ', $retrieved_variation['attributes'][0]['slug'], 'Variation should contain color attribute slug' );
		$this->assertEquals( 'سبز', $retrieved_variation['attributes'][0]['option'], 'Retrieved attribute option should be decoded' );
	}

	/**
	 * Test that the products endpoint can filter by global_unique_id and also return matched variations.
	 *
	 * @return void
	 */
	public function test_products_variations_query_by_global_unique_id() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variation      = $parent_product->get_available_variations()[0];
		$variation      = wc_get_product( $variation['variation_id'] );
		$unique_id      = $parent_product->get_id() . '-1';
		$variation->set_global_unique_id( $unique_id );
		$variation->save();
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'global_unique_id' => $unique_id,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $response_products[0]['id'], $variation->get_id() );
	}

	/**
	 * Test `downloadable` filter returns only downloadable product variations.
	 */
	public function test_downloadable_filter_returns_only_downloadable_products() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_downloadable( true );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'downloadable', true );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $products );

		foreach ( $products as $product ) {
			$this->assertTrue( $product['downloadable'] );
		}
	}

	/**
	 * Test `downloadable` filter returns only non-downloadable products when is false.
	 */
	public function test_downloadable_filter_returns_only_non_downloadable_products() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_downloadable( true );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'downloadable', false );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $products );

		foreach ( $products as $product ) {
			$this->assertFalse( $product['downloadable'] );
		}
	}

	/**
	 * Test invalid downloadable parameter type returns error.
	 */
	public function test_downloadable_filter_with_invalid_param() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'downloadable', 'invalid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Test `virtual` filter returns only virtual product variations.
	 */
	public function test_virtual_filter_returns_only_virtual_products() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_virtual( true );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'virtual', true );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $products );

		foreach ( $products as $product ) {
			$this->assertTrue( $product['virtual'] );
		}
	}

	/**
	 * Test `virtual` filter returns only non-virtual products when is false.
	 */
	public function test_virtual_filter_returns_only_non_virtual_products() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_virtual( true );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'virtual', false );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $products );

		foreach ( $products as $product ) {
			$this->assertFalse( $product['virtual'] );
		}
	}

	/**
	 * Test invalid virtual parameter type returns error.
	 */
	public function test_virtual_filter_with_invalid_param() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'virtual', 'invalid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Test that the `include_status` parameter correctly filters product variations by a single status.
	 */
	public function test_collection_filter_with_single_include_status() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variation      = $parent_product->get_available_variations( 'objects' )[0];
		$variation->set_status( 'draft' );
		$variation->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'draft' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 1, $response_products );

		foreach ( $response_products as $product ) {
			$this->assertEquals( 'draft', $product['status'] );
		}
	}

	/**
	 * Test that the `include_status` parameter correctly filters product variations by multiple statuses.
	 *
	 * @return void
	 */
	public function test_collection_filter_with_multiple_include_status() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		$variations[0]->set_status( 'draft' );
		$variations[0]->save();

		$variations[1]->set_status( 'pending' );
		$variations[1]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'draft', 'pending' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 2, $response_products );

		$statuses = array_map(
			function ( $product ) {
				return $product['status'];
			},
			$response_products
		);

		$this->assertContains( 'draft', $statuses );
		$this->assertContains( 'pending', $statuses );
		$this->assertNotContains( 'private', $statuses );
		$this->assertNotContains( 'publish', $statuses );
		$this->assertNotContains( 'trash', $statuses );
		$this->assertNotContains( 'future', $statuses );
	}

	/**
	 * Test that the `include_status` parameter correctly handles invalid status values.
	 */
	public function test_collection_filter_with_invalid_include_status() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'invalid_status' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that the `include_status` parameter with any status returns all product variations.
	 */
	public function test_collection_filter_with_include_status_any() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		$all_statuses = array_keys( get_post_statuses() );
		foreach ( $all_statuses as $i => $status ) {
			$variations[ $i ]->set_status( $status );
			$variations[ $i ]->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'any' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$statuses = array_unique(
			array_map(
				function ( $product ) {
					return $product['status'];
				},
				$response_products
			)
		);

		$this->assertCount( 4, $statuses );
		$this->assertEqualsCanonicalizing(
			$all_statuses,
			$statuses
		);
	}

	/**
	 *  Test that the `exclude_status` parameter correctly filters product variations by a single status.
	 */
	public function test_collection_filter_with_single_exclude_status() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'exclude_status' => array( 'publish' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 0, $response_products );

		foreach ( $response_products as $product ) {
			$this->assertEquals( 'draft', $product['status'] );
		}
	}

	/**
	 * Test that `exclude_status` parameter correctly excludes multiple product variations.
	 */
	public function test_collection_filter_with_multiple_exclude_status() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		$variations[0]->set_status( 'private' );
		$variations[0]->save();

		$variations[1]->set_status( 'draft' );
		$variations[1]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'exclude_status' => array( 'draft', 'private' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$statuses = array_unique( array_column( $response_products, 'status' ) );

		$this->assertNotContains( 'draft', $statuses );
		$this->assertNotContains( 'private', $statuses );
	}

	/**
	 * Test that empty `exclude_status` parameter returns all products.
	 */
	public function test_collection_filter_with_empty_exclude_status() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'exclude_status' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( count( $variations ), $response_products );
	}

	/**
	 * Test that `exclude_status` parameter validation handles invalid values.
	 */
	public function test_collection_filter_with_valid_invalid_exclude_status() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'exclude_status' => array( 'draft', 'invalid_status' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that `exclude_status` with all statuses returns empty.
	 */
	public function test_collection_filter_exclude_status_with_all_statuses_returns_empty() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$all_statuses = array_merge( array( 'future', 'trash' ), array_keys( get_post_statuses() ) );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'exclude_status' => $all_statuses,
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test that `exclude_status` parameter takes precedence over `include_status`.
	 */
	public function test_collection_filter_exclude_status_precedence_over_include() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_status( 'draft' );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'draft', 'publish' ),
				'exclude_status' => array( 'publish' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$statuses = array_unique( array_column( $response->get_data(), 'status' ) );

		$this->assertContains( 'draft', $statuses );
		$this->assertNotContains( 'publish', $statuses );
	}

	/**
	 * Test that `exclude_status` works correctly when `include_status` is 'any'.
	 */
	public function test_collection_filter_exclude_status_with_include_any() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );
		$variations[0]->set_status( 'draft' );
		$variations[0]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'any' ),
				'exclude_status' => array( 'publish' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$statuses = array_unique( array_column( $response->get_data(), 'status' ) );

		$this->assertEqualsCanonicalizing(
			array( 'draft' ),
			$statuses
		);
		$this->assertNotContains( 'publish', $statuses );
	}

	/**
	 * Test that `exclude_status` works correctly with specific `include_status` values.
	 */
	public function test_collection_filter_exclude_status_with_specific_includes() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		$variations[0]->set_status( 'draft' );
		$variations[0]->save();
		$variations[1]->set_status( 'private' );
		$variations[1]->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'include_status' => array( 'draft', 'private' ),
				'exclude_status' => array( 'private', 'draft' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test that `exclude_status` works correctly with the 'status' parameter.
	 */
	public function test_collection_filter_exclude_status_with_status_param() {
		$parent_product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_query_params(
			array(
				'status'         => 'publish',
				'exclude_status' => array( 'publish' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test `pos_products_only` filter returns only POS-visible variations when true.
	 */
	public function test_pos_products_only_true_returns_only_pos_visible_variations() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		// Mark one variation as hidden from POS.
		wp_set_object_terms( $variations[0]->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'pos_products_only', true );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();
		$variation_ids = wp_list_pluck( $response_data, 'id' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotContains( $variations[0]->get_id(), $variation_ids );
		$this->assertContains( $variations[1]->get_id(), $variation_ids );
	}

	/**
	 * Test `pos_products_only` filter returns all variations when false.
	 */
	public function test_pos_products_only_false_returns_all_variations() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		// Mark one variation as hidden from POS.
		wp_set_object_terms( $variations[0]->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		$request->set_param( 'pos_products_only', false );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();
		$variation_ids = wp_list_pluck( $response_data, 'id' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( $variations[0]->get_id(), $variation_ids );
		$this->assertContains( $variations[1]->get_id(), $variation_ids );
	}

	/**
	 * Test that omitting `pos_products_only` filter returns all variations regardless of POS visibility.
	 */
	public function test_pos_products_only_omitted_returns_all_variations() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_available_variations( 'objects' );

		// Mark one variation as hidden from POS.
		wp_set_object_terms( $variations[0]->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $parent_product->get_id() . '/variations' );
		// Do not set pos_products_only parameter.

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();
		$variation_ids = wp_list_pluck( $response_data, 'id' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( $variations[0]->get_id(), $variation_ids );
		$this->assertContains( $variations[1]->get_id(), $variation_ids );
	}
}
