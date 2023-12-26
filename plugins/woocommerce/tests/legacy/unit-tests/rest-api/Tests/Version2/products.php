<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Products_API_V2 class.
 */
class Products_API_V2 extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

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
	 * Test route registration.
	 *
	 * @since 3.0.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/products', $routes );
		$this->assertArrayHasKey( '/wc/v2/products/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v2/products/batch', $routes );
	}

	/**
	 * Test getting products.
	 *
	 * @since 3.0.0
	 */
	public function test_get_products() {
		wp_set_current_user( $this->user );
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products' ) );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );
		$this->assertEquals( 'DUMMY SKU', $products[0]['sku'] );
		$this->assertEquals( 'Dummy External Product', $products[1]['name'] );
		$this->assertEquals( 'DUMMY EXTERNAL SKU', $products[1]['sku'] );
	}

	/**
	 * Test getting products without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_get_products_without_permission() {
		wp_set_current_user( 0 );
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_get_product() {
		wp_set_current_user( $this->user );
		$simple   = ProductHelper::create_external_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArraySubset(
			array(
				'id'            => $simple->get_id(),
				'name'          => 'Dummy External Product',
				'type'          => 'external',
				'status'        => 'publish',
				'sku'           => 'DUMMY EXTERNAL SKU',
				'regular_price' => '10',
			),
			$product
		);
	}

	/**
	 * Test getting single product without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_get_product_without_permission() {
		wp_set_current_user( 0 );
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_product() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products' ) );
		$variations = $response->get_data();
		$this->assertEquals( 0, count( $variations ) );
	}

	/**
	 * Test deleting a single product without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_product_without_permission() {
		wp_set_current_user( 0 );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single product with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_product_with_invalid_id() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product() {
		wp_set_current_user( $this->user );

		// test simple products.
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 'DUMMY SKU', $data['sku'] );
		$this->assertEquals( 10, $data['regular_price'] );
		$this->assertEmpty( $data['sale_price'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'sku'         => 'FIXED-SKU',
				'sale_price'  => '8',
				'description' => 'Testing',
				'images'      => array(
					array(
						'position' => 0,
						'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
						'alt'      => 'test upload image',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'Testing', $data['description'] );
		$this->assertEquals( '8', $data['price'] );
		$this->assertEquals( '8', $data['sale_price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertEquals( 'FIXED-SKU', $data['sku'] );
		$this->assertStringContainsString( 'Dr1Bczxq4q', $data['images'][0]['src'] );
		$this->assertStringContainsString( 'test upload image', $data['images'][0]['alt'] );
		$product->delete( true );
		wp_delete_attachment( $data['images'][0]['id'], true );

		// test variable product (variations are tested in product-variations.php).
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		foreach ( array( 'small', 'large' ) as $term_name ) {
			$this->assertContains( $term_name, $data['attributes'][0]['options'] );
		}

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'attributes' => array(
					array(
						'id'        => 0,
						'name'      => 'pa_color',
						'options'   => array(
							'red',
							'yellow',
						),
						'visible'   => false,
						'variation' => 1,
					),
					array(
						'id'        => 0,
						'name'      => 'pa_size',
						'options'   => array(
							'small',
						),
						'visible'   => false,
						'variation' => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'small' ), $data['attributes'][0]['options'] );
		$this->assertEquals( array( 'red', 'yellow' ), $data['attributes'][1]['options'] );
		$product->delete( true );

		// test external product.
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_external_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 'Buy external product', $data['button_text'] );
		$this->assertEquals( 'https://woo.com', $data['external_url'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'button_text'  => 'Test API Update',
				'external_url' => 'http://automattic.com',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'Test API Update', $data['button_text'] );
		$this->assertEquals( 'http://automattic.com', $data['external_url'] );
	}

	/**
	 * Test updating a single product without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product_without_permission() {
		wp_set_current_user( 0 );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single product with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product_with_invalid_id() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/0' );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-INVALID-ID',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_create_product() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v2/products/shipping_classes' );
		$request->set_body_params(
			array(
				'name' => 'Test',
			)
		);
		$response          = $this->server->dispatch( $request );
		$data              = $response->get_data();
		$shipping_class_id = $data['id'];

		// Create simple.
		$request = new WP_REST_Request( 'POST', '/wc/v2/products' );
		$request->set_body_params(
			array(
				'type'           => 'simple',
				'name'           => 'Test Simple Product',
				'sku'            => 'DUMMY SKU SIMPLE API',
				'regular_price'  => '10',
				'shipping_class' => 'test',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '10', $data['price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertTrue( $data['purchasable'] );
		$this->assertEquals( 'DUMMY SKU SIMPLE API', $data['sku'] );
		$this->assertEquals( 'Test Simple Product', $data['name'] );
		$this->assertEquals( 'simple', $data['type'] );
		$this->assertEquals( $shipping_class_id, $data['shipping_class_id'] );

		// Create external.
		$request = new WP_REST_Request( 'POST', '/wc/v2/products' );
		$request->set_body_params(
			array(
				'type'          => 'external',
				'name'          => 'Test External Product',
				'sku'           => 'DUMMY SKU EXTERNAL API',
				'regular_price' => '10',
				'button_text'   => 'Test Button',
				'external_url'  => 'https://wordpress.org',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '10', $data['price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertFalse( $data['purchasable'] );
		$this->assertEquals( 'DUMMY SKU EXTERNAL API', $data['sku'] );
		$this->assertEquals( 'Test External Product', $data['name'] );
		$this->assertEquals( 'external', $data['type'] );
		$this->assertEquals( 'Test Button', $data['button_text'] );
		$this->assertEquals( 'https://wordpress.org', $data['external_url'] );

		// Create variable.
		$request = new WP_REST_Request( 'POST', '/wc/v2/products' );
		$request->set_body_params(
			array(
				'type'       => 'variable',
				'name'       => 'Test Variable Product',
				'sku'        => 'DUMMY SKU VARIABLE API',
				'attributes' => array(
					array(
						'id'        => 0,
						'name'      => 'pa_size',
						'options'   => array(
							'small',
							'medium',
						),
						'visible'   => false,
						'variation' => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'DUMMY SKU VARIABLE API', $data['sku'] );
		$this->assertEquals( 'Test Variable Product', $data['name'] );
		$this->assertEquals( 'variable', $data['type'] );
		$this->assertEquals( array( 'small', 'medium' ), $data['attributes'][0]['options'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products' ) );
		$products = $response->get_data();
		$this->assertEquals( 3, count( $products ) );
	}

	/**
	 * Test creating a single product without permission.
	 *
	 * @since 3.0.0
	 */
	public function test_create_product_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wc/v2/products' );
		$request->set_body_params(
			array(
				'name'          => 'Test Product',
				'regular_price' => '12',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch managing products.
	 */
	public function test_products_batch() {
		wp_set_current_user( $this->user );
		$product   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$product_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request   = new WP_REST_Request( 'POST', '/wc/v2/products/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'          => $product->get_id(),
						'description' => 'Updated description.',
					),
				),
				'delete' => array(
					$product_2->get_id(),
				),
				'create' => array(
					array(
						'sku'           => 'DUMMY SKU BATCH TEST 1',
						'regular_price' => '10',
						'name'          => 'Test Batch Create 1',
						'type'          => 'external',
						'button_text'   => 'Test Button',
					),
					array(
						'sku'           => 'DUMMY SKU BATCH TEST 2',
						'regular_price' => '20',
						'name'          => 'Test Batch Create 2',
						'type'          => 'simple',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'Updated description.', $data['update'][0]['description'] );
		$this->assertEquals( 'DUMMY SKU BATCH TEST 1', $data['create'][0]['sku'] );
		$this->assertEquals( 'DUMMY SKU BATCH TEST 2', $data['create'][1]['sku'] );
		$this->assertEquals( 'Test Button', $data['create'][0]['button_text'] );
		$this->assertEquals( 'external', $data['create'][0]['type'] );
		$this->assertEquals( 'simple', $data['create'][1]['type'] );
		$this->assertEquals( $product_2->get_id(), $data['delete'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * Tests to make sure you can filter products post statuses by both
	 * the status query arg and WP_Query.
	 *
	 * @since 3.0.0
	 */
	public function test_products_filter_post_status() {
		wp_set_current_user( $this->user );
		for ( $i = 0; $i < 8; $i++ ) {
			$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
			if ( 0 === $i % 2 ) {
				wp_update_post(
					array(
						'ID'          => $product->get_id(),
						'post_status' => 'draft',
					)
				);
			}
		}

		// Test filtering with status=publish.
		$request = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_param( 'status', 'publish' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 4, count( $products ) );
		foreach ( $products as $product ) {
			$this->assertEquals( 'publish', $product['status'] );
		}

		// Test filtering with status=draft.
		$request = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_param( 'status', 'draft' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 4, count( $products ) );
		foreach ( $products as $product ) {
			$this->assertEquals( 'draft', $product['status'] );
		}

		// Test filtering with no filters - which should return 'any' (all 8).
		$request  = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 8, count( $products ) );
	}

	/**
	 * Test product schema.
	 *
	 * @since 3.0.0
	 */
	public function test_product_schema() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/products/' . $product->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 65, count( $properties ) );
	}
}
