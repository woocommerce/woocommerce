<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use \WC_Helper_Coupon as CouponHelper;
use \WC_Helper_Shipping;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Controller Tests.
 */
class Cart extends TestCase {
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

		$this->coupon = CouponHelper::create_coupon();

		wc_empty_cart();

		$this->keys   = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
		wc()->cart->apply_coupon( $this->coupon->get_code() );

		WC_Helper_Shipping::create_simple_flat_rate();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/apply-coupon', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/remove-coupon', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/update-shipping', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/select-shipping-rate/(?P<package_id>[\d]+)', $routes );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_item() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/cart' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, $data['items_count'] );
		$this->assertEquals( 2, count( $data['items'] ) );
		$this->assertEquals( true, $data['needs_payment'] );
		$this->assertEquals( true, $data['needs_shipping'] );
		$this->assertEquals( '30', $data['items_weight'] );

		$this->assertEquals( 'GBP', $data['totals']->currency_code );
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
		$request  = new WP_REST_Request( 'POST', '/wc/store/cart/remove-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => 'bad_item_key_123',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_invalid_key', $data['code'] );
	}

	/**
	 * Test remove cart item.
	 */
	public function test_remove_cart_item() {
		// Test removing a valid cart item - should return updated cart.
		$request  = new WP_REST_Request( 'POST', '/wc/store/cart/remove-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $data['items_count'] );
		$this->assertEquals( 1, count( $data['items'] ) );
		$this->assertEquals( '10', $data['items_weight'] );
		$this->assertEquals( '1000', $data['totals']->total_items );

		// Test removing same item again - should return 404 (item is already removed).
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_invalid_key', $data['code'] );
	}

	/**
	 * Test changing the quantity of a cart item.
	 */
	public function test_update_item() {
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-item' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 10,
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['items'][0]['quantity'] );
		$this->assertEquals( 11, $data['items_count'] );
		$this->assertEquals( '11000', $data['totals']->total_items );
	}

/**
	 * Test getting updated shipping.
	 */
	public function test_update_shipping() {
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'country' => 'US',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
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
	public function test_get_items_address_validation() {
		// US address.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'address_1' => 'Test address 1',
				'address_2' => 'Test address 2',
				'city'      => 'Test City',
				'state'     => 'AL',
				'postcode'  => '90210',
				'country'   => 'US'
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test address 1', $data['shipping_rates'][0]['destination']->address_1 );
		$this->assertEquals( 'Test address 2', $data['shipping_rates'][0]['destination']->address_2 );
		$this->assertEquals( 'Test City', $data['shipping_rates'][0]['destination']->city );
		$this->assertEquals( 'AL', $data['shipping_rates'][0]['destination']->state );
		$this->assertEquals( '90210', $data['shipping_rates'][0]['destination']->postcode );
		$this->assertEquals( 'US', $data['shipping_rates'][0]['destination']->country );

		// Address with empty country.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'country' => ''
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// Address with invalid country.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'country' => 'ZZZZZZZZ'
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// US address with named state.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'state'   =>'Alabama',
				'country' => 'US'
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'AL', $data['shipping_rates'][0]['destination']->state );
		$this->assertEquals( 'US', $data['shipping_rates'][0]['destination']->country );

		// US address with invalid state.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/update-shipping' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'state'   =>'ZZZZZZZZ',
				'country' => 'US'
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}


	/**
	 * Test applying coupon to cart.
	 */
	public function test_apply_coupon() {
		wc()->cart->remove_coupon( $this->coupon->get_code() );

		$request = new WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '100', $data['totals']->total_discount );

		// Test coupons with different case.
		$newcoupon = CouponHelper::create_coupon( 'testCoupon' );
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'testCoupon',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );

		// Test coupons with special chars in the code.
		$newcoupon = CouponHelper::create_coupon( '$5 off' );
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/apply-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => '$5 off',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test removing coupon from cart.
	 */
	public function test_remove_coupon() {
		// Invalid coupon.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/remove-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'doesnotexist',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );

		// Applied coupon.
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/remove-coupon' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '0', $data['totals']->total_discount );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item_for_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
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
	public function test_schema_matches_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
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
