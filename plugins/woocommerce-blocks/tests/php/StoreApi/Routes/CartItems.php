<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Controller Tests.
 */
class CartItems extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		global $wpdb;

		parent::setUp();

		wp_set_current_user( 0 );

		update_option( 'woocommerce_weight_unit', 'g' );

		$this->products = [];

		// Create a test simple product.
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_weight( 10 );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$image_url = media_sideload_image( 'http://cldup.com/Dr1Bczxq4q.png', $this->products[0]->get_id(), '', 'src' );
		$image_id  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $image_url ) );
		$this->products[0]->set_image_id( $image_id[0] );
		$this->products[0]->save();

		// Create a test variable product.
		$this->products[1] = ProductHelper::create_variation_product();
		$this->products[1]->set_weight( 10 );
		$this->products[1]->set_regular_price( 10 );
		$this->products[1]->save();

		wc_empty_cart();

		$this->keys   = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart(
			$this->products[1]->get_id(),
			1,
			current( $this->products[1]->get_children() ),
			array(
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '2',
			)
		);
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
		$this->assertEquals( '2000', $data['totals']->line_subtotal );
		$this->assertEquals( '2000', $data['totals']->line_total );

		$request  = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/XXX815416f775098fe977004015c6193' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $invalid_product->get_id(),
				'quantity' => '10',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test updating an item.
	 */
	public function test_update_item() {
		$request = new WP_REST_Request( 'PUT', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$request  = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 204, $response->get_status() );
		$this->assertEmpty( $data );

		$request  = new WP_REST_Request( 'DELETE', '/wc/store/cart/items/' . $this->keys[0] );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request  = new WP_REST_Request( 'DELETE', '/wc/store/cart/items' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
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
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item_for_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
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
		$this->assertArrayHasKey( 'low_stock_remaining', $data );
		$this->assertArrayHasKey( 'backorders_allowed', $data );
		$this->assertArrayHasKey( 'show_backorder_badge', $data );
		$this->assertArrayHasKey( 'short_description', $data );
	}

	/**
	 * Test schema matches responses.
	 *
	 * Tests schema of both products in cart to cover as much schema as possible.
	 */
	public function test_schema_matches_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
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
