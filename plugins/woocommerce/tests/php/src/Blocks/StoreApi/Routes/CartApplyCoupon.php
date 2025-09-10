<?php
/**
 * Controller Tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;

/**
 * Cart Apply Coupon Controller Tests.
 */
class CartApplyCoupon extends ControllerTestCase {

	/**
	 * Setup test products data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 110,
					'sale_price'    => 55,
					'weight'        => 10,
				)
			),
		);

		wc_empty_cart();

		$this->coupon = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 1,
			)
		);

		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/v1/cart/apply-coupon', $routes );
	}

	/**
	 * Test applying a single coupon.
	 */
	public function test_apply_single_coupon() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					'total_discount' => '100',
				),
			)
		);
	}

	/**
	 * Test applying multiple coupons.
	 */
	public function test_apply_multiple_coupons() {
		wc()->cart->remove_coupons();

		$fixtures = new FixtureData();

		// Create additional coupons like in the e2e test.
		$coupon_fixed = $fixtures->get_coupon(
			array(
				'code'          => '5fixedcheckout',
				'discount_type' => 'fixed_cart',
				'amount'        => 5,
			)
		);

		$coupon_percent = $fixtures->get_coupon(
			array(
				'code'          => '50percoffcheckout',
				'discount_type' => 'percent',
				'amount'        => 50,
			)
		);

		$coupon_fixed_product = $fixtures->get_coupon(
			array(
				'code'          => '10fixedproductcheckout',
				'discount_type' => 'fixed_product',
				'amount'        => 10,
			)
		);

		// Apply first coupon (fixed cart).
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'code' => $coupon_fixed->get_code() ) );

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					'total_price' => '5000', // $55 - $5 = $50.
				),
			)
		);

		// Apply second coupon (percent).
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'code' => $coupon_percent->get_code() ) );

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					// After 50% off: ($55 - $5) * 0.5 = $25 - but percent is calculated on original price
					// So: $55 - $5 - ($55 * 0.5) = $55 - $5 - $27.50 = $22.50.
					'total_price' => '2250',
				),
			)
		);

		// Apply third coupon (fixed product).
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'code' => $coupon_fixed_product->get_code() ) );

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals'  => array(
					// After fixed product discount: $22.50 - $10 = $12.50.
					'total_price' => '1250',
				),
				'coupons' => function ( $coupons ) use ( $coupon_fixed, $coupon_percent, $coupon_fixed_product ) {
					$this->assertCount( 3, $coupons );
					$coupon_codes = array_map(
						function ( $coupon ) {
							return is_object( $coupon ) ? $coupon->code : $coupon['code'];
						},
						$coupons
					);
					$this->assertContains( $coupon_fixed->get_code(), $coupon_codes );
					$this->assertContains( $coupon_percent->get_code(), $coupon_codes );
					$this->assertContains( $coupon_fixed_product->get_code(), $coupon_codes );
					return true;
				},
			)
		);
	}

	/**
	 * Test preventing duplicate coupon application.
	 */
	public function test_prevent_duplicate_coupon() {
		wc()->cart->remove_coupons();

		// Apply coupon first time.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Try to apply same coupon again.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_coupon_error', $data['code'] );
		$this->assertStringContainsString( 'already been applied', $data['message'] );
	}

	/**
	 * Test coupon usage limit validation.
	 */
	public function test_coupon_usage_limit() {
		wc()->cart->remove_coupons();

		$fixtures = new FixtureData();

		// Create a coupon with usage limit.
		$limited_coupon = $fixtures->get_coupon(
			array(
				'code'          => '10fixedcheckoutlimited',
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'usage_limit'   => 1,
			)
		);

		// Manually increment usage count to simulate it being used.
		$limited_coupon->increase_usage_count();

		// Try to apply the limited coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $limited_coupon->get_code(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_coupon_error', $data['code'] );
		$this->assertStringContainsString( 'Usage limit', html_entity_decode( $data['message'] ) );
	}

	/**
	 * Test applying coupon when coupons are disabled.
	 */
	public function test_apply_coupon_when_disabled() {
		wc()->cart->remove_coupons();

		// Disable coupons.
		update_option( 'woocommerce_enable_coupons', 'no' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_coupon_disabled', $data['code'] );

		// Re-enable coupons for other tests.
		update_option( 'woocommerce_enable_coupons', 'yes' );
	}

	/**
	 * Test applying invalid coupon.
	 */
	public function test_apply_invalid_coupon() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'invalid_coupon_code',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_coupon_error', $data['code'] );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/cart/apply-coupon' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'schema', $data );
		$this->assertArrayHasKey( 'properties', $data['schema'] );
		$properties = $data['schema']['properties'];
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'items', $properties );
		$this->assertArrayHasKey( 'coupons', $properties );
	}
}
