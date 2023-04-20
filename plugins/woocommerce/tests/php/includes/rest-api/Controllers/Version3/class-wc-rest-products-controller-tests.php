<?php

/**
 * class WC_REST_Products_Controller_Tests.
 * Product Controller tests for V3 REST API.
 */
class WC_REST_Products_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * @var WC_Product_Simple[]
	 */
	protected static $products = array();

	/**
	 * Create products for tests.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass() {
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Pancake',
				'sku'  => 'pancake-1',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Waffle 1',
				'sku'  => 'pancake-2',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'French Toast',
				'sku'  => 'waffle-2',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Waffle 3',
				'sku'  => 'waffle-3',
			)
		);

		foreach ( self::$products as $product ) {
			$product->add_meta_data( 'test1', 'test1', true );
			$product->add_meta_data( 'test2', 'test2', true );
			$product->save();
		}
	}

	/**
	 * Clean up products after tests.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		foreach ( self::$products as $product ) {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

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
		wp_set_current_user( $this->user );
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
	 * Test that featured field exists under images in product argument.
	 */
	public function test_products_args_includes_featured_field() {
		$request = new WP_REST_Request( 'OPTIONS', '/wc/v3/products' );
		$request->set_param( '_fields', 'endpoints.1.args.images.items.properties' );
		$response  = $this->server->dispatch( $request );
		$image_arg = $response->get_data()['endpoints'][1]['args']['images']['items']['properties'];
		$this->assertArrayHasKey( 'featured', $image_arg );
		$this->assertEquals(
			array(
				'description' => 'Featured image.',
				'type'        => 'boolean',
				'default'     => false,
				'context'     =>
				array(
					0 => 'view',
					1 => 'edit',
				),
			),
			$image_arg['featured']
		);
	}

	/**
	 * Test that featured field exists under images in product schema.
	 */
	public function test_products_schema_includes_featured_field() {
		$request = new WP_REST_Request( 'OPTIONS', '/wc/v3/products' );
		$request->set_param( '_fields', 'schema.properties.images.items.properties' );
		$response     = $this->server->dispatch( $request );
		$image_schema = $response->get_data()['schema']['properties']['images']['items']['properties'];
		$this->assertArrayHasKey( 'featured', $image_schema );
		$this->assertEquals(
			array(
				'description' => 'Featured image.',
				'type'        => 'boolean',
				'default'     => false,
				'context'     =>
				array(
					0 => 'view',
					1 => 'edit',
				),
			),
			$image_schema['featured']
		);
	}

	/**
	 * Test that feature field is returned inside images.
	 */
	public function test_products_get_images_array_contains_featured() {
		global $wpdb;

		$product     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$gallery_url = media_sideload_image( 'https://cldup.com/6L9h56D9Bw.jpg', $product->get_id(), 'gallery', 'src' );
		$this->assertNotWPError( $gallery_url );
		$gallery_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $gallery_url ) );

		$featured_url = media_sideload_image( 'https://cldup.com/Dr1Bczxq4q.png', $product->get_id(), 'featured', 'src' );
		$this->assertNotWPError( $featured_url );
		$featured_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $featured_url ) );

		$product->set_image_id( $featured_id[0] );
		$product->set_gallery_image_ids( $gallery_id[0] );
		$product->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
		$request->set_param( '_fields', 'images' );
		$response     = $this->server->dispatch( $request );
		$images_array = $response->get_data()['images'];
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $images_array[0]['featured'] );
		$this->assertEquals( 'featured', $images_array[0]['name'] );
		$this->assertFalse( $images_array[1]['featured'] );
		$this->assertEquals( 'gallery', $images_array[1]['name'] );
	}

	/**
	 * Test that the `search` parameter does partial matching in the product name, but not the SKU.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_param_only() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'  => 'waffle',
				'order'   => 'asc',
				'orderby' => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 1' );
		$this->assertEquals( $response_products[0]['sku'], 'pancake-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );
	}

	/**
	 * Test that the `search_sku` parameter does partial matching in the product SKU, but not the name.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_sku_param_only() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'French Toast' );
		$this->assertEquals( $response_products[0]['sku'], 'waffle-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );
	}

	/**
	 * Test that using the `search` and `search_sku` parameters together only matches when both match.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_and_search_sku_param() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'     => 'waffle',
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[0]['sku'], 'waffle-3' );
	}

	/**
	 * Test that the `search_sku` parameter does nothing when product SKUs are disabled.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_sku_when_skus_disabled() {
		add_filter( 'wc_product_sku_enabled', '__return_false' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'     => 'waffle',
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 1' );
		$this->assertEquals( $response_products[0]['sku'], 'pancake-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );

		remove_filter( 'wc_product_sku_enabled', '__return_false' );
	}

	/**
	 * Test that the `include_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_include_meta() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 4, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param is skipped when empty.
	 */
	public function test_collection_param_include_meta_empty() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', '' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 4, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
			$this->assertContains( 'test2', $meta_keys );
		}
	}

	/**
	 * Test that the `exclude_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_exclude_meta() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 4, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test2', $meta_keys );
			$this->assertNotContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param overrides the `exclude_meta` param.
	 */
	public function test_collection_param_include_meta_override() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test1' );
		$request->set_param( 'exclude_meta', 'test1' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( 4, $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the meta_data property contains an array, and not an object, after being filtered.
	 */
	public function test_collection_param_include_meta_returns_array() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test2' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data       = $this->server->response_to_data( $response, false );
		$encoded_data_string = wp_json_encode( $response_data );
		$decoded_data_object = json_decode( $encoded_data_string, false ); // Ensure object instead of associative array.

		$this->assertIsArray( $decoded_data_object[0]->meta_data );
	}
}
