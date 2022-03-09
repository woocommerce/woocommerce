<?php

/**
 * class WC_REST_Products_Controller_Tests.
 * Product Controller tests for V3 REST API.
 */
class WC_REST_Products_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Products_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Get all expected fields.
	 */
	public function get_expected_response_fields() {
		return array(
			'id',
			'name',
			'slug',
			'permalink',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'type',
			'status',
			'featured',
			'catalog_visibility',
			'description',
			'short_description',
			'sku',
			'price',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_from_gmt',
			'date_on_sale_to',
			'date_on_sale_to_gmt',
			'price_html',
			'on_sale',
			'purchasable',
			'total_sales',
			'virtual',
			'downloadable',
			'downloads',
			'download_limit',
			'download_expiry',
			'external_url',
			'button_text',
			'tax_status',
			'tax_class',
			'manage_stock',
			'stock_quantity',
			'stock_status',
			'backorders',
			'backorders_allowed',
			'backordered',
			'low_stock_amount',
			'sold_individually',
			'weight',
			'dimensions',
			'shipping_required',
			'shipping_taxable',
			'shipping_class',
			'shipping_class_id',
			'reviews_allowed',
			'average_rating',
			'rating_count',
			'related_ids',
			'upsell_ids',
			'cross_sell_ids',
			'parent_id',
			'purchase_note',
			'categories',
			'tags',
			'images',
			'has_options',
			'attributes',
			'default_attributes',
			'variations',
			'grouped_products',
			'menu_order',
			'meta_data',
		);
	}

	/**
	 * Test that all expected response fields are present.
	 * Note: This has fields hardcoded intentionally instead of fetching from schema to test for any bugs in schema result. Add new fields manually when added to schema.
	 */
	public function test_product_api_get_all_fields() {
		wp_set_current_user( $this->user );
		$expected_response_fields = $this->get_expected_response_fields();

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertEquals( 200, $response->get_status() );

		$response_fields = array_keys( $response->get_data() );

		$this->assertEmpty( array_diff( $expected_response_fields, $response_fields ), 'These fields were expected but not present in API response: ' . print_r( array_diff( $expected_response_fields, $response_fields ), true ) );

		$this->assertEmpty( array_diff( $response_fields, $expected_response_fields ), 'These fields were not expected in the API response: ' . print_r( array_diff( $response_fields, $expected_response_fields ), true ) );
	}

	/**
	 * Test that `get_product_data` function works without silent `request` parameter as it used to.
	 * TODO: Fix the underlying design issue when DI gets available.
	 */
	public function test_get_product_data_should_work_without_request_param() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		// Workaround to call protected method.
		$call_product_data_wrapper = function () use ( $product ) {
			return $this->get_product_data( $product );
		};
		$response = $call_product_data_wrapper->call( new WC_REST_Products_Controller() );
		$this->assertArrayHasKey( 'id', $response );
	}

	/**
	 * Test that all fields are returned when requested one by one.
	 */
	public function test_products_get_each_field_one_by_one() {
		wp_set_current_user( $this->user );
		$expected_response_fields = $this->get_expected_response_fields();
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		foreach ( $expected_response_fields as $field ) {
			$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
			$request->set_param( '_fields', $field );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$response_fields = array_keys( $response->get_data() );

			$this->assertContains( $field, $response_fields, "Field $field was expected but not present in product API response." );
		}
	}

	/**
	 * Test that products with partial name or SKU case-insensitive match are returned given both `search` and `search_includes_sku` parameters.
	 */
	public function test_products_with_search_param_including_sku_returns_products_with_sku_and_name_match() {
		// Given.
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WooCommerce Tests' ) );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WordCamp' ) );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WoO' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'Woo' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'wordpress' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => '*sunglasses-woo*' ) );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'              => 'woo',
				'search_includes_sku' => true,
				'order'               => 'asc',
				'orderby'             => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		// Then.
		$this->assertEquals( 4, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'WooCommerce Tests' );
		$this->assertEquals( $response_products[1]['name'], 'WoO' );
		$this->assertEquals( $response_products[2]['sku'], 'Woo' );
		$this->assertEquals( $response_products[3]['sku'], '*sunglasses-woo*' );
	}

	/**
	 * Test that only products with name match are returned given `search` without `search_includes_sku` parameter.
	 */
	public function test_products_with_search_param_only_returns_products_with_name_match() {
		// Given.
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WooCommerce Tests' ) );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WordCamp' ) );
		WC_Helper_Product::create_simple_product( true, array( 'name' => 'WoO' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'Woo' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'wordpress' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => '*sunglasses-woo*' ) );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'  => 'woo',
				'order'   => 'asc',
				'orderby' => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		// Then.
		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'WooCommerce Tests' );
		$this->assertEquals( $response_products[1]['name'], 'WoO' );
	}

	/**
	 * Test that no products with SKU are returned given `search` and `search_includes_sku` parameters when product SKU is disabled.
	 */
	public function test_products_with_search_param_including_sku_returns_no_products_when_sku_is_disabled() {
		// Given.
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'Woo' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'wordpress' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => '*sunglasses-woo*' ) );

		// When.
		add_filter( 'wc_product_sku_enabled', '__return_false' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'              => 'woo',
				'search_includes_sku' => true,
				'order'               => 'asc',
				'orderby'             => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		// Then.
		$this->assertEquals( 0, count( $response_products ) );
	}

	/**
	 * Test that the first product with full SKU case-insensitive match are returned given a `sku` parameter.
	 */
	public function test_products_with_sku_param_returns_the_first_product_with_full_sku_match() {
		// Given.
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => '0oWoO' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'WoO' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'woo' ) );
		WC_Helper_Product::create_simple_product( true, array( 'sku' => 'woo-sunglasses' ) );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'sku'     => 'woo',
				'order'   => 'asc',
				'orderby' => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		// Then.
		$this->assertEquals( 1, count( $response_products ) );
		$response_product = $response_products[0];
		$this->assertEquals( 'WoO', $response_product['sku'] );
	}
}
