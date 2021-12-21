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
class Cart extends ControllerTestCase {

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);

		$this->coupon = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 1,
			)
		);

		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
		wc()->cart->apply_coupon( $this->coupon->get_code() );

		// Draft order.
		$order = new \WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();
		wc()->session->set( 'store_api_draft_order', $order->get_id() );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/apply-coupon', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/remove-coupon', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/update-customer', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/select-shipping-rate', $routes );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_item() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, $data['items_count'] );
		$this->assertEquals( 2, count( $data['items'] ) );
		$this->assertEquals( true, $data['needs_payment'] );
		$this->assertEquals( true, $data['needs_shipping'] );
		$this->assertEquals( '30', $data['items_weight'] );

		$this->assertEquals( 'USD', $data['totals']->currency_code );
		$this->assertEquals( 2, $data['totals']->currency_minor_unit );
		$this->assertEquals( '3000', $data['totals']->total_items );
		$this->assertEquals( '0', $data['totals']->total_items_tax );
		$this->assertEquals( '0', $data['totals']->total_fees );
		$this->assertEquals( '0', $data['totals']->total_fees_tax );
		$this->assertEquals( '100', $data['totals']->total_discount );
		$this->assertEquals( '0', $data['totals']->total_discount_tax );
		$this->assertEquals( '0', $data['totals']->total_shipping );
		$this->assertEquals( '0', $data['totals']->total_shipping_tax );
		$this->assertEquals( '0', $data['totals']->total_tax );
		$this->assertEquals( '2900', $data['totals']->total_price );
	}

	/**
	 * Test removing a nonexistent cart item.
	 */
	public function test_remove_bad_cart_item() {
		// Test removing a bad cart item - should return 404.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/remove-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => 'bad_item_key_123',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_invalid_key', $data['code'] );
	}

	/**
	 * Test remove cart item.
	 */
	public function test_remove_cart_item() {
		// Test removing a valid cart item - should return updated cart.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/remove-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $data['items_count'] );
		$this->assertEquals( 1, count( $data['items'] ) );
		$this->assertEquals( '10', $data['items_weight'] );
		$this->assertEquals( '1000', $data['totals']->total_items );

		// Test removing same item again - should return 404 (item is already removed).
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_invalid_key', $data['code'] );
	}

	/**
	 * Test changing the quantity of a cart item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 10,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['items'][0]['quantity'] );
		$this->assertEquals( 11, $data['items_count'] );
		$this->assertEquals( '11000', $data['totals']->total_items );
	}

	/**
	 * Test getting updated shipping.
	 */
	public function test_update_customer() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'country' => 'US',
				),
			)
		);

		$action_callback = \Mockery::mock( 'ActionCallback' );
		$action_callback->shouldReceive( 'do_customer_callback' )->once();

		add_action( 'woocommerce_blocks_cart_update_customer_from_request', array( $action_callback, 'do_customer_callback' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		remove_action( 'woocommerce_blocks_cart_update_customer_from_request', array( $action_callback, 'do_customer_callback' ) );

		$this->assertEquals( 200, $response->get_status(), print_r( $response, true ) );
		$this->assertArrayHasKey( 'shipping_rates', $data );

		$this->assertEquals( null, $data['shipping_rates'][0]['destination']->address_1 );
		$this->assertEquals( null, $data['shipping_rates'][0]['destination']->address_2 );
		$this->assertEquals( null, $data['shipping_rates'][0]['destination']->city );
		$this->assertEquals( null, $data['shipping_rates'][0]['destination']->state );
		$this->assertEquals( null, $data['shipping_rates'][0]['destination']->postcode );
		$this->assertEquals( 'US', $data['shipping_rates'][0]['destination']->country );
	}

	/**
	 * Test shipping address validation.
	 */
	public function test_update_customer_address() {
		// US address.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test address 1', $data['shipping_rates'][0]['destination']->address_1 );
		$this->assertEquals( 'Test address 2', $data['shipping_rates'][0]['destination']->address_2 );
		$this->assertEquals( 'Test City', $data['shipping_rates'][0]['destination']->city );
		$this->assertEquals( 'AL', $data['shipping_rates'][0]['destination']->state );
		$this->assertEquals( '90210', $data['shipping_rates'][0]['destination']->postcode );
		$this->assertEquals( 'US', $data['shipping_rates'][0]['destination']->country );

		// Address with invalid country.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'ZZZZZZZZ',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// US address with named state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'Alabama',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'AL', $data['shipping_rates'][0]['destination']->state );
		$this->assertEquals( 'US', $data['shipping_rates'][0]['destination']->country );

		// US address with invalid state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'ZZZZZZZZ',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// US address with invalid postcode.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/update-customer' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => 'ABCDE',
					'country'    => 'US',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}


	/**
	 * Test applying coupon to cart.
	 */
	public function test_apply_coupon() {
		wc()->cart->remove_coupon( $this->coupon->get_code() );

		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '100', $data['totals']->total_discount );

		$fixtures = new FixtureData();

		// Test coupons with different case.
		$newcoupon = $fixtures->get_coupon( array( 'code' => 'testCoupon' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'testCoupon',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );

		// Test coupons with special chars in the code.
		$newcoupon = $fixtures->get_coupon( array( 'code' => '$5 off' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => '$5 off',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test removing coupon from cart.
	 */
	public function test_remove_coupon() {
		// Invalid coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/remove-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'doesnotexist',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );

		// Applied coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/remove-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '0', $data['totals']->total_discount );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart' );
		$cart       = wc()->cart;
		$response   = $controller->prepare_item_for_response( $cart, new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'items_count', $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'shipping_rates', $data );
		$this->assertArrayHasKey( 'coupons', $data );
		$this->assertArrayHasKey( 'needs_payment', $data );
		$this->assertArrayHasKey( 'needs_shipping', $data );
		$this->assertArrayHasKey( 'items_weight', $data );
		$this->assertArrayHasKey( 'totals', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart' );
		$schema     = $controller->get_item_schema();
		$cart       = wc()->cart;
		$response   = $controller->prepare_item_for_response( $cart, new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
