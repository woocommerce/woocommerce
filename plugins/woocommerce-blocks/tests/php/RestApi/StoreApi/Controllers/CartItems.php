<?php
/**
 * Controller Tests.
 *
 * @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\RestApi\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;

/**
 * Cart Controller Tests.
 */
class CartItems extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		update_option( 'woocommerce_weight_unit', 'g' );

		$this->products = [];

		// Create some test products.
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_weight( 10 );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_weight( 10 );
		$this->products[1]->set_regular_price( 10 );
		$this->products[1]->save();

		wc_empty_cart();

		$this->keys = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/items', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/items/(?P<key>[\w-]{32})', $routes );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) );
	}

	/**
	 * Test getting cart item by key.
	 */
	public function test_get_item() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/cart/items/' . $this->keys[0] ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->keys[0], $data['key'] );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( $this->products[0]->get_name(), $data['name'] );
		$this->assertEquals( $this->products[0]->get_sku(), $data['sku'] );
		$this->assertEquals( $this->products[0]->get_permalink(), $data['permalink'] );
		$this->assertEquals( 2, $data['quantity'] );
		$this->assertEquals( '10.00', $data['price'] );
		$this->assertEquals( '20.00', $data['line_price'] );

		$request  = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/XXX815416f775098fe977004015c6193' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc_empty_cart();

		$request = new WP_REST_Request( 'POST', '/wc/store/cart/items' );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '10',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( 10, $data['quantity'] );

		$response = $this->server->dispatch( $request );
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

		$invalid_product = ProductHelper::create_simple_product( false );
		$invalid_product->set_regular_price( '' );
		$invalid_product->save();

		$request = new WP_REST_Request( 'POST', '/wc/store/cart/items' );
		$request->set_body_params(
			array(
				'id'       => $invalid_product->get_id(),
				'quantity' => '10',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test updating an item.
	 */
	public function test_update_item() {
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_body_params(
			array(
				'quantity' => '10',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['quantity'] );
	}

	/**
	 * Test delete item.
	 */
	public function test_delete_item() {
		$request = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 204, $response->get_status() );
		$this->assertEmpty( $data );

		$request = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request = new WP_REST_Request( 'DELETE', '/wc/store/cart/items' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $data );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $data ) );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartItems();
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'quantity', $schema['properties'] );
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'sku', $schema['properties'] );
		$this->assertArrayHasKey( 'permalink', $schema['properties'] );
		$this->assertArrayHasKey( 'images', $schema['properties'] );
		$this->assertArrayHasKey( 'price', $schema['properties'] );
		$this->assertArrayHasKey( 'line_price', $schema['properties'] );
		$this->assertArrayHasKey( 'variation', $schema['properties'] );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item_for_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartItems();
		$cart       = wc()->cart->get_cart();
		$response   = $controller->prepare_item_for_response( current( $cart ), [] );

		$this->assertArrayHasKey( 'key', $response->get_data() );
		$this->assertArrayHasKey( 'id', $response->get_data() );
		$this->assertArrayHasKey( 'quantity', $response->get_data() );
		$this->assertArrayHasKey( 'name', $response->get_data() );
		$this->assertArrayHasKey( 'sku', $response->get_data() );
		$this->assertArrayHasKey( 'permalink', $response->get_data() );
		$this->assertArrayHasKey( 'images', $response->get_data() );
		$this->assertArrayHasKey( 'price', $response->get_data() );
		$this->assertArrayHasKey( 'line_price', $response->get_data() );
		$this->assertArrayHasKey( 'variation', $response->get_data() );
	}
}
