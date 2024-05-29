<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Spy_REST_Server;

/**
 * Cart Controller Tests.
 */
class Cart extends ControllerTestCase {


	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
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
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 3',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 4',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
					'virtual'       => true,
				)
			),
		);

		// Add product #3 as a cross-sell for product #1.
		$this->products[0]->set_cross_sell_ids( array( $this->products[2]->get_id() ) );
		$this->products[0]->save();

		$this->coupon = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 1,
			)
		);

		wc_empty_cart();
		$this->reset_customer_state();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );

		// Draft order.
		$order = new \WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();
		wc()->session->set( 'store_api_draft_order', $order->get_id() );
	}

	/**
	 * Resets customer state and remove any existing data from previous tests.
	 */
	private function reset_customer_state() {
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_billing_state( '' );
		wc()->customer->set_shipping_state( '' );
		wc()->customer->set_billing_postcode( '' );
		wc()->customer->set_shipping_postcode( '' );
		wc()->customer->set_shipping_city( '' );
		wc()->customer->set_billing_city( '' );
		wc()->customer->set_shipping_address_1( '' );
		wc()->customer->set_billing_address_1( '' );
	}
	/**
	 * Test getting cart.
	 */
	public function test_get_item() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart',
			200,
			array(
				'items_count'    => 3,
				'needs_payment'  => true,
				'needs_shipping' => true,
				'items_weight'   => '30',
				'items'          => function ( $value ) {
					return count( $value ) === 2;
				},
				'cross_sells'    => array(
					array(
						'id' => $this->products[2]->get_id(),
					),
				),
				'totals'         => array(
					'currency_code'               => 'USD',
					'currency_minor_unit'         => 2,
					'currency_symbol'             => '$',
					'currency_decimal_separator'  => '.',
					'currency_thousand_separator' => ',',
					'currency_prefix'             => '$',
					'currency_suffix'             => '',
					'total_items'                 => '3000',
					'total_items_tax'             => '0',
					'total_fees'                  => '0',
					'total_fees_tax'              => '0',
					'total_discount'              => '100',
					'total_discount_tax'          => '0',
					'total_shipping'              => '1000',
					'total_shipping_tax'          => '0',
					'total_tax'                   => '0',
					'total_price'                 => '3900',
					'tax_lines'                   => array(),
				),
			)
		);
	}

	/**
	 * Test removing a nonexistent cart item. This should return 409 conflict with updated cart data.
	 */
	public function test_remove_bad_cart_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => 'bad_item_key_123',
			)
		);
		$this->assertAPIResponse(
			$request,
			409,
			array(
				'code' => 'woocommerce_rest_cart_invalid_key',
			)
		);
	}

	/**
	 * Test remove cart item.
	 */
	public function test_remove_cart_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'items_count'  => 1,
				'items'        => function ( $value ) {
					return count( $value ) === 1;
				},
				'items_weight' => '10',
				'totals'       => array(
					'total_items' => '1000',
				),
			)
		);

		// Test removing same item again - should return 404 (item is already removed).
		$this->assertAPIResponse(
			$request,
			409,
			array(
				'code' => 'woocommerce_rest_cart_invalid_key',
			)
		);
	}

	/**
	 * Test changing the quantity of a cart item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 10,
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'items_count' => 11,
				'totals'      => array(
					'total_items' => '11000',
				),
				'items'       => array(
					0 => array(
						'quantity' => 10,
					),
				),
			)
		);
	}

	/**
	 * Test getting updated shipping.
	 */
	public function test_update_customer() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'country' => 'US',
				),
			)
		);

		$action_callback = \Mockery::mock( 'ActionCallback' );
		$action_callback->shouldReceive( 'do_customer_callback' )->once();

		add_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			array(
				$action_callback,
				'do_customer_callback',
			)
		);

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'address_1' => '',
							'address_2' => '',
							'city'      => '',
							'state'     => '',
							'postcode'  => '',
							'country'   => 'US',
						),
					),
				),
			)
		);

		remove_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			array(
				$action_callback,
				'do_customer_callback',
			)
		);
	}

	/**
	 * Test shipping address validation.
	 */
	public function test_update_customer_address() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'address_1' => 'Test address 1',
							'address_2' => 'Test address 2',
							'city'      => 'Test City',
							'state'     => 'AL',
							'postcode'  => '90210',
							'country'   => 'US',
						),
					),
				),
			)
		);

		// Address with invalid country.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$this->assertAPIResponse(
			$request,
			400
		);

		// US address with named state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'state'   => 'AL',
							'country' => 'US',
						),
					),
				),
			)
		);

		// US address with invalid state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$this->assertAPIResponse(
			$request,
			400
		);

		// US address with invalid postcode.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
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
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test updating customer with a virtual cart only, this should test the address copying functionality.
	 */
	public function test_update_customer_virtual_cart() {
		// Add a virtual item to cart.
		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[3]->get_id() );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'US',
					'email'      => 'testaccount@test.com',
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(),
			)
		);
		// Restore cart for other tests.
		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
	}

	/**
	 * Test applying coupon to cart.
	 */
	public function test_apply_coupon() {
		wc()->cart->remove_coupon( $this->coupon->get_code() );

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

		$fixtures = new FixtureData();

		// Test coupons with different case.
		$newcoupon = $fixtures->get_coupon( array( 'code' => 'testCoupon' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'testCoupon',
			)
		);
		$this->assertAPIResponse(
			$request,
			200
		);

		// Test coupons with special chars in the code.
		$newcoupon = $fixtures->get_coupon( array( 'code' => '$5 off' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => '$5 off',
			)
		);
		$this->assertAPIResponse(
			$request,
			200
		);
	}

	/**
	 * Test removing coupon from cart.
	 */
	public function test_remove_coupon() {
		// Invalid coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'doesnotexist',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);

		// Applied coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-coupon' );
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
					'total_discount' => '0',
				),
			)
		);
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart', 'v1' );
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
		$this->assertArrayHasKey( 'cross_sells', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart', 'v1' );
		$cart       = wc()->cart;
		$response   = $controller->prepare_item_for_response( $cart, new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff );
	}

	/**
	 * Tests for Cart-Token header presence and validity.
	 */
	public function test_cart_token_header() {

		/** @var Spy_REST_Server $server */
		$server = rest_get_server();

		$server->serve_request( '/wc/store/cart' );

		$this->assertArrayHasKey( 'Cart-Token', $server->sent_headers );

		$this->assertTrue(
			JsonWebToken::validate(
				$server->sent_headers['Cart-Token'],
				'@' . wp_salt()
			)
		);
	}

	/**
	 * Test adding a variable product to cart returns proper variation data.
	 */
	public function test_add_variable_product_to_cart_returns_variation_data() {
		wc_empty_cart();

		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Test Variable Product 4',
				'stock_status'  => 'instock',
				'regular_price' => 10,
				'weight'        => 10,
			),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);

		$variable_product->save();

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array(
				'pa_color' => 'red',
				'pa_size'  => 'small',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$request->set_body_params(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => 1,
				'variation' => array( // intentionally alphabetically disordered.
					array(
						'attribute' => 'pa_color',
						'value'     => 'red',
					),
					array(
						'attribute' => 'pa_size',
						'value'     => 'small',
					),
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'items' => array(
					array(
						'variation' => array( // order matters, alphabetical attribute order.
							array(
								'attribute' => 'color',
								'value'     => 'red',
							),
							array(
								'attribute' => 'size',
								'value'     => 'small',
							),
						),
					),
				),
			)
		);
	}
}
