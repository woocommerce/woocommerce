<?php
/**
 * Tests for Variations API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

class Product_Variations_API extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Product_Variations_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.0.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/products/(?P<product_id>[\d]+)/variations', $routes );
		$this->assertArrayHasKey( '/wc/v2/products/(?P<product_id>[\d]+)/variations/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v2/products/(?P<product_id>[\d]+)/variations/batch', $routes );
	}

	/**
	 * Test getting variations.
	 *
	 * @since 3.0.0
	 */
	public function test_get_variations() {
		wp_set_current_user( $this->user );
		$product    = WC_Helper_Product::create_variation_product();
		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $variations ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE LARGE', $variations[0]['sku'] );
		$this->assertEquals( 'size', $variations[0]['attributes'][0]['name'] );
	}

	/**
	 * Test getting variations without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_get_variations_without_permission() {
		wp_set_current_user( 0 );
		$product  = WC_Helper_Product::create_variation_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single variation.
	 *
	 * @since 3.0.0
	 */
	public function test_get_variation() {
		wp_set_current_user( $this->user );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $variation_id, $variation['id'] );
		$this->assertEquals( 'size', $variation['attributes'][0]['name'] );
	}

	/**
	 * Test getting single variation without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_get_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];
		$response     = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single variation.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_variation() {
		wp_set_current_user( $this->user );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 1, count( $variations ) );
	}

	/**
	 * Test deleting a single variation without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single variation with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_variation_with_invalid_id() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_variation_product();
		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/variations/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test editing a single variation.
	 *
	 * @since 3.0.0
	 */
	public function test_update_variation() {
		wp_set_current_user( $this->user );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$variation = $response->get_data();

		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variation['sku'] );
		$this->assertEquals( 10, $variation['regular_price'] );
		$this->assertEmpty( $variation['sale_price'] );
		$this->assertEquals( 'small', $variation['attributes'][0]['option'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'sku'         => 'FIXED-SKU',
				'sale_price'  => '8',
				'description' => 'O_O',
				'image'       => array(
					'position' => 0,
					'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
					'alt'      => 'test upload image',
				),
				'attributes'  => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertTrue( isset( $variation['description'] ), print_r( $variation, true ) );
		$this->assertContains( 'O_O', $variation['description'], print_r( $variation, true ) );
		$this->assertEquals( '8', $variation['price'], print_r( $variation, true ) );
		$this->assertEquals( '8', $variation['sale_price'], print_r( $variation, true ) );
		$this->assertEquals( '10', $variation['regular_price'], print_r( $variation, true ) );
		$this->assertEquals( 'FIXED-SKU', $variation['sku'], print_r( $variation, true ) );
		$this->assertEquals( 'medium', $variation['attributes'][0]['option'], print_r( $variation, true ) );
		$this->assertContains( 'Dr1Bczxq4q', $variation['image']['src'], print_r( $variation, true ) );
		$this->assertContains( 'test upload image', $variation['image']['alt'], print_r( $variation, true ) );
	}

	/**
	 * Test updating a single variation without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_update_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single variation with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_update_variation_with_invalid_id() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_variation_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/0' );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single variation.
	 *
	 * @since 3.0.0
	 */
	public function test_create_variation() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_variation_product();

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 2, count( $variations ) );

		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
				'regular_price' => '12',
				'description'   => 'A medium size.',
				'attributes'    => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertContains( 'A medium size.', $variation['description'] );
		$this->assertEquals( '12', $variation['price'] );
		$this->assertEquals( '12', $variation['regular_price'] );
		$this->assertTrue( $variation['purchasable'] );
		$this->assertEquals( 'DUMMY SKU VARIABLE MEDIUM', $variation['sku'] );
		$this->assertEquals( 'medium', $variation['attributes'][0]['option'] );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 3, count( $variations ) );
	}

	/**
	 * Test creating a single variation without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_create_variation_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_variation_product();

		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
				'regular_price' => '12',
				'description'   => 'A medium size.',
				'attributes'    => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch managing product variations.
	 */
	public function test_product_variations_batch() {
		wp_set_current_user( $this->user );
		$product  = WC_Helper_Product::create_variation_product();
		$children = $product->get_children();
		$request  = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/variations/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'          => $children[0],
						'description' => 'Updated description.',
						'image'       => array(
							'position' => 0,
							'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
							'alt'      => 'test upload image',
						),
					),
				),
				'delete' => array(
					$children[1],
				),
				'create' => array(
					array(
						'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
						'regular_price' => '12',
						'description'   => 'A medium size.',
						'attributes'    => array(
							array(
								'name'   => 'pa_size',
								'option' => 'medium',
							),
						),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertContains( 'Updated description.', $data['update'][0]['description'] );
		$this->assertEquals( 'DUMMY SKU VARIABLE MEDIUM', $data['create'][0]['sku'] );
		$this->assertEquals( 'medium', $data['create'][0]['attributes'][0]['option'] );
		$this->assertEquals( $children[1], $data['delete'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/variations' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 2, count( $data ) );
	}

	/**
	 * Test variation schema.
	 *
	 * @since 3.0.0
	 */
	public function test_variation_schema() {
		wp_set_current_user( $this->user );
		$product    = WC_Helper_Product::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/products/' . $product->get_id() . '/variations' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 37, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'permalink', $properties );
		$this->assertArrayHasKey( 'sku', $properties );
		$this->assertArrayHasKey( 'price', $properties );
		$this->assertArrayHasKey( 'regular_price', $properties );
		$this->assertArrayHasKey( 'sale_price', $properties );
		$this->assertArrayHasKey( 'date_on_sale_from', $properties );
		$this->assertArrayHasKey( 'date_on_sale_to', $properties );
		$this->assertArrayHasKey( 'on_sale', $properties );
		$this->assertArrayHasKey( 'visible', $properties );
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
		$this->assertArrayHasKey( 'in_stock', $properties );
		$this->assertArrayHasKey( 'backorders', $properties );
		$this->assertArrayHasKey( 'backorders_allowed', $properties );
		$this->assertArrayHasKey( 'backordered', $properties );
		$this->assertArrayHasKey( 'weight', $properties );
		$this->assertArrayHasKey( 'dimensions', $properties );
		$this->assertArrayHasKey( 'shipping_class', $properties );
		$this->assertArrayHasKey( 'shipping_class_id', $properties );
		$this->assertArrayHasKey( 'image', $properties );
		$this->assertArrayHasKey( 'attributes', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'meta_data', $properties );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 3.0.0
	 */
	public function test_update_variation_manage_stock() {
		wp_set_current_user( $this->user );

		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( false );
		$product->save();

		$children     = $product->get_children();
		$variation_id = $children[0];

		// Set stock to true.
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => true,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $variation['manage_stock'] );

		// Set stock to false.
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => false,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( false, $variation['manage_stock'] );

		// Set stock to false but parent is managing stock.
		$product->set_manage_stock( true );
		$product->save();
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => false,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'parent', $variation['manage_stock'] );
	}

	/**
	 * Test getting product variations filtered by product category.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations_by_category() {
		wp_set_current_user( $this->user );

		// Create product assigned to a single category.
		$category = wp_insert_term( 'Some Category', 'product_cat' );
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_category_ids( array( $category['term_id'] ) );
		$variable_product->save();
		$variations = $variable_product->get_children();

		$query_params = array(
			'category' => (string) $category['term_id'],
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable_product->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $variations ), count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertContains( $response_variation['id'], $variations );
		}

		// Clean up.
		wp_delete_term( $category['term_id'], 'product_cat' );
		$variable_product->delete( true );
	}

	/**
	 * Test getting variations filtered by product type.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations_by_type() {
		wp_set_current_user( $this->user );

		$simple = WC_Helper_Product::create_simple_product();
		$external = WC_Helper_Product::create_external_product();
		$grouped = WC_Helper_Product::create_grouped_product();
		$variable = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();

		$query_params = array(
			'type' => 'variable',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $variations ), count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertContains( $response_variation['id'], $variations );
		}

		$query_params = array(
			'type' => 'simple',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response_variations );

		$query_params = array(
			'type' => 'external',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response_variations );

		$query_params = array(
			'type' => 'grouped',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response_variations );

		$simple->delete( true );
		$external->delete( true );
		$variable->delete( true );
		$grouped->delete( true );
	}

	/**
	 * Test getting variations by featured property.
	 *
	 * @since 3.5.0
	 */
	public function test_get_featured_variations() {
		wp_set_current_user( $this->user );

		// Create a featured product.
		$feat_product    = WC_Helper_Product::create_variation_product();
		$feat_product->set_featured( true );
		$feat_product->save();
		$feat_variations = $feat_product->get_children();

		// Create a non-featured product.
		$nonfeat_product    = WC_Helper_Product::create_variation_product();
		$nonfeat_product->save();
		$nonfeat_variations = $nonfeat_product->get_children();

		$query_params = array(
			'featured' => 'true',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $feat_product->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $feat_variations ), count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertContains( $response_variation['id'], $feat_variations );
		}

		$query_params = array(
			'featured' => 'false',
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $nonfeat_product->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $nonfeat_variations ), count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertContains( $response_variation['id'], $nonfeat_variations );
		}

		$feat_product->delete( true );
		$nonfeat_product->delete( true );
	}

	/**
	 * Test getting products by shipping class property.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations_by_shipping_class() {
		wp_set_current_user( $this->user );

		// Shipping class can be set on product and variation level.
		$shipping_class_1 = wp_insert_term( 'Bulky', 'product_shipping_class' );
		$shipping_class_2 = wp_insert_term( 'Light', 'product_shipping_class' );

		// Default shipping class for variations is set to Light.
		$variable   = WC_Helper_Product::create_variation_product();
		$variable->set_shipping_class_id( $shipping_class_2['term_id'] );
		$variations = $variable->get_available_variations();

		// Bulky shipping class for first variation.
		$variation_0 = wc_get_product( $variations[0]['variation_id'] );
		$variation_0->set_shipping_class_id( $shipping_class_1['term_id'] );
		$variation_0->save();

		$variable->save();

		// Test Bulky shipping class.
		$query_params = array(
			'shipping_class' => (string) $shipping_class_1['term_id'],
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertEquals( $response_variation['id'], $variations[0]['variation_id'] );
		}

		// Test Light shipping class.
		$query_params = array(
			'shipping_class' => (string) $shipping_class_2['term_id'],
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertEquals( $response_variation['id'], $variations[1]['variation_id'] );
		}

		$variable->delete( true );
		wp_delete_term( $shipping_class_1['term_id'], 'product_shipping_class' );
		wp_delete_term( $shipping_class_2['term_id'], 'product_shipping_class' );
	}

	/**
	 * Test getting variations filtered by tag.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations_by_tag() {
		wp_set_current_user( $this->user );

		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );

		// Variable product with a tag.
		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$variable_product->save();
		$variations       = $variable_product->get_children();

		// Variable product without a tag.
		$variable_product_2 = WC_Helper_Product::create_variation_product();

		$query_params = array(
			'tag' => (string) $test_tag_1['term_id'],
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable_product->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $variations ), count( $response_variations ) );
		foreach ( $response_variations as $response_variation ) {
			$this->assertContains( $response_variation['id'], $variations );
		}

		$query_params = array(
			'tag' => (string) $test_tag_1['term_id'],
		);
		$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable_product_2->get_id() . '/variations' );
		$request->set_query_params( $query_params );
		$response            = $this->server->dispatch( $request );
		$response_variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $response_variations ) );

		$variable_product->delete( true );
		$variable_product_2->delete( true );
		wp_delete_term( $test_tag_1['term_id'], 'product_tag' );
	}

	/**
	 * Test getting product variations by global attribute.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_by_attribute() {
		global $wpdb;
		wp_set_current_user( $this->user );

		// Variable product with 2 different variations.
		$variable_product = WC_Helper_Product::create_variation_product();

		// Create one variation without attribute value.
		$variation_3 = new WC_Product_Variation();
		$variation_3->set_props( array(
			'parent_id'     => $variable_product->get_id(),
			'sku'           => 'DUMMY SKU VARIABLE NOSIZE',
			'regular_price' => 16,
		) );
		$variation_3->save();

		$variations       = $variable_product->get_available_variations();

		foreach ( $variations as $variation ) {
			$attrib_value = isset( $variation['attributes']['attribute_pa_size'] ) ? $variation['attributes']['attribute_pa_size'] : null;
			// Skip testing without attribute.
			if ( ! $attrib_value ) {
				continue;
			}
			$query_params = array(
				'attribute'      => 'pa_size',
				'attribute_term' => (string) get_term_by( 'slug', $attrib_value, 'pa_size' )->term_id,
			);
			$expected_product_ids = array( $variation['variation_id'] );
			$request             = new WP_REST_Request( 'GET', '/wc/v2/products/' . $variable_product->get_id() . '/variations' );
			$request->set_query_params( $query_params );
			$response            = $this->server->dispatch( $request );
			$response_variations = $response->get_data();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertEquals( 1, count( $response_variations ) );
			foreach ( $response_variations as $response_variation ) {
				$this->assertContains( $response_variation['id'], $expected_product_ids );
			}
		}

		$variable_product->delete( true );
	}
}
