<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\Tests\Helpers\FixtureData;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Coupons Controller Tests.
 */
class CartCoupons extends ControllerTestCase {

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product = $fixtures->get_simple_product( [
			'name' => 'Test Product 1',
			'regular_price' => 10,
		] );
		$this->coupon  = $fixtures->get_coupon( ['code' => 'test_coupon'] );

		wc_empty_cart();

		wc()->cart->add_to_cart( $this->product->get_id(), 2 );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/coupons', $routes );
		$this->assertArrayHasKey( '/wc/store/cart/coupons/(?P<code>[\w-]+)', $routes );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/coupons' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $data ) );
	}

	/**
	 * Test getting cart item by key.
	 */
	public function test_get_item() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/coupons/' . $this->coupon->get_code() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->coupon->get_code(), $data['code'] );
		$this->assertEquals( '0', $data['totals']->total_discount );
		$this->assertEquals( '0', $data['totals']->total_discount_tax );
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/coupons' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $this->coupon->get_code(), $data['code'] );
	}

	/**
	 * Test add to cart does not allow invalid items.
	 */
	public function test_invalid_create_item() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/cart/coupons' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'IDONOTEXIST',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test delete item.
	 */
	public function test_delete_item() {
		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/coupons/' . $this->coupon->get_code() );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 204, $response->get_status() );
		$this->assertEmpty( $data );

		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/coupons/' . $this->coupon->get_code() );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );

		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/coupons/i-do-not-exist' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request  = new \WP_REST_Request( 'DELETE', '/wc/store/cart/coupons' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [], $data );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/cart/coupons' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, count( $data ) );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-coupons' );

		$response   = $controller->prepare_item_for_response( $this->coupon->get_code(), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'totals', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-coupons' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( $this->coupon->get_code(), new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
