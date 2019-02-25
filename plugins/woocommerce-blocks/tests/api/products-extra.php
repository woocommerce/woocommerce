<?php
/**
 * @package WooCommerce\Tests\API
 */

/**
 * Product Controller "extras" REST API Test
 *
 * @since 1.2.0
 */
class WC_Tests_API_Products_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-blocks/v1';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user        = $this->factory->user->create(
			array(
				'role' => 'author',
			)
		);
		$this->contributor = $this->factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
		$this->subscriber  = $this->factory->user->create(
			array(
				'role' => 'subscriber',
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

		$this->assertArrayHasKey( '/wc-blocks/v1/products', $routes );
		$this->assertArrayHasKey( '/wc-blocks/v1/products/(?P<id>[\d]+)', $routes );
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
		$product  = WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc-blocks/v1/products' ) );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );
		$this->assertEquals( 'DUMMY SKU', $products[0]['sku'] );
		$this->assertEquals( 'Dummy External Product', $products[1]['name'] );
		$this->assertEquals( 'DUMMY EXTERNAL SKU', $products[1]['sku'] );
	}

	/**
	 * Test getting products as an contributor.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_as_contributor() {
		wp_set_current_user( $this->contributor );
		WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc-blocks/v1/products' ) );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test getting products as an subscriber.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_as_subscriber() {
		wp_set_current_user( $this->subscriber );
		WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc-blocks/v1/products' ) );
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
		$product->set_props(
			array(
				'regular_price' => 15,
				'price'         => 15,
			)
		);
		$product->save();

		$request = new WP_REST_Request( 'GET', '/wc-blocks/v1/products' );
		$request->set_param( 'orderby', 'price' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $products ) );

		// The external product should be first, then the simple product.
		$this->assertEquals( 'Dummy External Product', $products[0]['name'] );
		$this->assertEquals( 'Dummy Product', $products[1]['name'] );
		$this->assertEquals( '10', $products[0]['price'] );
		$this->assertEquals( '15', $products[1]['price'] );
	}

	/**
	 * Test product_visibility queries.
	 *
	 * @since 1.3.1
	 */
	public function test_product_visibility() {
		wp_set_current_user( $this->user );
		$visible_product = WC_Helper_Product::create_simple_product();
		$visible_product->set_name( 'Visible Product' );
		$visible_product->set_catalog_visibility( 'visible' );
		$visible_product->save();

		$catalog_product = WC_Helper_Product::create_simple_product();
		$catalog_product->set_name( 'Catalog Product' );
		$catalog_product->set_catalog_visibility( 'catalog' );
		$catalog_product->save();

		$search_product = WC_Helper_Product::create_simple_product();
		$search_product->set_name( 'Search Product' );
		$search_product->set_catalog_visibility( 'search' );
		$search_product->save();

		$hidden_product = WC_Helper_Product::create_simple_product();
		$hidden_product->set_name( 'Hidden Product' );
		$hidden_product->set_catalog_visibility( 'hidden' );
		$hidden_product->save();

		$query_params = array(
			'catalog_visibility' => 'visible',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc-blocks/v1/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $products ) );
		$this->assertEquals( 'Visible Product', $products[0]['name'] );

		$query_params = array(
			'catalog_visibility' => 'catalog',
			'orderby'            => 'id',
			'order'              => 'asc',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc-blocks/v1/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Visible Product', $products[0]['name'] );
		$this->assertEquals( 'Catalog Product', $products[1]['name'] );

		$query_params = array(
			'catalog_visibility' => 'search',
			'orderby'            => 'id',
			'order'              => 'asc',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc-blocks/v1/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Visible Product', $products[0]['name'] );
		$this->assertEquals( 'Search Product', $products[1]['name'] );

		$query_params = array(
			'catalog_visibility' => 'hidden',
		);
		$request      = new WP_REST_REQUEST( 'GET', '/wc-blocks/v1/products' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $products ) );
		$this->assertEquals( 'Hidden Product', $products[0]['name'] );
	}
}
