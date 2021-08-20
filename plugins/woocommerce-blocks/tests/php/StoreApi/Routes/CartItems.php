<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\Tests\Helpers\FixtureData;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Controller Tests.
 */
class CartItems extends ControllerTestCase {

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = [
			$fixtures->get_simple_product( [
				'name' => 'Test Product 1',
				'stock_status' => 'instock',
				'regular_price' => 10,
				'weight' => 10,
				'image_id' => $fixtures->sideload_image(),
			] ),
		];

		$variable_product = $fixtures->get_variable_product(
			[
				'name' => 'Test Product 2',
				'stock_status' => 'instock',
				'regular_price' => 10,
				'weight' => 10,
				'image_id' => $fixtures->sideload_image(),
			],
			[
				$fixtures->get_product_attribute( 'color', [ 'red', 'green', 'blue' ] ),
				$fixtures->get_product_attribute( 'size', [ 'small', 'medium', 'large' ] )
			]
		);
		$variation = $fixtures->get_variation_product( $variable_product->get_id(), [ 'pa_color' => 'red', 'pa_size' => 'small' ] );

		$this->products[] = $variable_product;

		wc_empty_cart();
		$this->keys   = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart(
			$this->products[1]->get_id(),
			1,
			$variation->get_id(),
			array(
				'attribute_pa_color' => 'red',
				'attribute_pa_size' => 'small',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/items', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/items/(?P<key>[\w-]{32})', $routes );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) );
	}

	/**
	 * Test getting cart item by key.
	 */
	public function test_get_item() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/items/' . $this->keys[0] ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->keys[0], $data['key'] );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( $this->products[0]->get_name(), $data['name'] );
		$this->assertEquals( $this->products[0]->get_sku(), $data['sku'] );
		$this->assertEquals( $this->products[0]->get_permalink(), $data['permalink'] );
		$this->assertEquals( 2, $data['quantity'] );
		$this->assertEquals( '2000', $data['totals']->line_subtotal );
		$this->assertEquals( '2000', $data['totals']->line_total );

		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/items/XXX815416f775098fe977004015c6193' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/items' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '10',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( 10, $data['quantity'] );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( 20, $data['quantity'] );
	}

	/**
	 * Test add to cart does not allow invalid items.
	 */
	public function test_invalid_create_item() {
		wc_empty_cart();

		$fixtures        = new FixtureData();
		$invalid_product = $fixtures->get_simple_product( [
			'name' => 'Invalid Product',
			'regular_price' => '',
		] );

		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/items' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $invalid_product->get_id(),
				'quantity' => '10',
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test updating an item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'quantity' => '10',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['quantity'] );
	}

	/**
	 * Test delete item.
	 */
	public function test_delete_item() {
		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 204, $response->get_status() );
		$this->assertEmpty( $data );

		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/items' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $data );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $data ) );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items' );
		$cart       = wc()->cart->get_cart();
		$response   = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'key', $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'quantity', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'sku', $data );
		$this->assertArrayHasKey( 'permalink', $data );
		$this->assertArrayHasKey( 'images', $data );
		$this->assertArrayHasKey( 'totals', $data );
		$this->assertArrayHasKey( 'variation', $data );
		$this->assertArrayHasKey( 'item_data', $data );
		$this->assertArrayHasKey( 'low_stock_remaining', $data );
		$this->assertArrayHasKey( 'backorders_allowed', $data );
		$this->assertArrayHasKey( 'show_backorder_badge', $data );
		$this->assertArrayHasKey( 'short_description', $data );
		$this->assertArrayHasKey( 'catalog_visibility', $data );
	}

	/**
	 * Test schema matches responses.
	 *
	 * Tests schema of both products in cart to cover as much schema as possible.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items' );
		$schema     = $controller->get_item_schema();
		$cart       = wc()->cart->get_cart();
		$validate   = new ValidateSchema( $schema );

		// Simple product.
		$response = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$diff     = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );

		// Variable product.
		$response = $controller->prepare_item_for_response( end( $cart ), new \WP_REST_Request() );
		$diff     = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
