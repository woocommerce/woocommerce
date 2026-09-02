<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;

/**
 * Cart Coupons Controller Tests.
 */
class CartCoupons extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);
		$this->coupon  = $fixtures->get_coupon( array( 'code' => 'test_coupon' ) );

		wc_empty_cart();

		wc()->cart->add_to_cart( $this->product->get_id(), 2 );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_items() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart/coupons',
			200,
			array(
				0 => array(
					'code'          => 'test_coupon',
					'discount_type' => 'fixed_cart',
					'totals'        => array(
						'total_discount'     => '0',
						'total_discount_tax' => '0',
					),
				),
			)
		);
	}

	/**
	 * @testdox Sequential percentage coupons should be projected in application order with exact discount totals.
	 */
	public function test_get_items_projects_sequential_percentage_coupons_in_application_order(): void {
		update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		wc_empty_cart();

		$this->product->set_regular_price( '100.00' );
		$this->product->set_price( '100.00' );
		$this->product->set_tax_status( 'none' );
		$this->product->save();
		$this->coupon->set_discount_type( 'percent' );
		$this->coupon->set_amount( '20' );
		$this->coupon->save();

		$low_coupon = ( new FixtureData() )->get_coupon(
			array(
				'code'          => 'sequential-low',
				'discount_type' => 'percent',
				'amount'        => '10',
			)
		);

		wc()->cart->add_to_cart( $this->product->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
		wc()->cart->apply_coupon( $low_coupon->get_code() );
		wc()->cart->calculate_totals();

		$this->assertAPIResponse(
			'/wc/store/v1/cart/coupons',
			200,
			array(
				array(
					'code'   => $this->coupon->get_code(),
					'totals' => array(
						'total_discount'     => '2000',
						'total_discount_tax' => '0',
					),
				),
				array(
					'code'   => $low_coupon->get_code(),
					'totals' => array(
						'total_discount'     => '800',
						'total_discount_tax' => '0',
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
			'/wc/store/v1/cart/coupons/' . $this->coupon->get_code(),
			200,
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'totals'        => array(
					'total_discount'     => '0',
					'total_discount_tax' => '0',
				),
			)
		);
	}

	/**
	 * Test add to cart.
	 */
	public function test_create_item() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/coupons' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$this->assertAPIResponse(
			$request,
			201,
			array(
				'code' => $this->coupon->get_code(),
			)
		);
	}

	/**
	 * Test add to cart does not allow invalid items.
	 */
	public function test_invalid_create_item() {
		wc()->cart->remove_coupons();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/coupons' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'IDONOTEXIST',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test delete item.
	 */
	public function test_delete_item() {
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/coupons/' . $this->coupon->get_code() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			204
		);

		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/coupons/' . $this->coupon->get_code() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			404
		);

		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/coupons/i-do-not-exist' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			404
		);
	}

	/**
	 * Test delete all items.
	 */
	public function test_delete_items() {
		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/coupons' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertAPIResponse(
			$request,
			200,
			array()
		);
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-coupons', 'v1' );

		$response = $controller->prepare_item_for_response( $this->coupon->get_code(), new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'totals', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart-coupons', 'v1' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( $this->coupon->get_code(), new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff );
	}
}
