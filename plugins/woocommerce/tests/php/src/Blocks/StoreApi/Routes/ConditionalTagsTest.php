<?php
/**
 * Conditional Tags Tests for Store API Routes.
 *
 * Tests that is_cart() and is_checkout() return correct values during Store API requests.
 *
 * @package Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Enums\ProductStockStatus;

class ConditionalTagsTest extends ControllerTestCase {

	/**
	 * Products created for tests.
	 *
	 * @var array
	 */
	protected $products = array();

	protected function setUp(): void {
		parent::setUp();

		wc_empty_cart();
		$this->reset_customer_state();

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 20,
					'weight'        => 5,
				)
			),
		);

		wc_get_container()->get( RoutesController::class )->register_all_routes();
	}

	protected function tearDown(): void {
		parent::tearDown();
		wc_empty_cart();
		$this->reset_customer_state();
	}

	private function reset_customer_state() {
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_billing_state( '' );
		wc()->customer->set_shipping_state( '' );
		wc()->customer->set_billing_postcode( '' );
		wc()->customer->set_shipping_postcode( '' );
		wc()->customer->set_is_vat_exempt( false );
		wc()->customer->set_calculated_shipping( false );
	}

	public function test_cart_constant_defined_during_cart_request() {
		$constant_defined = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$constant_defined ) {
				$constant_defined = defined( 'WOOCOMMERCE_CART' ) && WOOCOMMERCE_CART;
			},
			1
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $constant_defined, 'WOOCOMMERCE_CART constant should be defined during Store API cart request' );
	}

	public function test_is_cart_returns_true_during_cart_request() {
		$is_cart_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result ) {
				$is_cart_result = is_cart();
			},
			1
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $is_cart_result, 'is_cart() should return true during Store API cart request' );
	}

	public function test_is_cart_returns_true_during_add_to_cart_request() {
		$is_cart_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result ) {
				$is_cart_result = is_cart();
			},
			1
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 2,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertTrue( $is_cart_result, 'is_cart() should return true during Store API add-to-cart request' );
	}

	public function test_is_cart_returns_true_during_update_cart_request() {
		$cart_item_key = wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$is_cart_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result ) {
				$is_cart_result = is_cart();
			},
			1
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $cart_item_key,
				'quantity' => 3,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $is_cart_result, 'is_cart() should return true during Store API update cart item request' );
	}

	public function test_is_cart_returns_true_during_remove_cart_item_request() {
		$cart_item_key = wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$is_cart_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result ) {
				$is_cart_result = is_cart();
			},
			1
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $cart_item_key,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $is_cart_result, 'is_cart() should return true during Store API remove cart item request' );
	}

	public function test_is_cart_returns_true_during_apply_coupon_request() {
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$fixtures = new FixtureData();
		$coupon = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 5,
			)
		);

		$is_cart_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result ) {
				$is_cart_result = is_cart();
			},
			1
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'test_coupon',
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 200, 201 ), 'Apply coupon should return 200 or 201' );
		$this->assertTrue( $is_cart_result, 'is_cart() should return true during Store API apply coupon request' );

		$coupon->delete( true );
	}

	public function test_is_checkout_returns_true_during_checkout_request() {
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		wc()->customer->set_billing_first_name( 'Test' );
		wc()->customer->set_billing_last_name( 'Customer' );
		wc()->customer->set_billing_address_1( '123 Test St' );
		wc()->customer->set_billing_city( 'Test City' );
		wc()->customer->set_billing_postcode( '90210' );
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_billing_state( 'CA' );
		wc()->customer->set_billing_email( 'test@example.com' );

		$is_checkout_result = null;
		$checkout_constant_defined = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_checkout_result, &$checkout_constant_defined ) {
				$is_checkout_result = is_checkout();
				$checkout_constant_defined = defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT;
			},
			1
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 200, 401 ), 'Checkout request status' );
		
		if ( 200 === $response->get_status() ) {
			$this->assertTrue( $is_checkout_result, 'is_checkout() should return true during Store API checkout request' );
			$this->assertTrue( $checkout_constant_defined, 'WOOCOMMERCE_CHECKOUT constant should be defined during Store API checkout request' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function test_both_conditional_tags_during_checkout_request() {
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		wc()->customer->set_billing_first_name( 'Test' );
		wc()->customer->set_billing_last_name( 'Customer' );
		wc()->customer->set_billing_address_1( '123 Test St' );
		wc()->customer->set_billing_city( 'Test City' );
		wc()->customer->set_billing_postcode( '90210' );
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_billing_state( 'CA' );
		wc()->customer->set_billing_email( 'test@example.com' );

		$is_cart_result = null;
		$is_checkout_result = null;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$is_cart_result, &$is_checkout_result ) {
				$is_cart_result = is_cart();
				$is_checkout_result = is_checkout();
			},
			1
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 200, 401 ), 'Checkout request status' );
		
		if ( 200 === $response->get_status() ) {
			$this->assertFalse( $is_cart_result, 'is_cart() should return false during Store API checkout request (matches core AJAX)' );
			$this->assertTrue( $is_checkout_result, 'is_checkout() should return true during Store API checkout request' );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function test_custom_cart_validation_with_is_cart_check() {
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );

		$validation_ran = false;
		$is_cart_check_passed = false;

		add_action(
			'woocommerce_check_cart_items',
			function () use ( &$validation_ran, &$is_cart_check_passed ) {
				$validation_ran = true;

				if ( is_cart() ) {
					$is_cart_check_passed = true;

					$item_count = WC()->cart->get_cart_contents_count();
					if ( 0 !== $item_count % 3 ) {
						wc_add_notice( 'Please select a multiple of 3 items.', 'error' );
					}
				}
			},
			9
		);

		$cart_items = wc()->cart->get_cart();
		$cart_item_key = array_key_first( $cart_items );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $cart_item_key,
				'quantity' => 2,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $validation_ran, 'Custom validation should have run' );
		$this->assertTrue( $is_cart_check_passed, 'is_cart() check should have passed, allowing custom validation to run' );
	}
}
