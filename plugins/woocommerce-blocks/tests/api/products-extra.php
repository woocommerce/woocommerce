<?php
/**
 * Product Controller "extras" REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 1.2.0
 */
class WC_Tests_API_Products_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wgbp/v3'; // @todo Fix endpoint typo?

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$this->author = $this->factory->user->create(
			array(
				'role' => 'author',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 1.2.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wgbp/v3/products', $routes );
		$this->assertArrayHasKey( '/wgbp/v3/products/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wgbp/v3/products/batch', $routes );
	}

	/**
	 * Test getting products.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products() {
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		$product = WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wgbp/v3/products' ) );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );
		$this->assertEquals( 'DUMMY SKU', $products[0]['sku'] );
		$this->assertEquals( 'Dummy External Product', $products[1]['name'] );
		$this->assertEquals( 'DUMMY EXTERNAL SKU', $products[1]['sku'] );
	}

	/**
	 * Test getting products as an author.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_as_author() {
		wp_set_current_user( $this->author );
		WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wgbp/v3/products' ) );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test getting products with custom ordering.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_order_by_price() {
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		$product = WC_Helper_Product::create_simple_product( false ); // Prevent saving, since we save here.
		// Customize the price, otherwise both are 10.
		$product->set_props( array(
			'regular_price' => 15,
			'price'         => 15,
		) );
		$product->save();

		$request = new WP_REST_Request( 'GET', '/wgbp/v3/products' );
		$request->set_param( 'orderby', 'price' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $products ) );

		// The external product should be first, then the simple product
		$this->assertEquals( 'Dummy External Product', $products[0]['name'] );
		$this->assertEquals( 'Dummy Product', $products[1]['name'] );
		$this->assertEquals( '10', $products[0]['price'] );
		$this->assertEquals( '15', $products[1]['price'] );
	}
}
