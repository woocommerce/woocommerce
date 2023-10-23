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
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
					'image_id'      => $fixtures->sideload_image(),
				)
			),
		);

		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Test Product 2',
				'stock_status'  => 'instock',
				'regular_price' => 10,
				'weight'        => 10,
				'image_id'      => $fixtures->sideload_image(),
			),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$variation        = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array(
				'pa_color' => 'red',
				'pa_size'  => 'small',
			)
		);

		$this->products[] = $variable_product;

		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart(
			$this->products[1]->get_id(),
			1,
			$variation->get_id(),
			array(
				'attribute_pa_color' => 'red',
				'attribute_pa_size'  => 'small',
			)
		);
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart/items',
			200,
			array(
				0 => array(
					'key'       => $this->keys[0],
					'id'        => $this->products[0]->get_id(),
					'type'      => $this->products[0]->get_type(),
					'name'      => $this->products[0]->get_name(),
					'sku'       => $this->products[0]->get_sku(),
					'permalink' => $this->products[0]->get_permalink(),
					'quantity'  => 2,
					'totals'    => array(
						'line_subtotal' => '2000',
						'line_total'    => '2000',
					),
				),
				1 => array(
					'key'      => $this->keys[1],
					'quantity' => 1,
					'totals'   => array(
						'line_subtotal' => '1000',
						'line_total'    => '1000',
					),
				),
			)
		);
	}

	/**
	 * Test getting cart item by key.
	 */
	public function test_get_item() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart/items/' . $this->keys[0],
			200,
			array(
				'key'       => $this->keys[0],
				'id'        => $this->products[0]->get_id(),
				'type'      => $this->products[0]->get_type(),
				'name'      => $this->products[0]->get_name(),
				'sku'       => $this->products[0]->get_sku(),
				'permalink' => $this->products[0]->get_permalink(),
				'quantity'  => 2,
				'totals'    => array(
					'line_subtotal' => '2000',
					'line_total'    => '2000',
				),
			)
		);
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '10',
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 10,
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 20,
			)
		);
	}

	/**
	 * Test add to cart does not allow invalid items.
	 */
	public function test_invalid_create_item() {
		wc_empty_cart();

		$fixtures        = new FixtureData();
		$invalid_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Invalid Product',
				'regular_price' => '',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $invalid_product->get_id(),
				'quantity' => '10',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test updating an item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			204,
			array()
		);

		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items/' . $this->keys[0] );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			409
		);
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(), $data );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/items' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $data ) );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$cart       = wc()->cart->get_cart();
		$response   = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'key', $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'type', $data );
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
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-items', 'v1' );
		$schema     = $controller->get_item_schema();
		$cart       = wc()->cart->get_cart();
		$validate   = new ValidateSchema( $schema );

		// Simple product.
		$response = $controller->prepare_item_for_response( current( $cart ), new \WP_REST_Request() );
		$diff     = $validate->get_diff_from_object( $response->get_data() );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( $diff, print_r( $diff, true ) );

		// Variable product.
		$response = $controller->prepare_item_for_response( end( $cart ), new \WP_REST_Request() );
		$diff     = $validate->get_diff_from_object( $response->get_data() );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
