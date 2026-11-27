<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as CheckoutRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\CheckoutOrder as CheckoutOrderRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WC_Gateway_BACS;
use WC_Tax;

/**
 * Checkout Controller Tests.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
 */
class Checkout extends \WP_Test_REST_TestCase {
	use MockeryPHPUnitIntegration;
	use StoreApiRestTestCaseTrait;

	const TEST_COUPON_CODE = 'test_coupon_code';

	/**
	 * Product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Coupon ID shared by the class.
	 *
	 * @var int
	 */
	private static $coupon_id;

	/**
	 * Payment gateway registry before the test.
	 *
	 * @var array
	 */
	private $payment_gateways_before_test = array();

	/**
	 * PayPal gateway singleton before the test.
	 *
	 * @var \WC_Gateway_Paypal
	 */
	private $paypal_gateway_before_test;

	/**
	 * $_COOKIE before the test. filter_woocommerce_set_cookie_enabled() writes the cookies the
	 * checkout sets, such as the session and login cookies, into it.
	 *
	 * @var array<string, string>
	 */
	private $cookies_before_test = array();

	/**
	 * Tax rate IDs inserted by the current test, removed in tearDown().
	 *
	 * @var int[]
	 */
	private $inserted_tax_rate_ids = array();

	/**
	 * Create immutable catalog rows shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$product_ids = array_map(
			fn( $product ) => $product->get_id(),
			self::create_class_fixture_products(
				array(
					array(
						'name'          => 'Test Product 1',
						'stock_status'  => ProductStockStatus::IN_STOCK,
						'regular_price' => 10,
						'weight'        => 10,
					),
					array(
						'name'          => 'Test Product 2',
						'stock_status'  => ProductStockStatus::IN_STOCK,
						'regular_price' => 10,
						'weight'        => 10,
					),
					array(
						'name'          => 'Virtual Test Product 2',
						'stock_status'  => ProductStockStatus::IN_STOCK,
						'regular_price' => 10,
						'weight'        => 10,
						'virtual'       => true,
					),
				),
			)
		);

		$coupon = new \WC_Coupon();
		$coupon->set_code( self::TEST_COUPON_CODE );
		$coupon->set_amount( 2 );
		$coupon->save();
		self::$coupon_id = $coupon->get_id();
	}

	/**
	 * Delete class products through WooCommerce data stores.
	 */
	public static function wpTearDownAfterClass(): void {
		try {
			self::delete_class_fixture_products( self::$product_ids );
		} finally {
			$coupon = new \WC_Coupon( self::$coupon_id );
			$coupon->delete( true );
		}
	}

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->cookies_before_test = $_COOKIE;
		add_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ), 10, 4 );

		update_option( 'woocommerce_checkout_phone_field', 'optional' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

		$this->initialize_store_api_server();

		wp_set_current_user( 0 );

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$this->mock_extend = new ExtendSchema( $formatters );
		$this->mock_extend->register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'extension_namespace',
				'schema_callback' => function () {
					return array(
						'extension_key' => array(
							'description' => 'Test key',
							'type'        => 'boolean',
						),
					);
				},
			)
		);
		$schema_controller = new SchemaController( $this->mock_extend );
		$route             = new CheckoutRoute( $schema_controller, $schema_controller->get( 'checkout' ) );
		register_rest_route( $route->get_namespace(), $route->get_path(), $route->get_args(), true );
		$order_route = new CheckoutOrderRoute( $schema_controller, $schema_controller->get( 'checkout-order' ) );
		register_rest_route( $order_route->get_namespace(), $order_route->get_path(), $order_route->get_args(), true );

		$this->payment_gateways_before_test = WC()->payment_gateways()->payment_gateways;
		$this->paypal_gateway_before_test   = \WC_Gateway_Paypal::get_instance();

		$fixtures = new FixtureData();
		$fixtures->payments_enable_bacs();
		$fixtures->shipping_add_pickup_location();
		$fixtures->shipping_add_flat_rate_instance();

		$this->products = array_map( 'wc_get_product', self::$product_ids );
		wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

	/**
	 * Tear down Rest API server.
	 */
	protected function tearDown(): void {
		try {
			// Resets wc_log_order_step()'s static state between tests.
			wc_log_order_step( 'Test cleanup', null, true );

			$this->remove_inserted_tax_rates();

			remove_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ) );

			update_option( 'woocommerce_ship_to_countries', 'all' );
			update_option( 'woocommerce_ship_to_destination', 'billing' );
			update_option( 'woocommerce_allowed_countries', 'all' );
			update_option( 'woocommerce_enable_guest_checkout', 'yes' );
			update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

			$fixtures = new FixtureData();
			$fixtures->shipping_remove_pickup_location();
			$fixtures->shipping_remove_methods_from_default_zone();

			$customer_to_delete = get_user_by( 'email', 'testaccount@test.com' );
			if ( $customer_to_delete ) {
				wp_delete_user( $customer_to_delete->ID );
			}

			unset( WC()->countries->locale );
			WC()->cart->empty_cart();
			WC()->session->destroy_session();
			WC()->customer = null;
			WC()->initialize_cart();

			$GLOBALS['wp_rest_server'] = null;
		} finally {
			try {
				parent::tearDown();
			} finally {
				$this->invalidate_checkout_option_caches();
				WC()->payment_gateways()->payment_gateways = $this->payment_gateways_before_test;
				\WC_Gateway_Paypal::set_instance( $this->paypal_gateway_before_test );
				$_COOKIE = $this->cookies_before_test;
			}
		}
	}

	/**
	 * Enable taxes and add a 10% US/CA rate, so an address in California changes the cart total.
	 */
	private function enable_taxes_with_us_ca_rate(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'CA Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
			)
		);
	}

	/**
	 * Delete the tax rates inserted by the current test, so a failing test does not leak them into later ones.
	 */
	private function remove_inserted_tax_rates(): void {
		if ( ! $this->inserted_tax_rate_ids ) {
			return;
		}

		foreach ( $this->inserted_tax_rate_ids as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}

		$this->inserted_tax_rate_ids = array();
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Format the current cart total the way the client sends it in `expected_total`.
	 */
	private function get_cart_total_in_minor_units(): string {
		return (string) (int) round( (float) WC()->cart->get_total( 'edit' ) * pow( 10, wc_get_price_decimals() ), 0, PHP_ROUND_HALF_UP );
	}

	/**
	 * Invalidate caches for options modified by checkout tests.
	 */
	private function invalidate_checkout_option_caches(): void {
		$option_names = array(
			'woocommerce_checkout_phone_field',
			'woocommerce_enable_guest_checkout',
			'woocommerce_enable_signup_and_login_from_checkout',
			'woocommerce_ship_to_countries',
			'woocommerce_allowed_countries',
			'woocommerce_specific_ship_to_countries',
			'woocommerce_specific_allowed_countries',
			'woocommerce_bacs_settings',
			'woocommerce_pickup_location_settings',
			'pickup_location_pickup_locations',
			'woocommerce_calc_taxes',
			'woocommerce_tax_based_on',
			'woocommerce_ship_to_destination',
		);

		foreach ( $option_names as $option_name ) {
			wp_cache_delete( $option_name, 'options' );
		}
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Filter wc_setcookie() to disable calling setcookie() during the tests but apply the changes to the $_COOKIE global.
	 *
	 * @param bool    $enabled Filtered value of whether calls to setcookie() are enabled.
	 * @param string  $name    Name of the cookie being set.
	 * @param string  $value   Value of the cookie.
	 * @param integer $expire  Expiry of the cookie.
	 *
	 * @return false
	 */
	public function filter_woocommerce_set_cookie_enabled( $enabled, $name, $value, $expire ) {
		if ( $expire < time() ) {
			unset( $_COOKIE[ $name ] );
		} else {
			$_COOKIE[ $name ] = $value;
		}

		return false;
	}

	/**
	 * Ensure that orders can be placed.
	 */
	public function test_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * @testdox Should preserve cart tax totals through checkout and order reload.
	 *
	 * @dataProvider checkout_tax_totals_provider
	 * @param string $tax_mode Whether catalog prices include tax.
	 * @param string $country Customer billing country.
	 * @param bool   $adjust_non_base_prices Whether to adjust prices outside the base location.
	 * @param string $expected_subtotal Expected line subtotal excluding tax.
	 * @param string $expected_tax Expected tax total.
	 * @param string $expected_total Expected order total.
	 */
	public function test_checkout_preserves_cart_tax_totals( string $tax_mode, string $country, bool $adjust_non_base_prices, string $expected_subtotal, string $expected_tax, string $expected_total ): void {
		$options          = array(
			'woocommerce_calc_taxes'         => 'yes',
			'woocommerce_prices_include_tax' => $tax_mode,
			'woocommerce_tax_based_on'       => 'billing',
			'woocommerce_default_country'    => 'GB',
			'woocommerce_price_num_decimals' => '2',
		);
		$previous_options = array();
		foreach ( $options as $name => $value ) {
			$previous_options[ $name ] = get_option( $name );
			update_option( $name, $value );
		}
		$adjust_prices_filter = $adjust_non_base_prices ? '__return_true' : '__return_false';
		add_filter( 'woocommerce_adjust_non_base_location_prices', $adjust_prices_filter );
		$tax_rate_ids = array();

		try {
			\WC_Tax::create_tax_class( 'Checkout tax totals', 'checkout-tax-totals' );
			$tax_rates = array(
				'GB' => '20',
				'DE' => '10',
			);
			foreach ( $tax_rates as $tax_country => $rate ) {
				$tax_rate_ids[] = \WC_Tax::_insert_tax_rate(
					array(
						'tax_rate_country'  => $tax_country,
						'tax_rate'          => $rate,
						'tax_rate_name'     => 'Checkout tax',
						'tax_rate_priority' => 1,
						'tax_rate_compound' => 0,
						'tax_rate_shipping' => 0,
						'tax_rate_class'    => 'checkout-tax-totals',
					)
				);
			}
			$fixtures = new FixtureData();
			$product  = $fixtures->get_simple_product(
				array(
					'regular_price' => '132',
					'virtual'       => true,
					'tax_status'    => 'taxable',
					'tax_class'     => 'checkout-tax-totals',
				)
			);
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $product->get_id() );
			WC()->customer->set_is_vat_exempt( false );
			$billing_address = (object) array(
				'first_name' => 'Test',
				'last_name'  => 'Customer',
				'address_1'  => '1 Test Street',
				'city'       => 'Test City',
				'state'      => '',
				'postcode'   => 'GB' === $country ? 'CB24 1AB' : '10115',
				'country'    => $country,
				'email'      => 'testaccount@test.com',
			);

			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params( array( 'billing_address' => $billing_address ) );
			$cart_response = rest_get_server()->dispatch( $request );
			$this->assertSame( 200, $cart_response->get_status(), 'The cart request should succeed.' );
			$cart_totals = (array) $cart_response->get_data()['totals'];
			$this->assertSame( $expected_subtotal, $cart_totals['total_items'], 'Cart line subtotal should exclude the applicable tax.' );
			$this->assertSame( $expected_tax, $cart_totals['total_tax'], 'Cart tax should use the customer location and adjustment setting.' );
			$this->assertSame( $expected_total, $cart_totals['total_price'], 'Cart total should match the hand-calculated amount.' );

			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params(
				array(
					'billing_address' => $billing_address,
					'payment_method'  => WC_Gateway_BACS::ID,
					'expected_total'  => $expected_total,
				)
			);
			$response = rest_get_server()->dispatch( $request );
			$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
			$this->assertGreaterThan( 0, $response->get_data()['order_id'], 'Checkout should create an order.' );

			update_option( 'woocommerce_prices_include_tax', 'yes' === $tax_mode ? 'no' : 'yes' );
			$order = new \WC_Order( $response->get_data()['order_id'] );
			$this->assertSame( 'yes' === $tax_mode, $order->get_prices_include_tax(), 'Persisted checkout tax mode should survive a store setting change.' );
			$this->assertSame( (float) $expected_subtotal / 100, (float) $order->get_subtotal(), 'Saved line subtotal should match the cart.' );
			$this->assertSame( (float) $expected_tax / 100, (float) $order->get_total_tax(), 'Saved order tax should match the cart.' );
			$this->assertSame( (float) $expected_total / 100, (float) $order->get_total(), 'Saved order total should match the cart.' );
		} finally {
			remove_filter( 'woocommerce_adjust_non_base_location_prices', $adjust_prices_filter );
			foreach ( $tax_rate_ids as $tax_rate_id ) {
				\WC_Tax::_delete_tax_rate( $tax_rate_id );
			}
			\WC_Tax::delete_tax_class_by( 'slug', 'checkout-tax-totals' );
			foreach ( $previous_options as $name => $value ) {
				if ( false === $value ) {
					delete_option( $name );
				} else {
					update_option( $name, $value );
				}
			}
		}
	}

	/**
	 * Tax totals for a 132 catalog price with 20% base tax and 10% non-base tax, in minor units.
	 *
	 * @return array
	 */
	public static function checkout_tax_totals_provider(): array {
		return array(
			'inclusive base adjusted'       => array( 'yes', 'GB', true, '11000', '2200', '13200' ),
			'inclusive base unadjusted'     => array( 'yes', 'GB', false, '11000', '2200', '13200' ),
			'inclusive non-base adjusted'   => array( 'yes', 'DE', true, '11000', '1100', '12100' ),
			'inclusive non-base unadjusted' => array( 'yes', 'DE', false, '12000', '1200', '13200' ),
			'exclusive base adjusted'       => array( 'no', 'GB', true, '13200', '2640', '15840' ),
			'exclusive base unadjusted'     => array( 'no', 'GB', false, '13200', '2640', '15840' ),
			'exclusive non-base adjusted'   => array( 'no', 'DE', true, '13200', '1320', '14520' ),
			'exclusive non-base unadjusted' => array( 'no', 'DE', false, '13200', '1320', '14520' ),
		);
	}

	/**
	 * Ensure an order is placed when the expected total sent by the client matches the server total.
	 */
	public function test_post_data_accepts_matching_expected_total() {
		WC()->cart->calculate_totals();
		$expected_total = (string) (int) round( (float) WC()->cart->get_total( 'edit' ) * pow( 10, wc_get_price_decimals() ), 0, PHP_ROUND_HALF_UP );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => $expected_total,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * Ensure an order is rejected with a 409 when the expected total sent by the client no longer
	 * matches the total calculated on the server, and the refreshed cart is returned.
	 */
	public function test_post_data_rejects_mismatched_expected_total() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				// A total the customer could never have seen for this cart.
				'expected_total'   => '1',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status(), print_r( $data, true ) );
		$this->assertEquals( 'woocommerce_rest_checkout_total_mismatch', $data['code'] );
		$this->assertArrayHasKey( 'cart', $data['data'], 'The refreshed cart should be returned so the client can display the updated total.' );
		$this->assertEquals( 1, $data['data']['expected_total'] );
		$this->assertGreaterThan( 1, $data['data']['actual_total'] );
	}

	/**
	 * @testdox A confirmed total of zero should still be reported back in the mismatch payload.
	 *
	 * RouteException filters its additional data, and a naive filter drops `0`, which is a total a free
	 * cart can legitimately confirm before the request's address adds tax or shipping.
	 */
	public function test_post_data_reports_zero_expected_total_in_mismatch_payload() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '0',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status(), print_r( $data, true ) );
		$this->assertArrayHasKey( 'expected_total', $data['data'], 'A confirmed total of zero should not be filtered out of the error payload.' );
		$this->assertSame( 0, $data['data']['expected_total'] );
	}

	/**
	 * Ensure an expected total that is not in the documented minor-unit format is rejected outright,
	 * rather than being coerced to an integer and silently weakening the guard.
	 */
	public function test_post_data_rejects_malformed_expected_total() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				// A major-unit total: `(int) '30.00'` is 30, which would pass a check meant to compare 3000.
				'expected_total'   => '30.00',
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * @testdox Should reject the order when this request's address changes the total, even if that total matches the pre-request session total.
	 *
	 * validate_order_totals() runs before update_customer_from_request(), so it checks the total for the
	 * address already in the session, not the address this request is submitting. A client that (correctly)
	 * echoes back the last total it saw before editing the address ends up matching that stale session
	 * total, and the guard waves the order through even though the address in this very request raises it.
	 */
	public function test_post_data_rejects_expected_total_when_this_requests_address_changes_tax() {
		$this->enable_taxes_with_us_ca_rate();

		// The address already in the session before this request: untaxed.
		WC()->customer->set_billing_country( 'GB' );
		WC()->customer->set_shipping_country( 'GB' );
		WC()->cart->calculate_totals();
		$expected_total = $this->get_cart_total_in_minor_units();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '123 Main St',
					'address_2'  => '',
					'city'       => 'Beverly Hills',
					'state'      => 'CA',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '123 Main St',
					'address_2'  => '',
					'city'       => 'Beverly Hills',
					'state'      => 'CA',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				// The total for the GB address already in session, not for the CA address below.
				'expected_total'   => $expected_total,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 409, $response->get_status(), 'The order should be rejected because the CA address in this request raises the total above what the shopper confirmed: ' . print_r( $data, true ) );
		$this->assertEquals( 'woocommerce_rest_checkout_total_mismatch', $data['code'], 'The rejection should come from the expected_total guard, not another 409.' );
		$this->assertEquals( (int) $expected_total, $data['data']['expected_total'] );
		$this->assertGreaterThan( (int) $expected_total, $data['data']['actual_total'] );
	}

	/**
	 * @testdox Should accept the order when expected_total is correctly computed for this request's own address.
	 *
	 * A single-POST client (per the documented Store API flow) can submit an address for the first time
	 * in the place-order request itself, with no prior PUT to sync it to the session. If it correctly
	 * computes expected_total for that address, the order should place - but validate_order_totals() checks
	 * the pre-request (addressless) session total instead, so it rejects a correctly-computed request.
	 */
	public function test_post_data_accepts_expected_total_correctly_computed_for_this_requests_address() {
		$this->enable_taxes_with_us_ca_rate();

		// No address in the session yet, matching a fresh single-POST checkout.
		WC()->customer->set_billing_country( '' );
		WC()->customer->set_shipping_country( '' );

		// Compute the total the order will actually settle at once the CA address in this request
		// is applied - this is what a correctly implemented client would send as expected_total.
		WC()->customer->set_billing_country( 'US' );
		WC()->customer->set_billing_state( 'CA' );
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->cart->calculate_totals();
		$expected_total = $this->get_cart_total_in_minor_units();

		// Reset the session back to addressless, since the client hasn't PUT the address yet.
		WC()->customer->set_billing_country( '' );
		WC()->customer->set_shipping_country( '' );
		WC()->cart->calculate_totals();

		// Without this the test would pass vacuously if the tax rate above stopped applying.
		$this->assertNotSame( $expected_total, $this->get_cart_total_in_minor_units(), 'The CA rate must move the total, otherwise this scenario does not exercise the guard.' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '123 Main St',
					'address_2'  => '',
					'city'       => 'Beverly Hills',
					'state'      => 'CA',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '123 Main St',
					'address_2'  => '',
					'city'       => 'Beverly Hills',
					'state'      => 'CA',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => $expected_total,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'The order should place because expected_total is exactly what the server will charge for the CA address in this request: ' . print_r( $data, true ) );
	}

	/**
	 * Ensure the total check is skipped (order placed) when the client does not send an expected total.
	 */
	public function test_post_data_skips_total_check_when_not_provided() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * Ensure a free order (zero total) is accepted when the client sends a matching expected total.
	 */
	public function test_post_data_accepts_matching_expected_total_for_free_order() {
		$fixtures     = new FixtureData();
		$free_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Free Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 0,
				'virtual'       => true,
			)
		);

		wc_empty_cart();
		wc()->cart->add_to_cart( $free_product->get_id(), 1 );
		wc()->cart->calculate_totals();

		// The cart is genuinely free, and a zero total serialises to the string "0".
		$expected_total = (string) (int) round( (float) WC()->cart->get_total( 'edit' ) * pow( 10, wc_get_price_decimals() ), 0, PHP_ROUND_HALF_UP );
		$this->assertSame( '0', $expected_total );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '1234567890',
					'email'      => 'testaccount@test.com',
				),
				'payment_method'  => WC_Gateway_BACS::ID,
				'expected_total'  => $expected_total,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * @testdox Each checkout served by the same route instance works on its own order.
	 */
	public function test_checkouts_on_same_route_instance_use_their_own_orders() {
		$fixtures     = new FixtureData();
		$free_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Free Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 0,
				'virtual'       => true,
			)
		);

		wc_empty_cart();
		wc()->cart->add_to_cart( $free_product->get_id(), 1 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'billing_address' => (object) $this->get_fallback_billing_address() ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$first_order_id = $response->get_data()['order_id'];
		$this->assertTrue( wc_get_order( $first_order_id )->is_paid() );

		wc_empty_cart();
		wc()->cart->add_to_cart( self::$product_ids[2], 5 );

		// Both dispatches go through the same registered route instance, which is also how the batch endpoint serves its children.
		$billing_address              = $this->get_fallback_billing_address();
		$billing_address['last_name'] = '';
		$request                      = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'billing_address' => (object) $billing_address ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status(), print_r( $response->get_data(), true ) );

		$first_order = wc_get_order( $first_order_id );
		$this->assertSame( 0.0, (float) $first_order->get_total() );
		$this->assertCount( 1, $first_order->get_items() );
	}

	/**
	 * @testdox Each checkout hands the gateway the payment data from its own request.
	 */
	public function test_each_checkout_hands_gateway_its_own_payment_data() {
		$seen_payment_data = array();
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context ) use ( &$seen_payment_data ) {
				$seen_payment_data[] = $context->payment_data['token'] ?? null;
			}
		);

		foreach ( array( 'first', 'second' ) as $token ) {
			wc_empty_cart();
			wc()->cart->add_to_cart( self::$product_ids[2], 1 );

			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params(
				array(
					'billing_address' => (object) $this->get_fallback_billing_address(),
					'payment_method'  => WC_Gateway_BACS::ID,
					'payment_data'    => array(
						array(
							'key'   => 'token',
							'value' => $token,
						),
					),
				)
			);
			$response = rest_get_server()->dispatch( $request );
			$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		}

		$this->assertSame( array( 'first', 'second' ), $seen_payment_data );
	}

	/**
	 * @testdox Checkouts in one batch request each work on their own order.
	 */
	public function test_batch_checkouts_use_their_own_orders() {
		$fixtures     = new FixtureData();
		$free_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Free Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 0,
				'virtual'       => true,
			)
		);

		wc_empty_cart();

		$nonce        = wp_create_nonce( 'wc_store_api' );
		$good_address = (object) $this->get_fallback_billing_address();
		$bad_address  = $this->get_fallback_billing_address();
		unset( $bad_address['last_name'] );
		$bad_address = (object) array_merge( $bad_address, array( 'last_name' => '' ) );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', $nonce );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'headers' => array( 'Nonce' => $nonce ),
						'body'    => array(
							'id'       => $free_product->get_id(),
							'quantity' => 1,
						),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/checkout',
						'headers' => array( 'Nonce' => $nonce ),
						'body'    => array( 'billing_address' => $good_address ),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'headers' => array( 'Nonce' => $nonce ),
						'body'    => array(
							'id'       => self::$product_ids[2],
							'quantity' => 5,
						),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/checkout',
						'headers' => array( 'Nonce' => $nonce ),
						'body'    => array( 'billing_address' => $bad_address ),
					),
				),
			)
		);

		$responses = rest_get_server()->dispatch( $request )->get_data()['responses'];

		// The first checkout completes; the second is rejected.
		$this->assertSame( 200, $responses[1]['status'], print_r( $responses[1]['body'], true ) );
		$first_order_id = $responses[1]['body']['order_id'];
		$this->assertSame( 400, $responses[3]['status'], print_r( $responses[3]['body'], true ) );

		$first_order = wc_get_order( $first_order_id );
		$this->assertTrue( $first_order->is_paid() );
		$this->assertSame( 0.0, (float) $first_order->get_total() );
		$this->assertCount( 1, $first_order->get_items() );
	}

	/**
	 * @testdox A failed checkout request leaves other orders' reserved stock alone.
	 */
	public function test_failed_checkout_request_keeps_other_orders_reserved_stock() {
		update_option( 'woocommerce_hold_stock_minutes', '60' );

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Reserved Stock Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 100 );
		$product->save();

		// First request: a pending order that holds reserved stock.
		wc_empty_cart();
		wc()->cart->add_to_cart( $product->get_id(), 3 );

		$order = new \WC_Order();
		$order->add_product( $product, 3 );
		$order->set_status( OrderStatus::PENDING );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->calculate_totals();
		$order->save();
		wc_reserve_stock_for_order( $order );
		wc()->session->set( 'store_api_draft_order', $order->get_id() );

		$reserve = new \Automattic\WooCommerce\Checkout\Helpers\ReserveStock();
		$this->assertSame( 3, $reserve->get_reserved_stock( $product, 0 ), 'Setup: the order should hold 3 reserved units.' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );

		// Second request on the same route instance fails early.
		wc_empty_cart();
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'billing_address' => (object) $this->get_fallback_billing_address() ) );
		$this->assertSame( 'woocommerce_rest_cart_empty', rest_get_server()->dispatch( $request )->get_data()['code'] );

		// The first order still holds its reserved stock.
		$this->assertSame( 3, $reserve->get_reserved_stock( $product, 0 ) );
	}

	/**
	 * Billing address used by the shipping address fallback tests.
	 *
	 * @return array
	 */
	private function get_fallback_billing_address() {
		return array(
			'first_name' => 'Billing',
			'last_name'  => 'Person',
			'company'    => '',
			'address_1'  => '10 Billing Street',
			'address_2'  => '',
			'city'       => 'Cambridge',
			'state'      => '',
			'postcode'   => 'cb241ab',
			'country'    => 'GB',
			'phone'      => '01223 000000',
			'email'      => 'testaccount@test.com',
		);
	}

	/**
	 * Shipping address used by the shipping address fallback tests.
	 *
	 * @return array
	 */
	private function get_fallback_shipping_address() {
		return array(
			'first_name' => 'Shipping',
			'last_name'  => 'Person',
			'company'    => '',
			'address_1'  => '20 Shipping Street',
			'address_2'  => '',
			'city'       => 'Oxford',
			'state'      => '',
			'postcode'   => 'ox11aa',
			'country'    => 'GB',
			'phone'      => '01865 000000',
		);
	}

	/**
	 * @testdox Address values should round-trip through the cart response and checkout without HTML encoding.
	 */
	public function test_address_values_round_trip_without_html_encoding(): void {
		$billing_address             = $this->get_fallback_billing_address();
		$billing_address['company']  = 'AT&T';
		$shipping_address            = $this->get_fallback_shipping_address();
		$shipping_address['company'] = "St John's";

		$update_request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$update_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$update_request->set_body_params(
			array(
				'billing_address'  => (object) $billing_address,
				'shipping_address' => (object) $shipping_address,
			)
		);

		$update_response = rest_get_server()->dispatch( $update_request );
		$this->assertSame( 200, $update_response->get_status(), print_r( $update_response->get_data(), true ) );
		$cart_data                 = $update_response->get_data();
		$response_billing_address  = (array) $cart_data['billing_address'];
		$response_shipping_address = (array) $cart_data['shipping_address'];

		$this->assertSame( 'AT&T', $response_billing_address['company'] );
		$this->assertSame( "St John's", $response_shipping_address['company'] );

		$checkout_request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$checkout_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$checkout_request->set_body_params(
			array(
				'billing_address'  => (object) $response_billing_address,
				'shipping_address' => (object) $response_shipping_address,
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$checkout_response = rest_get_server()->dispatch( $checkout_request );
		$this->assertSame( 200, $checkout_response->get_status(), print_r( $checkout_response->get_data(), true ) );

		$order = wc_get_order( $checkout_response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertSame( 'AT&T', $order->get_billing_company( 'edit' ) );
		$this->assertSame( "St John's", $order->get_shipping_company( 'edit' ) );
	}

	/**
	 * When the cart needs shipping and the request omits the shipping address, the billing address is used as the
	 * shipping address.
	 *
	 * Regression test: the fallback never ran because `(array)` binds tighter than `??`, so the null coalesce
	 * operated on an already-cast empty array instead of on the missing `shipping_address` param. That left the
	 * order without a shipping address, which then failed pre-payment validation.
	 */
	public function test_omitted_shipping_address_falls_back_to_billing_address() {
		$this->assertTrue( WC()->cart->needs_shipping(), 'Test cart is expected to need shipping.' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address' => (object) $this->get_fallback_billing_address(),
				'payment_method'  => WC_Gateway_BACS::ID,
				'expected_total'  => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertEquals( 'Billing', $order->get_shipping_first_name() );
		$this->assertEquals( 'Person', $order->get_shipping_last_name() );
		$this->assertEquals( '10 Billing Street', $order->get_shipping_address_1() );
		$this->assertEquals( 'Cambridge', $order->get_shipping_city() );
		$this->assertEquals( 'CB24 1AB', $order->get_shipping_postcode() );
		$this->assertEquals( 'GB', $order->get_shipping_country() );
	}

	/**
	 * When the cart needs shipping and a shipping address is provided, it is used as-is rather than being overwritten
	 * by the billing address fallback.
	 */
	public function test_provided_shipping_address_is_used_when_cart_needs_shipping() {
		$this->assertTrue( WC()->cart->needs_shipping(), 'Test cart is expected to need shipping.' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $this->get_fallback_billing_address(),
				'shipping_address' => (object) $this->get_fallback_shipping_address(),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertEquals( 'Shipping', $order->get_shipping_first_name() );
		$this->assertEquals( '20 Shipping Street', $order->get_shipping_address_1() );
		$this->assertEquals( 'Oxford', $order->get_shipping_city() );
	}

	/**
	 * When the cart does not need shipping, the provided shipping address is ignored in favour of the billing address.
	 */
	public function test_shipping_address_is_ignored_when_cart_does_not_need_shipping() {
		wc_empty_cart();
		WC()->cart->add_to_cart( $this->products[2]->get_id(), 1 );
		$this->assertFalse( WC()->cart->needs_shipping(), 'Test cart is expected to not need shipping.' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $this->get_fallback_billing_address(),
				'shipping_address' => (object) $this->get_fallback_shipping_address(),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '1000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertEquals( 'Billing', $order->get_shipping_first_name() );
		$this->assertEquals( '10 Billing Street', $order->get_shipping_address_1() );
		$this->assertEquals( 'Cambridge', $order->get_shipping_city() );
	}

	/**
	 * Turns on taxes based on the shipping address, with a rate for Ontario and Alberta and none for Canada as a whole.
	 */
	private function add_canada_tax_rates() {
		$this->set_taxes_based_on( 'shipping' );
		$rates = array(
			'ON' => '13.0000',
			'AB' => '5.0000',
		);

		foreach ( $rates as $state => $rate ) {
			$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'CA',
					'tax_rate_state'    => $state,
					'tax_rate'          => $rate,
					'tax_rate_name'     => "$state tax",
					'tax_rate_priority' => '1',
					'tax_rate_compound' => '0',
					'tax_rate_shipping' => '1',
					'tax_rate_order'    => '1',
				)
			);
		}
	}

	/**
	 * Toronto address used by the address merge tests.
	 *
	 * @param array $overrides Fields to change; a null value removes the field from the address.
	 * @return array
	 */
	private function get_toronto_address( array $overrides = array() ) {
		$address = array_merge(
			array(
				'first_name' => 'Guest',
				'last_name'  => 'Shopper',
				'company'    => '',
				'address_1'  => '100 Queen St W',
				'address_2'  => '',
				'city'       => 'Toronto',
				'state'      => 'ON',
				'postcode'   => 'M5H 2N2',
				'country'    => 'CA',
				'email'      => 'testaccount@test.com',
				'phone'      => '',
			),
			$overrides
		);

		return array_filter( $address, fn( $value ) => null !== $value );
	}

	/**
	 * Dispatches a checkout request with the given addresses.
	 *
	 * @param array      $billing  Billing address.
	 * @param array|null $shipping Shipping address, or null to leave it out.
	 * @return \WP_REST_Response
	 */
	private function dispatch_checkout_with_addresses( array $billing, ?array $shipping ) {
		$params = array(
			'billing_address' => (object) $billing,
			'payment_method'  => WC_Gateway_BACS::ID,
		);
		if ( null !== $shipping ) {
			$params['shipping_address'] = (object) $shipping;
		}

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $params );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Sets the session customer's billing and shipping country and state.
	 *
	 * @param string $country Country code.
	 * @param string $state   State.
	 */
	private function set_session_customer_location( string $country, string $state ) {
		WC()->customer->set_billing_location( $country, $state, '', '' );
		WC()->customer->set_shipping_location( $country, $state, '', '' );
		WC()->customer->save();
	}

	/**
	 * @testdox Checkout should store the state code when the address names the state and leaves out the country.
	 */
	public function test_checkout_converts_state_name_when_country_is_omitted() {
		$this->add_canada_tax_rates();
		$this->set_session_customer_location( 'CA', 'ON' );
		$address = $this->get_toronto_address(
			array(
				'state'   => 'Ontario',
				'country' => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertSame( 'ON', $order->get_shipping_state() );
		$this->assertSame( 'ON', $order->get_billing_state() );
		$this->assertGreaterThan( 0, (float) $order->get_total_tax(), 'The Ontario rate must apply.' );
	}

	/**
	 * @testdox Checkout should reject an unknown state when the address leaves out the country, without changing the session.
	 */
	public function test_checkout_rejects_unknown_state_when_country_is_omitted() {
		$this->set_session_customer_location( 'CA', 'ON' );
		$address = $this->get_toronto_address(
			array(
				'state'   => 'Nowhere',
				'country' => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( 'ON', WC()->customer->get_shipping_state(), 'A rejected request must not change the session customer.' );
	}

	/**
	 * @testdox Checkout should reject a postcode that is not valid for the session country when the address leaves out the country.
	 */
	public function test_checkout_rejects_invalid_postcode_when_country_is_omitted() {
		$this->set_session_customer_location( 'CA', 'ON' );
		$address = $this->get_toronto_address(
			array(
				'postcode' => 'NOTAZIP',
				'country'  => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( '', WC()->customer->get_shipping_postcode(), 'A rejected request must not change the session customer.' );
	}

	/**
	 * @testdox Checkout should format the postcode for the session country when the address leaves out the country.
	 */
	public function test_checkout_formats_postcode_when_country_is_omitted() {
		$this->set_session_customer_location( 'CA', 'ON' );
		$address = $this->get_toronto_address(
			array(
				'postcode' => 'm5h2n2',
				'country'  => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertSame( 'M5H 2N2', $order->get_shipping_postcode() );
		$this->assertSame( 'M5H 2N2', $order->get_billing_postcode() );
	}

	/**
	 * @testdox Checkout should store the state code when the address leaves out the state and the session holds its name.
	 */
	public function test_checkout_converts_session_state_name_when_state_is_omitted() {
		$this->add_canada_tax_rates();
		// The classic update_order_review handler stores the state as posted.
		$this->set_session_customer_location( 'CA', 'Ontario' );
		$address = $this->get_toronto_address( array( 'state' => null ) );

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'ON', wc_get_order( $response->get_data()['order_id'] )->get_shipping_state() );
	}

	/**
	 * @testdox Checkout should reject a state of "0".
	 */
	public function test_checkout_rejects_state_zero() {
		$address = $this->get_toronto_address( array( 'state' => '0' ) );

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Checkout should store the state code for a country code sent in lower case.
	 */
	public function test_checkout_formats_state_for_lowercase_country() {
		$address = $this->get_toronto_address(
			array(
				'city'     => 'Chicago',
				'state'    => 'ıl',
				'postcode' => '60601',
				'country'  => 'us',
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertSame( 'US', $order->get_shipping_country() );
		$this->assertSame( 'IL', $order->get_shipping_state() );
	}

	/**
	 * @testdox Checkout should accept a complete address when the session holds stale values for another country.
	 */
	public function test_checkout_accepts_full_address_over_stale_session_values() {
		WC()->customer->set_props(
			array(
				'billing_country'   => 'GB',
				'billing_postcode'  => 'NOTAZIP',
				'billing_phone'     => 'abc',
				'shipping_country'  => 'GB',
				'shipping_postcode' => 'NOTAZIP',
				'shipping_phone'    => 'abc',
			)
		);
		WC()->customer->save();
		$address = $this->get_toronto_address();

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
	}

	/**
	 * @testdox Checkout should not validate stale session fields that the address leaves out, other than the country, state and postcode.
	 */
	public function test_checkout_ignores_stale_session_fields_left_out_of_the_address() {
		WC()->customer->set_props(
			array(
				'billing_country'   => 'CA',
				'billing_state'     => 'ON',
				'billing_postcode'  => 'M5H 2N2',
				'billing_phone'     => 'abc',
				'shipping_country'  => 'CA',
				'shipping_state'    => 'ON',
				'shipping_postcode' => 'M5H 2N2',
				'shipping_phone'    => 'abc',
			)
		);
		WC()->customer->save();
		$address = $this->get_toronto_address(
			array(
				'postcode' => null,
				'phone'    => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
	}

	/**
	 * @testdox Checkout should reject a session postcode that is not valid for the country when the $param leaves out the postcode, without changing the session.
	 * @testWith ["billing_address"]
	 *           ["shipping_address"]
	 *
	 * @param string $param The address that leaves out the postcode.
	 */
	public function test_checkout_rejects_session_postcode_not_valid_for_the_country( string $param ) {
		WC()->customer->set_props(
			array(
				'billing_country'   => 'CA',
				'billing_state'     => 'ON',
				'billing_postcode'  => 'NOTAZIP',
				'shipping_country'  => 'CA',
				'shipping_state'    => 'ON',
				'shipping_postcode' => 'NOTAZIP',
			)
		);
		WC()->customer->save();
		$addresses           = array(
			'billing_address'  => $this->get_toronto_address(),
			'shipping_address' => $this->get_toronto_address(),
		);
		$addresses[ $param ] = $this->get_toronto_address( array( 'postcode' => null ) );

		$response = $this->dispatch_checkout_with_addresses( $addresses['billing_address'], $addresses['shipping_address'] );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertArrayHasKey( $param, $response->get_data()['data']['details'] );
		$this->assertSame( 'NOTAZIP', WC()->customer->get_billing_postcode(), 'A rejected request must not save the billing address.' );
		$this->assertSame( 'NOTAZIP', WC()->customer->get_shipping_postcode(), 'A rejected request must not save the shipping address.' );
	}

	/**
	 * @testdox Checkout should reject a postcode saved for a country with no postcode rule when the address changes the country and leaves out the postcode.
	 */
	public function test_checkout_rejects_session_postcode_saved_for_another_country() {
		$location = array(
			'country'  => 'AE',
			'state'    => '',
			'postcode' => 'NOTAZIP',
		);
		$request  = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $location,
				'shipping_address' => (object) $location,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$address  = $this->get_toronto_address( array( 'postcode' => null ) );
		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( 'AE', WC()->customer->get_shipping_country(), 'A rejected request must not change the session customer.' );
	}

	/**
	 * @testdox Checkout should format a session postcode for the country when the address leaves out the postcode.
	 */
	public function test_checkout_formats_session_postcode_when_postcode_is_omitted() {
		WC()->customer->set_billing_location( 'CA', 'ON', 'm5h2n2', '' );
		WC()->customer->set_shipping_location( 'CA', 'ON', 'm5h2n2', '' );
		WC()->customer->save();
		$address = $this->get_toronto_address( array( 'postcode' => null ) );

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertSame( 'M5H 2N2', $order->get_shipping_postcode() );
		$this->assertSame( 'M5H 2N2', $order->get_billing_postcode() );
	}

	/**
	 * @testdox Checkout should ignore a stale session shipping address when the cart needs no shipping.
	 */
	public function test_checkout_without_shipping_ignores_stale_session_shipping() {
		wc_empty_cart();
		WC()->cart->add_to_cart( $this->products[2]->get_id(), 1 );
		WC()->customer->set_shipping_location( 'CA', 'Nowhere', '', '' );
		WC()->customer->save();

		$response = $this->dispatch_checkout_with_addresses( $this->get_toronto_address(), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'ON', wc_get_order( $response->get_data()['order_id'] )->get_shipping_state() );
	}

	/**
	 * @testdox Checkout should send error details with a rejected address, so the Checkout block can show a notice.
	 */
	public function test_checkout_rejected_address_includes_error_details() {
		$this->set_session_customer_location( 'CA', 'ON' );
		$address = $this->get_toronto_address(
			array(
				'state'   => 'Nowhere',
				'country' => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$data = $response->get_data()['data'];
		$this->assertArrayHasKey( 'details', $data, 'The Checkout block builds its notices from details.' );
		$this->assertArrayHasKey( 'billing_address', $data['details'] );
		$this->assertArrayHasKey( 'message', $data['details']['billing_address'] );
		$this->assertStringContainsString( 'NOWHERE', $data['details']['billing_address']['message'] );
	}

	/**
	 * @testdox Checkout should reject an invalid $invalid_param without saving the other, valid address to the session.
	 * @testWith ["billing_address"]
	 *           ["shipping_address"]
	 *
	 * @param string $invalid_param The address that gets the invalid state.
	 */
	public function test_checkout_rejects_one_invalid_address_without_saving_the_other( string $invalid_param ) {
		$this->set_session_customer_location( 'CA', 'ON' );
		$addresses = array(
			'billing_address'  => $this->get_toronto_address(
				array(
					'city'     => 'Montreal',
					'state'    => 'QC',
					'postcode' => 'H2Y 1C6',
				)
			),
			'shipping_address' => $this->get_toronto_address(
				array(
					'city'     => 'Montreal',
					'state'    => 'QC',
					'postcode' => 'H2Y 1C6',
				)
			),
		);

		$addresses[ $invalid_param ] = $this->get_toronto_address(
			array(
				'state'   => 'Nowhere',
				'country' => null,
			)
		);

		$response = $this->dispatch_checkout_with_addresses( $addresses['billing_address'], $addresses['shipping_address'] );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertArrayHasKey( $invalid_param, $response->get_data()['data']['details'] );
		$this->assertSame( 'ON', WC()->customer->get_billing_state(), 'A rejected request must not save the billing address.' );
		$this->assertSame( 'ON', WC()->customer->get_shipping_state(), 'A rejected request must not save the shipping address.' );
	}

	/**
	 * @testdox Checkout should copy a partial billing address to shipping when the cart needs no shipping, ignoring the session shipping address.
	 */
	public function test_checkout_without_shipping_derives_shipping_from_partial_billing() {
		wc_empty_cart();
		WC()->cart->add_to_cart( $this->products[2]->get_id(), 1 );
		WC()->customer->set_billing_location( 'CA', 'ON', '', '' );
		WC()->customer->set_shipping_location( 'CA', 'Nowhere', '', '' );
		WC()->customer->save();

		$response = $this->dispatch_checkout_with_addresses( $this->get_toronto_address( array( 'state' => null ) ), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'ON', wc_get_order( $response->get_data()['order_id'] )->get_shipping_state() );
	}

	/**
	 * @testdox Checkout should reject a partial address whose session state is not valid for the posted country, like cart/update-customer does.
	 */
	public function test_checkout_rejects_partial_address_with_session_state_invalid_for_new_country() {
		$this->set_session_customer_location( 'CA', 'ON' );
		$berlin  = array(
			'address_1' => 'Unter den Linden 1',
			'city'      => 'Berlin',
			'postcode'  => '10115',
			'country'   => 'DE',
		);
		$address = $this->get_toronto_address( array_merge( $berlin, array( 'state' => null ) ) );

		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertStringContainsString( '(ON)', wp_json_encode( $response->get_data() ) );

		// The Checkout block always sends the state, blank for Germany unless the shopper picks one.
		$address  = $this->get_toronto_address( array_merge( $berlin, array( 'state' => '' ) ) );
		$response = $this->dispatch_checkout_with_addresses( $address, $address );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( '', wc_get_order( $response->get_data()['order_id'] )->get_shipping_state() );
	}

	/**
	 * @testdox Checkout should still save an additional address field posted as "0".
	 */
	public function test_checkout_persists_additional_address_field_zero() {
		$field_id = 'plugin-namespace/unit-number';
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => $field_id,
				'label'    => 'Unit number',
				'location' => 'address',
				'type'     => 'text',
				'required' => false,
			)
		);
		$checkout_fields = Package::container()->get( CheckoutFields::class );

		try {
			$address = $this->get_toronto_address( array( $field_id => '0' ) );

			$response = $this->dispatch_checkout_with_addresses( $address, $address );

			$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
			$order = wc_get_order( $response->get_data()['order_id'] );
			$this->assertSame( '0', $checkout_fields->get_field_from_object( $field_id, $order, 'billing' ) );
		} finally {
			$checkout_fields->deregister_checkout_field( $field_id );
		}
	}

	/**
	 * @testdox Checkout should ship to the billing address when the store ships to the billing address only, whatever shipping address the request sends.
	 */
	public function test_shipping_address_is_ignored_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );
		$this->assertTrue( WC()->cart->needs_shipping(), 'Test cart is expected to need shipping.' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $this->get_fallback_billing_address(),
				'shipping_address' => (object) $this->get_fallback_shipping_address(),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertEquals( 'Billing', $order->get_shipping_first_name() );
		$this->assertEquals( '10 Billing Street', $order->get_shipping_address_1() );
		$this->assertEquals( 'Cambridge', $order->get_shipping_city() );
		$this->assertEquals( 'CB24 1AB', $order->get_shipping_postcode() );
	}

	/**
	 * @testdox Checkout should not keep session shipping values the request's billing address leaves out when the store ships to the billing address only.
	 */
	public function test_session_shipping_values_are_replaced_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		WC()->customer->set_billing_company( 'Billing Company' );
		WC()->customer->set_shipping_company( 'Session Company' );
		WC()->customer->set_shipping_address_2( 'Session Flat 2' );

		$billing_address = $this->get_fallback_billing_address();
		unset( $billing_address['company'], $billing_address['address_2'] );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $billing_address,
				'shipping_address' => (object) $billing_address,
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertEquals( 'Billing Company', $order->get_shipping_company() );
		$this->assertEquals( '', $order->get_shipping_address_2() );
		$this->assertEquals( '10 Billing Street', $order->get_shipping_address_1() );
	}

	/**
	 * @testdox Updating the customer should set the shipping address to the billing address when the store ships to the billing address only.
	 */
	public function test_cart_update_customer_uses_billing_address_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $this->get_fallback_billing_address(),
				'shipping_address' => (object) $this->get_fallback_shipping_address(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$response_shipping_address = (array) $response->get_data()['shipping_address'];
		$this->assertSame( '10 Billing Street', $response_shipping_address['address_1'] );
		$this->assertSame( 'Cambridge', $response_shipping_address['city'] );
		$this->assertSame( '10 Billing Street', WC()->customer->get_shipping_address_1() );
		$this->assertSame( 'Cambridge', WC()->customer->get_shipping_city() );
	}

	/**
	 * @testdox Updating the customer should keep a different shipping address when the store does not ship to the billing address only.
	 */
	public function test_cart_update_customer_keeps_shipping_address_by_default() {
		$this->assertFalse( wc_ship_to_billing_address_only() );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) $this->get_fallback_billing_address(),
				'shipping_address' => (object) $this->get_fallback_shipping_address(),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		$this->assertSame( '20 Shipping Street', WC()->customer->get_shipping_address_1() );
		$this->assertSame( 'Oxford', WC()->customer->get_shipping_city() );
	}

	/**
	 * Ensure that orders can be placed with virtual products.
	 */
	public function test_virtual_product_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		WC()->cart->add_to_cart( $this->products[2]->get_id(), 1 );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '4000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Ensure that orders cannot be placed with invalid payment methods.
	 */
	public function test_invalid_payment_method_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => 'apples',
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Ensure that orders cannot be placed with out of stock items.
	 */
	public function test_out_of_stock_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$product = wc_get_product( $this->products[0]->get_id() );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Ensure that orders cannot be placed with un-owned coupons.
	 */
	public function test_unowned_coupon_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		WC()->cart->apply_coupon( 'test' );

		$coupon = new \WC_Coupon( self::TEST_COUPON_CODE );

		// Apply email restriction after adding coupon to cart.
		$coupon->set_email_restrictions( 'jon@mail.com' );
		$coupon->save();

		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 409, $response->get_status() );
	}

	/**
	 * Ensure that orders cannot be placed with coupons over their usage limit.
	 */
	public function test_usage_limit_coupon_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$coupon = new \WC_Coupon();
		$coupon->set_code( 'test' );
		$coupon->set_amount( 2 );
		$coupon->save();

		WC()->cart->apply_coupon( 'test' );

		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '2800',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		WC()->cart->apply_coupon( 'test' );
		$coupon->set_usage_limit( 1 );
		$coupon->save();

		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '2800',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 409, $response->get_status() );
	}

	/**
	 * Ensure that orders can be placed with coupons.
	 */
	public function test_coupon_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		WC()->cart->apply_coupon( self::TEST_COUPON_CODE );

		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '2800',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, wc_get_order( $response->get_data()['order_id'] )->get_data()['discount_total'] );
	}

	/**
	 * Ensure that orders cannot be placed with invalid data.
	 */
	public function test_invalid_post_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		// Test with empty state.
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Test with invalid state.
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => 'GG',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => 'GG',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// Test with no state passed.
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => 'test',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Ensure that validation respects locale filtering.
	 */
	public function test_locale_required_filtering_post_data() {
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) {
				$locale['US']['state']['required'] = false;
				return $locale;
			}
		);

		unset( WC()->countries->locale );

		// Create shipping rates.
		$flat_rate             = WC()->shipping()->get_shipping_methods()['flat_rate'];
		$default_zone          = \WC_Shipping_Zones::get_zone( 0 );
		$flat_rate_instance_id = $default_zone->add_shipping_method( $flat_rate->id );
		$default_zone->save();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		// Test that a country that usually requires state can be overridden with woocommerce_get_country_locale filter.
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test lane',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '123456',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'US',
					'phone'      => '123456',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Ensure that labels respect locale filtering.
	 */
	public function test_locale_label_filtering_post_data() {
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) {
				$locale['FR']['state']['label']    = 'French state';
				$locale['FR']['state']['required'] = true;
				$locale['FR']['state']['hidden']   = false;
				$locale['DE']['state']['label']    = 'German state';
				$locale['DE']['state']['required'] = true;
				return $locale;
			}
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		// Test that a country that usually requires state can be overridden with woocommerce_get_country_locale filter.
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test lane',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'FR',
					'phone'      => '123456',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => '90210',
					'country'    => 'DE',
					'phone'      => '123456',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 'French state is required', $response->get_data()['data']['errors']['billing'][0] );
		$this->assertEquals( 'German state is required', $response->get_data()['data']['errors']['shipping'][0] );
	}

	/**
	 * Ensure that registered extension data is correctly shown on options requests.
	 */
	public function test_options_extension_data() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Test key',
				'type'        => 'boolean',
			),
			$data['schema']['properties']['extensions']['properties']['extension_namespace']['properties']['extension_key'],
			print_r( $data['schema']['properties']['extensions']['properties']['extension_namespace'], true )
		);
	}

	/**
	 * Ensure that registered extension data is correctly posted and visible on the server after sanitization.
	 */
	public function test_post_extension_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);
		$action_callback = \Mockery::mock( 'ActionCallback' );
		$action_callback->shouldReceive( 'do_callback' )->withArgs(
			array(
				\Mockery::any(),
				\Mockery::on(
					function ( $argument ) {
						return true === $argument['extensions']['extension_namespace']['extension_key'];
					}
				),
			)
		)->once();
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $action_callback, 'do_callback' ), 10, 2 );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Ensure that registered extension data is correctly posted and visible on the server after sanitization.
	 */
	public function test_post_invalid_extension_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => 'invalid-string',
					),
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Ensure that passing partial extension data should still pass fine.
	 */
	public function test_passing_partial_extension_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'other_extension_data' => array(
						'another_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Ensure that not passing any extensions data should still pass fine.
	 */
	public function test_not_passing_extensions_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}
	/**
	 * Check that accounts are created on request.
	 */
	public function test_checkout_create_account() {
		// Since we're making a "request", we need to save the session data to be available during the API request.
		WC()->session->set_customer_session_cookie( true );
		WC()->session->save_data();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'create_account'   => true,
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 200, $status, print_r( $data, true ) );
		$this->assertTrue( $data['customer_id'] > 0 );

		$customer = get_user_by( 'id', $data['customer_id'] );
		$this->assertEquals( 'testaccount@test.com', $customer->user_email );
	}

	/**
	 * Test account creation options.
	 */
	public function test_checkout_do_not_create_account() {
		// Since we're making a "request", we need to save the session data to be available during the API request.
		WC()->session->set_customer_session_cookie( true );
		WC()->session->save_data();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'create_account'   => false,
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 200, $status );
		$this->assertEquals( 0, $data['customer_id'] );
	}

	/**
	 * Test account creation options.
	 */
	public function test_checkout_force_create_account() {
		// Since we're making a "request", we need to save the session data to be available during the API request.
		WC()->session->set_customer_session_cookie( true );
		WC()->session->save_data();

		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 200, $status, print_r( $data, true ) );
		$this->assertTrue( $data['customer_id'] > 0 );

		$customer = get_user_by( 'id', $data['customer_id'] );
		$this->assertEquals( $customer->user_email, 'testaccount@test.com' );
	}

	/**
	 * Test account creation options.
	 */
	public function test_checkout_invalid_address_data() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => array(
						'invalid' => 'invalid_data',
					),
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 400, $status, print_r( $data, true ) );
	}

	/**
	 * Test checkout without valid shipping methods.
	 */
	public function test_checkout_invalid_shipping_method() {
		global $wpdb;
		$shipping_methods = \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods();
		foreach ( $shipping_methods as $shipping_method ) {
			$wpdb->update( "{$wpdb->prefix}woocommerce_shipping_zone_methods", array( 'is_enabled' => '0' ), array( 'instance_id' => absint( $shipping_method->instance_id ) ) );
		}
		$fixtures = new FixtureData();
		$fixtures->shipping_remove_pickup_location();

		// Create a simple product and add to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '4000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();
		$this->assertEquals( 400, $status, print_r( $data, true ) );
		$this->assertEquals( 'woocommerce_rest_invalid_shipping_option', $data['code'], print_r( $data, true ) );
		$this->assertEquals( 'Sorry, this order requires a shipping option.', $data['message'], print_r( $data, true ) );
	}

	/**
	 * A town of "0" is a value, so the cart must rate the address and the order must carry a
	 * shipping line. On trunk this same request stores an order with no shipping line at all.
	 * The guard is not exercised here, by design: once the address rates, it returns immediately.
	 *
	 * @testdox A shipping city of "0" is checked out with shipping charged.
	 */
	public function test_checkout_charges_shipping_for_a_city_of_zero() {
		$fixtures = new FixtureData();

		// Load bearing: setUp() enables pickup, and with it on, show_shipping() returns before it
		// ever looks at the address, so the cart would rate "0" on trunk too and this regression
		// test would quietly stop testing anything.
		$fixtures->shipping_remove_pickup_location();
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		// setUp() adds the rate instance but leaves it free, and a free rate cannot show the
		// difference between charging shipping and skipping it.
		$zone_methods = \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods();
		$flat_rate    = reset( $zone_methods );

		$flat_rate->instance_settings['cost'] = '25.00';
		update_option( $flat_rate->get_instance_option_key(), $flat_rate->instance_settings );
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		// Rate a complete address first, so a chosen method sits in the session the way the block
		// checkout leaves it before the shopper submits.
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_city( 'San Francisco' );
		wc()->customer->set_shipping_postcode( '94103' );
		wc()->cart->calculate_totals();

		$packages = wc()->shipping()->get_packages();
		$rates    = array_keys( $packages[0]['rates'] ?? array() );
		$this->assertNotEmpty( $rates, 'The store must offer a rate, or this test proves nothing.' );
		wc()->session->set( 'chosen_shipping_methods', array( $rates[0] ) );

		wc()->session->set_customer_session_cookie( true );
		wc()->session->save_data();

		$address = array(
			'first_name' => 'Gina',
			'last_name'  => 'Guest',
			'company'    => '',
			'address_1'  => '17 Private Lane',
			'address_2'  => '',
			'city'       => '0',
			'state'      => 'CA',
			'postcode'   => '94103',
			'country'    => 'US',
			'email'      => 'gina.guest@example.test',
			'phone'      => '',
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => $address,
				'shipping_address' => $address,
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 200, $response->get_status(), print_r( $data, true ) );

		$order = wc_get_order( $data['order_id'] );
		$this->assertCount( 1, $order->get_items( 'shipping' ), 'A town of "0" must still be charged shipping.' );
		$this->assertGreaterThan( 0, (float) $order->get_shipping_total(), 'The shipping total must not be zero.' );
	}

	/**
	 * Test deny guest checkout when registration during checkout is disabled,
	 * guest checkout is disabled and the user is not logged in.
	 */
	public function test_checkout_deny_guest_checkout() {
		// Since we're making a "request", we need to save the session data to be available during the API request.
		WC()->session->set_customer_session_cookie( true );
		WC()->session->save_data();

		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'guest',
					'last_name'  => 'guest',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'guest@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'guest',
					'last_name'  => 'guest',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
				'extensions'       => array(
					'extension_namespace' => array(
						'extension_key' => true,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 403, $status, print_r( $data, true ) );
		$this->assertEquals( 'woocommerce_rest_guest_checkout_disabled', $data['code'], print_r( $data, true ) );
		$this->assertEquals( 'You must be logged in to checkout.', $data['message'], print_r( $data, true ) );
	}

	/**
	 * Test updating an order via PUT request.
	 */
	public function test_put_order_update() {
		$fields = array(
			array(
				'id'                => 'plugin-namespace/student-id',
				'label'             => 'Student ID',
				'location'          => 'address',
				'type'              => 'text',
				'required'          => true,
				'attributes'        => array(
					'title'          => 'This is a student id',
					'autocomplete'   => 'student-id',
					'autocapitalize' => 'none',
					'maxLength'      => '30',
				),
				'sanitize_callback' => function ( $value ) {
					return trim( $value );
				},
				'validate_callback' => function ( $value ) {
					return strlen( $value ) > 3;
				},
			),
			array(
				'id'       => 'plugin-namespace/job-function',
				'label'    => 'What is your main role at your company?',
				'location' => 'contact',
				'required' => false,
				'type'     => 'text',
			),
			array(
				'id'       => 'plugin-namespace/leave-on-porch',
				'label'    => __( 'Please leave my package on the porch if I\'m not home', 'woocommerce' ),
				'location' => 'order',
				'type'     => 'checkbox',
			),
		);
		array_map( 'woocommerce_register_additional_checkout_field', $fields );

		// PATCH the checkout with the additional fields. Under deferred draft creation,
		// PATCH does not materialise an order — values are captured to the field store.
		$put_request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$put_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$put_request->set_body_params(
			array(
				'additional_fields' => array(
					'plugin-namespace/student-id'     => '1234567890',
					'plugin-namespace/job-function'   => 'engineering',
					'plugin-namespace/leave-on-porch' => true,
				),
				'payment_method'    => 'bacs',
				'order_notes'       => 'Please leave my package on the porch',
			)
		);

		$put_response = rest_get_server()->dispatch( $put_request );
		$this->assertEquals( 200, $put_response->get_status() );
		$this->assertSame( 0, $put_response->get_data()['order_id'] );

		// POST to materialise the real order. The replayed field store should round-trip
		// the contact and order additional fields onto the persisted order.
		$post_request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$post_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$post_request->set_body_params(
			array(
				'billing_address'   => array(
					'first_name'                  => 'Test',
					'last_name'                   => 'User',
					'company'                     => '',
					'address_1'                   => '123 Test St',
					'address_2'                   => '',
					'city'                        => 'Test City',
					'state'                       => 'CA',
					'postcode'                    => '94016',
					'country'                     => 'US',
					'email'                       => 'testaccount@test.com',
					'phone'                       => '5555555555',
					'plugin-namespace/student-id' => '1234567890',
				),
				'shipping_address'  => array(
					'first_name'                  => 'Test',
					'last_name'                   => 'User',
					'company'                     => '',
					'address_1'                   => '123 Test St',
					'address_2'                   => '',
					'city'                        => 'Test City',
					'state'                       => 'CA',
					'postcode'                    => '94016',
					'country'                     => 'US',
					'phone'                       => '5555555555',
					'plugin-namespace/student-id' => '1234567890',
				),
				'payment_method'    => 'bacs',
				'additional_fields' => array(
					'plugin-namespace/job-function'   => 'engineering',
					'plugin-namespace/leave-on-porch' => true,
				),
				'expected_total'    => '3000',
			)
		);

		$post_response = rest_get_server()->dispatch( $post_request );
		$this->assertEquals( 200, $post_response->get_status(), print_r( $post_response->get_data(), true ) );

		$order = wc_get_order( $post_response->get_data()['order_id'] );
		$this->assertInstanceOf( \WC_Order::class, $order );

		$checkout_fields           = Package::container()->get( CheckoutFields::class );
		$additional_fields_address = $checkout_fields->get_order_additional_fields_with_values( $order, 'address', 'other', 'view' );
		$additional_fields_contact = $checkout_fields->get_order_additional_fields_with_values( $order, 'contact', 'other', 'view' );
		$additional_fields_order   = $checkout_fields->get_order_additional_fields_with_values( $order, 'order', 'other', 'view' );

		// Verify that address fields are not updated, but contact and order fields are.
		$this->assertArrayNotHasKey( 'plugin-namespace/student-id', $additional_fields_address );
		$this->assertEquals( 'engineering', $additional_fields_contact['plugin-namespace/job-function']['value'] );
		$this->assertEquals( true, $additional_fields_order['plugin-namespace/leave-on-porch']['value'] );

		// Deregister the fields we registered above so later tests start from a clean
		// global state. Without this, subsequent tests would fail address validation
		// because `student-id` is required on every address submission.
		foreach ( $fields as $field ) {
			$checkout_fields->deregister_checkout_field( $field['id'] );
		}
	}

	/**
	 * Test updating an order with invalid payment method.
	 */
	public function test_put_order_invalid_payment_method() {
		// Now test updating with invalid payment method .
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'payment_method' => 'invalid_method',
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}


	/**
	 * Test updating an order with invalid order notes.
	 */
	public function test_put_order_invalid_order_notes() {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'order_notes' => array( 'invalid' => 'notes format' ), // Order notes should be <string>.
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @testDox Test that perform_custom_order_validation throws a RouteException with a custom error.
	 */
	public function test_perform_custom_order_validation() {
		$order_controller = new \Automattic\WooCommerce\StoreApi\Utilities\OrderController();
		$order            = new \WC_Order();

		// Set up a test action to add a custom validation error.
		add_action(
			'woocommerce_checkout_validate_order_before_payment',
			function ( $order, $errors ) {
				$errors->add( 'custom_error', 'This is a custom validation error' );
			},
			10,
			2
		);

		// Use reflection to make the protected method accessible.
		$reflection = new \ReflectionClass( $order_controller );
		$method     = $reflection->getMethod( 'perform_custom_order_validation' );
		$method->setAccessible( true );

		// Assert that the method throws a RouteException with our custom error.
		$this->expectException( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException::class );
		$this->expectExceptionMessage( 'This is a custom validation error' );

		$method->invoke( $order_controller, $order );
	}

	/**
	 * @testdox Checkout charges local pickup tax only when local pickup is taxable ($tax_status).
	 *
	 * @testWith ["none", 0.0, 38.0]
	 *           ["taxable", 0.5, 38.5]
	 * @param string $tax_status            Local pickup tax status.
	 * @param float  $expected_shipping_tax Expected tax on the 5.00 pickup cost at 10%.
	 * @param float  $expected_total        Expected order total: 30.00 of items, 3.00 item tax, 5.00 pickup, and the pickup tax.
	 */
	public function test_checkout_local_pickup_tax_status( string $tax_status, float $expected_shipping_tax, float $expected_total ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'CA Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
			)
		);

		$pickup_settings               = get_option( 'woocommerce_pickup_location_settings' );
		$pickup_settings['cost']       = '5';
		$pickup_settings['tax_status'] = $tax_status;
		update_option( 'woocommerce_pickup_location_settings', $pickup_settings );
		WC()->shipping()->unregister_shipping_methods();
		\WC_Cache_Helper::get_transient_version( 'shipping', true );

		WC()->session->set( 'chosen_shipping_methods', array( 'pickup_location:0' ) );

		$address = array(
			'first_name' => 'test',
			'last_name'  => 'test',
			'address_1'  => 'test',
			'city'       => 'test',
			'state'      => 'CA',
			'postcode'   => '12345',
			'country'    => 'US',
		);

		// The quote the shopper sees at checkout, before the order is placed.
		WC()->customer->set_billing_location( 'US', 'CA', '12345', 'test' );
		WC()->customer->set_shipping_location( 'US', 'CA', '12345', 'test' );
		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( $expected_shipping_tax, (float) WC()->cart->get_shipping_tax(), 0.001, 'The checkout quote should tax pickup only when it is taxable.' );
		$this->assertEqualsWithDelta( $expected_total, (float) WC()->cart->get_total( 'edit' ), 0.001 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array_merge( $address, array( 'email' => 'test@test.com' ) ),
				'shipping_address' => (object) $address,
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		$order          = wc_get_order( $response->get_data()['order_id'] );
		$shipping_lines = $order->get_items( 'shipping' );
		$this->assertCount( 1, $shipping_lines );

		$shipping_line = reset( $shipping_lines );
		$this->assertSame( 'pickup_location', $shipping_line->get_method_id() );
		$this->assertEqualsWithDelta( 5.0, (float) $shipping_line->get_total(), 0.001 );
		$this->assertEqualsWithDelta( $expected_shipping_tax, (float) $shipping_line->get_total_tax(), 0.001, 'The saved pickup line should carry the tax checkout quoted.' );
		$this->assertEqualsWithDelta( $expected_shipping_tax, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 3.0, (float) $order->get_cart_tax(), 0.001 );
		$this->assertEqualsWithDelta( $expected_total, (float) $order->get_total(), 0.001, 'The order total should match the quote, with pickup tax only when pickup is taxable.' );
	}

	/**
	 * Test that local pickup orders bypass shipping country validation.
	 */
	public function test_local_pickup_country_validation() {
		// Set shipping to a country that's not enabled for shipping.
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'GB' ) );

		// Set chosen shipping method to pickup location.
		WC()->session->set( 'chosen_shipping_methods', array( 'pickup_location:0' ) );
		WC()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'US',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '4000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * Test that local pickup orders still validate billing country.
	 */
	public function test_local_pickup_invalid_billing_country() {
		// Set allowed countries to just US.
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );

		// Set chosen shipping method.
		WC()->session->set( 'chosen_shipping_methods', array( 'pickup_location:0' ) );
		WC()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '4000',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_address_country', $response->get_data()['code'] );
		$this->assertStringContainsString( 'Sorry, we do not allow orders from the provided country (France)', $response->get_data()['message'] );
	}

	/**
	 * @testdox Pay for Order should store and return address fields as plain text without HTML encoding.
	 */
	public function test_checkout_order_address_values_round_trip_without_html_encoding(): void {
		$order                       = \WC_Helper_Order::create_order( 0 );
		$billing_address             = array(
			'first_name' => 'Test',
			'last_name'  => 'User',
			'company'    => 'AT&T',
			'address_1'  => '123 Test St',
			'address_2'  => '',
			'city'       => 'WooCity',
			'state'      => 'NY',
			'postcode'   => '12345',
			'country'    => 'US',
			'email'      => $order->get_billing_email(),
			'phone'      => '555-32123',
		);
		$shipping_address            = $billing_address;
		$shipping_address['company'] = "St John's";
		unset( $shipping_address['email'] );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout/' . $order->get_id() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_query_params(
			array(
				'key'           => $order->get_order_key(),
				'billing_email' => $order->get_billing_email(),
			)
		);
		$request->set_body_params(
			array(
				'billing_address'  => $billing_address,
				'shipping_address' => $shipping_address,
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status(), print_r( $data, true ) );

		$response_billing_address  = (array) $data['billing_address'];
		$response_shipping_address = (array) $data['shipping_address'];
		$this->assertSame( 'AT&T', $response_billing_address['company'] );
		$this->assertSame( "St John's", $response_shipping_address['company'] );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertSame( 'AT&T', $stored_order->get_billing_company( 'edit' ) );
		$this->assertSame( "St John's", $stored_order->get_shipping_company( 'edit' ) );
	}

	/**
	 * @testdox Existing order payment should not persist address data when country validation fails.
	 */
	public function test_checkout_order_does_not_persist_invalid_country_address() {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );

		$order = \WC_Helper_Order::create_order( 0 );

		$original_billing_country  = $order->get_billing_country();
		$original_shipping_country = $order->get_shipping_country();
		$original_total            = $order->get_total();

		// Forbidden country (IN) on both addresses is what should trigger the rejection.
		$invalid_address = array(
			'first_name' => 'Test',
			'last_name'  => 'Kumar',
			'company'    => '',
			'address_1'  => '1 MG Road',
			'address_2'  => '',
			'city'       => 'Mumbai',
			'state'      => 'MH',
			'postcode'   => '400001',
			'country'    => 'IN',
			'phone'      => '',
		);

		$response = $this->dispatch_pay_for_order_request( $order, $invalid_address, $invalid_address );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_address_country', $response->get_data()['code'] );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( $original_billing_country, $stored_order->get_billing_country() );
		$this->assertEquals( $original_shipping_country, $stored_order->get_shipping_country() );
		$this->assertEquals( $original_total, $stored_order->get_total() );
	}

	/**
	 * @testdox Existing order payment should check the shipping country against the order's shipping line, with an empty cart.
	 *
	 * @dataProvider provide_pay_for_order_shipping_methods
	 *
	 * @param string $method_id       Method id of the order's shipping line.
	 * @param bool   $expect_rejected Whether the shipping country should be rejected.
	 */
	public function test_checkout_order_rejects_shipping_country_the_store_does_not_ship_to( string $method_id, bool $expect_rejected ) {
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );
		wc_empty_cart();

		// A merchant-created order with no address yet, so the shopper fills in the first one.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_address( array_fill_keys( array( 'city', 'state', 'postcode', 'country' ), '' ) );
		foreach ( $order->get_shipping_methods() as $shipping_line ) {
			$shipping_line->set_method_id( $method_id );
			$shipping_line->save();
		}
		$order->save();

		$response = $this->dispatch_pay_for_order_request(
			$order,
			array(
				'city'     => 'WooCity',
				'state'    => 'NY',
				'postcode' => '12345',
				'country'  => 'US',
			),
			array(
				'address_1' => 'Hauptstr. 1',
				'city'      => 'Berlin',
				'state'     => '',
				'postcode'  => '10115',
				'country'   => 'DE',
			)
		);

		$stored_order = wc_get_order( $order->get_id() );
		if ( $expect_rejected ) {
			$this->assertEquals( 400, $response->get_status() );
			$this->assertEquals( 'woocommerce_rest_invalid_address_country', $response->get_data()['code'] );
			$this->assertEquals( OrderStatus::PENDING, $stored_order->get_status() );
			$this->assertEquals( '', $stored_order->get_shipping_country() );
		} else {
			$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
			$this->assertEquals( 'DE', $stored_order->get_shipping_country() );
		}
	}

	/**
	 * Shipping methods on a pay-for-order order and whether a country the store does not ship to should be rejected.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public function provide_pay_for_order_shipping_methods(): array {
		return array(
			'flat rate'       => array( 'flat_rate', true ),
			'pickup location' => array( 'pickup_location', false ),
		);
	}

	/**
	 * Returns a destination in a different shipping zone and tax location to the fixture's US address.
	 *
	 * @return array
	 */
	private function get_different_destination() {
		return array(
			'city'     => 'London',
			'state'    => '',
			'postcode' => 'SW1A 1AA',
			'country'  => 'GB',
		);
	}

	/**
	 * Dispatches a pay-for-order request as the guest the order belongs to.
	 *
	 * Both addresses default to the one the order already carries and the payment method to bank transfer,
	 * which the guard accepts, so a test states only what it changes.
	 *
	 * @param \WC_Order  $order                  Order being paid for.
	 * @param array      $billing                Billing fields to override.
	 * @param array|null $shipping               Shipping fields to override, or null to leave the shipping address out.
	 * @param bool       $merge_existing_billing Whether to merge the stored billing address into the request.
	 * @param string     $payment_method        Payment method to pay with, bank transfer by default.
	 * @return \WP_REST_Response
	 */
	private function dispatch_pay_for_order_request(
		\WC_Order $order,
		array $billing = array(),
		?array $shipping = array(),
		bool $merge_existing_billing = true,
		string $payment_method = WC_Gateway_BACS::ID
	) {
		$address = $order->get_address( 'billing' );
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout/' . $order->get_id() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_query_params(
			array(
				'key'           => $order->get_order_key(),
				'billing_email' => $order->get_billing_email(),
			)
		);
		$body = array(
			'billing_address' => $merge_existing_billing ? array_merge( $address, $billing ) : $billing,
			'payment_method'  => $payment_method,
		);
		if ( null !== $shipping ) {
			$body['shipping_address'] = array_merge( $address, $shipping );
		}
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Creates a guest order that already carries a shipping address, as one placed through checkout would.
	 *
	 * @return \WC_Order
	 */
	private function create_pay_for_order_with_shipping_address() {
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_shipping_address( $order->get_address( 'billing' ) );
		$order->save();

		return $order;
	}

	/**
	 * Configures the store to collect tax based on the given address group.
	 *
	 * @param string $group Address group tax is based on: 'billing' or 'shipping'.
	 */
	private function set_taxes_based_on( string $group ) {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_based_on', $group );
	}

	/**
	 * Makes shipping unavailable through the global setting or by removing every method.
	 *
	 * @param bool $disable_shipping Whether to disable shipping globally.
	 * @return \Closure|null Shipping method count filter to remove, or null when shipping is disabled.
	 */
	private function make_shipping_unavailable( bool $disable_shipping ): ?\Closure {
		if ( $disable_shipping ) {
			update_option( 'woocommerce_ship_to_countries', 'disabled' );
			return null;
		}

		$no_methods = static function () {
			return array(
				'version'  => \WC_Cache_Helper::get_transient_version( 'shipping' ),
				'legacy'   => 0,
				'enabled'  => 0,
				'disabled' => 0,
			);
		};
		add_filter( 'pre_transient_wc_shipping_method_count', $no_methods );

		return $no_methods;
	}

	/**
	 * @testdox Existing order payment refuses a checkout-draft order, which has not passed checkout validation, and leaves it a draft.
	 */
	public function test_checkout_order_rejects_checkout_draft_order(): void {
		$order = $this->create_pay_for_order_with_shipping_address();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$this->assertTrue( $order->needs_payment(), 'Baseline: needs_payment() is still true for a draft, so the route has to refuse it explicitly.' );

		$response = $this->dispatch_pay_for_order_request( $order );

		$this->assertTrue( $response->is_error(), 'A draft must not be accepted for payment: ' . print_r( $response->get_data(), true ) );
		$this->assertSame( 'invalid_order_update_status', $response->get_data()['code'], 'A draft must be refused as an order that cannot be paid for.' );
		$this->assertSame( 400, $response->get_status(), 'A refused draft is a client error, not a server error.' );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CHECKOUT_DRAFT ), 'A refused draft must stay a draft.' );
	}

	/**
	 * @testdox Existing order payment refuses an order that no longer needs payment with a 400.
	 */
	public function test_checkout_order_rejects_order_not_needing_payment_with_400(): void {
		$order = $this->create_pay_for_order_with_shipping_address();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order );

		$this->assertSame( 'invalid_order_update_status', $response->get_data()['code'], 'A paid order must be refused as an order that cannot be paid for.' );
		$this->assertSame( 400, $response->get_status(), 'A refused order is a client error, not a server error.' );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::PROCESSING ), 'A refused order must keep its status.' );
	}

	/**
	 * @testdox Existing order payment should reject a shipping address that moves the order to a different destination.
	 */
	public function test_checkout_order_rejects_shipping_location_change() {
		$this->set_taxes_based_on( 'shipping' );

		$order          = $this->create_pay_for_order_with_shipping_address();
		$original_total = $order->get_total();

		$response = $this->dispatch_pay_for_order_request( $order, array(), $this->get_different_destination() );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		// The message is what distinguishes the shipping zone check from the tax check below it.
		$this->assertStringContainsString( 'the shipping cost was calculated', $response->get_data()['message'] );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country(), 'A rejected request must not persist the address' );
		$this->assertEquals( $original_total, $stored_order->get_total(), 'A rejected request must not change the total' );
	}

	/**
	 * @testdox Existing order payment should allow address changes that leave the destination alone.
	 */
	public function test_checkout_order_allows_address_change_that_keeps_the_location() {
		$this->set_taxes_based_on( 'shipping' );

		$order = $this->create_pay_for_order_with_shipping_address();

		$response = $this->dispatch_pay_for_order_request(
			$order,
			array(
				'first_name' => 'Renamed',
				'address_1'  => '9 Updated Street',
				'phone'      => '555-99999',
			),
			array( 'first_name' => 'Renamed' )
		);

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'Renamed', $stored_order->get_billing_first_name() );
		$this->assertEquals( '9 Updated Street', $stored_order->get_billing_address_1() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should allow a first address on an order that holds none at all.
	 */
	public function test_checkout_order_allows_first_address_when_order_holds_none() {
		$this->set_taxes_based_on( 'shipping' );

		// A merchant-created order awaiting the shopper's details has no shipping cost or tax tied to a location.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_address( array_fill_keys( array( 'city', 'state', 'postcode', 'country' ), '' ) );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination(), $this->get_different_destination() );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'GB', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should reject a state of "0" as the first address of an order that holds none.
	 */
	public function test_checkout_order_rejects_state_zero_on_order_without_address() {
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_address( array_fill_keys( array( 'city', 'state', 'postcode', 'country' ), '' ) );
		$order->save();

		$address = array(
			'city'     => 'Toronto',
			'state'    => '0',
			'postcode' => 'M5H 2N2',
			'country'  => 'CA',
		);

		$response = $this->dispatch_pay_for_order_request( $order, $address, $address );

		$this->assertEquals( 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( '', wc_get_order( $order->get_id() )->get_billing_state() );
	}

	/**
	 * @testdox Existing order payment should tax an order that ships nothing and holds no address at its billing address.
	 */
	public function test_checkout_order_taxes_virtual_order_without_address_at_billing() {
		$this->set_taxes_based_on( 'shipping' );
		$this->enable_taxes_with_us_ca_rate();

		// A shipping extension may decide from the submitted address, so the hook must see billing after it is set.
		add_filter(
			'woocommerce_order_needs_shipping',
			static function ( $needs_shipping, $filtered_order ) {
				return 'US' !== $filtered_order->get_billing_country() || $needs_shipping;
			},
			10,
			2
		);

		// A merchant-created order for a virtual product, awaiting the shopper's details.
		$virtual_product = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order           = \WC_Helper_Order::create_order( 0, $virtual_product );
		$order->set_billing_address(
			array(
				'city'     => '',
				'state'    => 'CA',
				'postcode' => '94103',
				'country'  => '',
			)
		);
		$order->save();

		add_filter(
			'woocommerce_get_order_address',
			static function ( $address, $address_type ) {
				if ( 'billing' === $address_type ) {
					$address['postcode'] = 'FILTERED';
				}
				return $address;
			},
			10,
			2
		);

		$billing = array(
			'city'    => 'San Francisco',
			'country' => 'US',
			'email'   => $order->get_billing_email(),
		);

		// The shipping address of an order that ships nothing is not validated, so it must not set the tax.
		$response = $this->dispatch_pay_for_order_request( $order, $billing, $this->get_different_destination(), false );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
		$this->assertEquals( 'CA', $stored_order->get_shipping_state() );
		$this->assertEquals( '94103', $stored_order->get_shipping_postcode() );
		$this->assertEquals( 4.0, (float) $stored_order->get_total_tax() );
	}

	/**
	 * @testdox Existing order address updates should re-check shipping needs after copying billing to shipping.
	 */
	public function test_existing_order_address_update_rechecks_shipping_needs_after_copying_billing() {
		$virtual_product = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order           = \WC_Helper_Order::create_order( 0, $virtual_product );
		$billing         = $order->get_address( 'billing' );
		$shipping        = array_merge( $billing, $this->get_different_destination() );
		$order->set_billing_country( '' );
		$order->save();

		$shipping_countries = array();
		add_filter(
			'woocommerce_order_needs_shipping',
			static function ( $needs_shipping, $filtered_order ) use ( &$shipping_countries ) {
				$shipping_countries[] = $filtered_order->get_shipping_country();
				return 'US' === $filtered_order->get_shipping_country() || $needs_shipping;
			},
			10,
			2
		);

		( new OrderController() )->update_existing_order_addresses( $order, $billing, $shipping );

		$this->assertSame( array( 'GB', 'US' ), $shipping_countries );
	}

	/**
	 * @testdox Existing order payment should keep the shipping address of a physical order with a shipping line when shipping is unavailable.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $disable_shipping Whether to disable shipping globally instead of removing every method.
	 */
	public function test_checkout_order_keeps_physical_order_shipping_with_shipping_line_when_shipping_is_unavailable( bool $disable_shipping ) {
		$no_methods = $this->make_shipping_unavailable( $disable_shipping );

		// A merchant-created order for a physical product with a manual shipping line and no address yet.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_country( '' );
		$order->save();
		$this->assertFalse( $order->needs_shipping() );

		$response = $this->dispatch_pay_for_order_request( $order, array( 'country' => 'US' ), $this->get_different_destination() );
		if ( null !== $no_methods ) {
			remove_filter( 'pre_transient_wc_shipping_method_count', $no_methods );
		}

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'GB', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should tax a physical order without a shipping line at billing when shipping is unavailable.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $disable_shipping Whether to disable shipping globally instead of removing every method.
	 */
	public function test_checkout_order_taxes_physical_order_without_shipping_line_at_billing_when_shipping_is_unavailable( bool $disable_shipping ) {
		$this->set_taxes_based_on( 'shipping' );
		$this->enable_taxes_with_us_ca_rate();
		$no_methods = $this->make_shipping_unavailable( $disable_shipping );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->remove_order_items( 'shipping' );
		$order->set_shipping_total( 0 );
		$order->set_billing_country( '' );
		$order->save();
		$this->assertFalse( $order->needs_shipping() );
		$this->assertCount( 0, $order->get_shipping_methods() );

		$response = $this->dispatch_pay_for_order_request(
			$order,
			array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '94103',
			),
			$this->get_different_destination()
		);
		if ( null !== $no_methods ) {
			remove_filter( 'pre_transient_wc_shipping_method_count', $no_methods );
		}

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
		$this->assertEquals( 'CA', $stored_order->get_shipping_state() );
		$this->assertEquals( '94103', $stored_order->get_shipping_postcode() );
		$this->assertEquals( 4.0, (float) $stored_order->get_total_tax() );
	}

	/**
	 * @testdox Existing order payment should tax a virtual order with a shipping line at billing when shipping is unavailable.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $disable_shipping Whether to disable shipping globally instead of removing every method.
	 */
	public function test_checkout_order_taxes_virtual_order_with_shipping_line_at_billing_when_shipping_is_unavailable( bool $disable_shipping ) {
		$this->set_taxes_based_on( 'shipping' );
		$this->enable_taxes_with_us_ca_rate();
		$no_methods = $this->make_shipping_unavailable( $disable_shipping );

		$virtual_product = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order           = \WC_Helper_Order::create_order( 0, $virtual_product );
		$order->set_billing_country( '' );
		$order->save();
		$this->assertFalse( $order->needs_shipping() );
		$this->assertCount( 1, $order->get_shipping_methods() );

		$response = $this->dispatch_pay_for_order_request(
			$order,
			array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '94103',
			),
			$this->get_different_destination()
		);
		if ( null !== $no_methods ) {
			remove_filter( 'pre_transient_wc_shipping_method_count', $no_methods );
		}

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
		$this->assertEquals( 'CA', $stored_order->get_shipping_state() );
		$this->assertEquals( '94103', $stored_order->get_shipping_postcode() );
		$this->assertEquals( 4.0, (float) $stored_order->get_total_tax() );
	}

	/**
	 * @testdox Existing order payment should keep the shipping address of a mixed order when shipping is unavailable.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $disable_shipping Whether to disable shipping globally instead of removing every method.
	 */
	public function test_checkout_order_keeps_mixed_order_shipping_when_shipping_is_unavailable( bool $disable_shipping ) {
		$no_methods = $this->make_shipping_unavailable( $disable_shipping );

		$virtual_product  = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$physical_product = \WC_Helper_Product::create_simple_product();
		$order            = \WC_Helper_Order::create_order( 0, $virtual_product );
		$order->add_product( $physical_product );
		$order->set_billing_country( '' );
		$order->calculate_totals( false );

		$items = array_values( $order->get_items() );
		$this->assertCount( 2, $items );
		$this->assertFalse( $items[0]->get_product()->needs_shipping() );
		$this->assertTrue( $items[1]->get_product()->needs_shipping() );
		$this->assertFalse( $order->needs_shipping() );
		$this->assertCount( 1, $order->get_shipping_methods() );

		$response = $this->dispatch_pay_for_order_request( $order, array( 'country' => 'US' ), $this->get_different_destination() );
		if ( null !== $no_methods ) {
			remove_filter( 'pre_transient_wc_shipping_method_count', $no_methods );
		}

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'GB', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should ship a filtered physical order to billing when an enabled or disabled shipping method exists.
	 * @testWith [1, 0]
	 *           [0, 1]
	 *
	 * @param int $enabled_methods  Number of enabled shipping methods.
	 * @param int $disabled_methods Number of disabled shipping methods.
	 */
	public function test_checkout_order_ships_filtered_physical_order_to_billing_with_shipping_methods( int $enabled_methods, int $disabled_methods ) {
		$method_counts = static function () use ( $enabled_methods, $disabled_methods ) {
			return array(
				'version'  => \WC_Cache_Helper::get_transient_version( 'shipping' ),
				'legacy'   => 0,
				'enabled'  => $enabled_methods,
				'disabled' => $disabled_methods,
			);
		};
		add_filter( 'pre_transient_wc_shipping_method_count', $method_counts );

		$this->assertSame( 1, wc_get_shipping_method_count( true ) );
		$this->assertSame( $enabled_methods, wc_get_shipping_method_count( false, true ) );
		add_filter( 'woocommerce_order_needs_shipping', '__return_false' );

		// A merchant-created order for a physical product that an extension says ships nothing, with no address yet.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_country( '' );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order, array( 'country' => 'US' ), $this->get_different_destination() );
		remove_filter( 'woocommerce_order_needs_shipping', '__return_false' );
		remove_filter( 'pre_transient_wc_shipping_method_count', $method_counts );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'US', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should reject a first shipping address away from the billing address the order ships to.
	 */
	public function test_checkout_order_rejects_first_shipping_address_away_from_billing() {
		$this->set_taxes_based_on( 'shipping' );

		// With no shipping country the order ships to its billing address, e.g. one created in the admin.
		$order = \WC_Helper_Order::create_order( 0 );
		$this->assertEquals( '', $order->get_shipping_country() );

		$response = $this->dispatch_pay_for_order_request( $order, array(), $this->get_different_destination() );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		$this->assertStringContainsString( 'the shipping cost was calculated', $response->get_data()['message'] );
		$this->assertEquals( '', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should reject a first shipping address that moves the tax location.
	 */
	public function test_checkout_order_rejects_first_shipping_address_that_moves_the_tax_location() {
		$this->set_taxes_based_on( 'shipping' );

		// With no shipping country the order is taxed on billing, so a shipping address relocates the tax.
		$virtual_product = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order           = \WC_Helper_Order::create_order( 0, $virtual_product );

		$response = $this->dispatch_pay_for_order_request( $order, array(), $this->get_different_destination() );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		// A virtual order skips the shipping zone check, so only the tax check can reject this.
		$this->assertStringContainsString( 'the tax was calculated', $response->get_data()['message'] );
		$this->assertEquals( '', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should keep the order's shipping address when the request leaves it out.
	 */
	public function test_checkout_order_keeps_shipping_address_when_request_omits_it() {
		$this->set_taxes_based_on( 'shipping' );

		$order = $this->create_pay_for_order_with_shipping_address();

		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination(), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'GB', $stored_order->get_billing_country() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country(), 'A billing-only request must not re-address the order' );
	}

	/**
	 * @testdox Existing order payment should ship to billing when neither the order nor the request holds a shipping address.
	 */
	public function test_checkout_order_copies_billing_to_shipping_when_neither_holds_one() {
		$this->set_taxes_based_on( 'shipping' );

		// An order made in the admin often has no shipping address, and a client may send only billing.
		$order = \WC_Helper_Order::create_order( 0 );
		$this->assertEquals( '', $order->get_shipping_country() );

		$response = $this->dispatch_pay_for_order_request( $order, array( 'address_1' => '1 New Street' ), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
		$this->assertEquals( '1 New Street', $stored_order->get_shipping_address_1() );
	}

	/**
	 * @testdox Existing order payment should reject a billing change when the store ships to the billing address only.
	 */
	public function test_checkout_order_rejects_billing_change_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		$order = $this->create_pay_for_order_with_shipping_address();

		// Shipping follows billing on this store, so a new billing address is a new destination.
		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination(), null );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		$this->assertStringContainsString( 'the shipping cost was calculated', $response->get_data()['message'] );
		$this->assertEquals( 'US', wc_get_order( $order->get_id() )->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should ship to billing when the store ships to the billing address only, whatever shipping address the request sends.
	 */
	public function test_checkout_order_ignores_shipping_address_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		$order = $this->create_pay_for_order_with_shipping_address();

		// A street change does not change the price, so the request passes and shows where shipping was copied from.
		$response = $this->dispatch_pay_for_order_request( $order, array( 'address_1' => '1 New Street' ), $this->get_different_destination() );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( '1 New Street', $order->get_shipping_address_1() );
		$this->assertEquals( 'US', $order->get_shipping_country() );
	}

	/**
	 * @testdox Existing order payment should keep an order's own shipping address when the store ships to the billing address only.
	 */
	public function test_checkout_order_keeps_distinct_shipping_address_when_store_ships_to_billing_only() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		// An order made in the admin can ship somewhere other than its billing address, even on this store.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_shipping_address( array_merge( $order->get_address( 'billing' ), $this->get_different_destination() ) );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order, array( 'address_1' => '1 New Street' ) );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( '1 New Street', $stored_order->get_billing_address_1() );
		$this->assertEquals( 'GB', $stored_order->get_shipping_country(), 'Billing must not be copied over the address the order already ships to' );
	}

	/**
	 * @testdox Existing order payment should reject a shipping city change even when tax is billing based.
	 */
	public function test_checkout_order_rejects_shipping_city_change() {
		$this->set_taxes_based_on( 'billing' );

		$order = $this->create_pay_for_order_with_shipping_address();

		// Tax follows billing here, so only the shipping zone check can reject a shipping city change.
		$response = $this->dispatch_pay_for_order_request( $order, array(), array( 'city' => 'Elsewhere' ) );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		$this->assertStringContainsString( 'the shipping cost was calculated', $response->get_data()['message'] );
	}

	/**
	 * @testdox Existing order payment should allow a billing change on a local pickup order taxed at the shop base.
	 */
	public function test_checkout_order_allows_billing_change_on_local_pickup_order() {
		$this->set_taxes_based_on( 'billing' );

		// Local pickup pins the tax location to the shop base, so billing cannot move what is charged.
		$order         = $this->create_pay_for_order_with_shipping_address();
		$pickup_method = new \WC_Order_Item_Shipping();
		$pickup_method->set_props(
			array(
				'method_title' => 'Local pickup',
				'method_id'    => 'local_pickup',
				'total'        => 0,
			)
		);
		$order->add_item( $pickup_method );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination() );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEquals( 'GB', wc_get_order( $order->get_id() )->get_billing_country() );
	}

	/**
	 * @testdox Existing order payment should allow an address change when the store does not collect tax.
	 */
	public function test_checkout_order_allows_address_change_when_taxes_are_disabled() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// A digital order has no shipping cost either, so the address cannot change the total.
		$virtual_product = \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order           = \WC_Helper_Order::create_order( 0, $virtual_product );
		$order->set_shipping_address( $order->get_address( 'billing' ) );
		$order->save();
		$original_total = $order->get_total();

		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination(), $this->get_different_destination() );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'GB', $stored_order->get_shipping_country() );
		$this->assertEquals( $original_total, $stored_order->get_total(), 'Without tax the address cannot change the total' );
	}

	/**
	 * @testdox Existing order payment should allow location fields that differ from the order's only in case.
	 */
	public function test_checkout_order_allows_location_fields_that_differ_only_in_case() {
		$this->set_taxes_based_on( 'billing' );

		$order = $this->create_pay_for_order_with_shipping_address();

		$response = $this->dispatch_pay_for_order_request(
			$order,
			// The zone and tax lookups uppercase the city and state, so both of these match the order's location.
			array(
				'city'  => 'woocity',
				'state' => 'ny',
			)
		);

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
	}

	/**
	 * @testdox Existing order payment should keep a state name the order holds when the request sends its code.
	 */
	public function test_checkout_order_keeps_state_name_the_order_holds() {
		$this->set_taxes_based_on( 'billing' );
		$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'NY',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'NY Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
			)
		);

		// Orders created outside the Store API (REST API, import) can hold the state name, which matches no tax
		// rate. The Store API always sends the code, which does, so saving it would change the total.
		$order = $this->create_pay_for_order_with_shipping_address();
		$order->set_billing_state( 'New York' );
		$order->calculate_totals();
		$order->save();
		$original_total = $order->get_total();

		$response = $this->dispatch_pay_for_order_request( $order, array( 'state' => 'NY' ) );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'New York', $stored_order->get_billing_state() );
		$this->assertEquals( $original_total, $stored_order->get_total(), 'An unchanged address must not change the total' );
	}

	/**
	 * @testdox Existing order payment should keep a state name on the shipping address it copies from billing.
	 */
	public function test_checkout_order_keeps_state_name_when_shipping_is_copied_from_billing() {
		$this->set_taxes_based_on( 'shipping' );
		$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'NY',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'NY Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
			)
		);

		// With no shipping address the order is taxed on billing, which holds the state name.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_state( 'New York' );
		$order->calculate_totals();
		$order->save();
		$original_total = $order->get_total();

		$response = $this->dispatch_pay_for_order_request( $order, array( 'state' => 'NY' ), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'New York', $stored_order->get_shipping_state() );
		$this->assertEquals( $original_total, $stored_order->get_total(), 'An unchanged location must not change the total' );
	}

	/**
	 * @testdox Existing order payment should reject a state that differs from the order's only in surrounding whitespace.
	 */
	public function test_checkout_order_rejects_state_that_differs_only_in_whitespace() {
		$this->set_taxes_based_on( 'billing' );
		$this->inserted_tax_rate_ids[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'NY',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'NY Sales Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
			)
		);

		// Tax rates match the stored state as it is, so the stray space kept this order away from the NY rate.
		$order = $this->create_pay_for_order_with_shipping_address();
		$order->set_billing_state( ' NY' );
		$order->calculate_totals();
		$order->save();
		$original_total = $order->get_total();

		$response = $this->dispatch_pay_for_order_request( $order, array( 'state' => 'NY' ) );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		$this->assertStringContainsString( 'the tax was calculated', $response->get_data()['message'] );
		$this->assertEquals( $original_total, wc_get_order( $order->get_id() )->get_total(), 'A rejected request must not change the total' );
	}

	/**
	 * @testdox Existing order payment should reject a billing address change when tax is based on billing.
	 */
	public function test_checkout_order_rejects_billing_address_change_when_tax_is_billing_based() {
		$this->set_taxes_based_on( 'billing' );

		$order = $this->create_pay_for_order_with_shipping_address();

		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination() );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_order_address_change_not_allowed', $response->get_data()['code'] );
		// Shipping is untouched, so only the tax check can reject this.
		$this->assertStringContainsString( 'the tax was calculated', $response->get_data()['message'] );
		$this->assertEquals( 'US', wc_get_order( $order->get_id() )->get_billing_country() );
	}

	/**
	 * @testdox Existing order payment should allow a billing address change when tax is based on shipping.
	 */
	public function test_checkout_order_allows_billing_address_change_when_tax_is_shipping_based() {
		$this->set_taxes_based_on( 'shipping' );

		$order = $this->create_pay_for_order_with_shipping_address();

		// Tax follows shipping here, so a new billing country cannot change the total.
		$response = $this->dispatch_pay_for_order_request( $order, $this->get_different_destination() );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'GB', $stored_order->get_billing_country() );
		$this->assertEquals( 'US', $stored_order->get_shipping_country() );
	}

	/**
	 * Enables an offline gateway with the shipping restrictions from its settings screen.
	 *
	 * @param string $gateway_id         Gateway ID.
	 * @param array  $enable_for_methods Shipping methods the gateway is enabled for, or empty for all.
	 * @param bool   $enable_for_virtual Whether the gateway accepts orders that need no shipping.
	 */
	private function restrict_offline_gateway( string $gateway_id, array $enable_for_methods, bool $enable_for_virtual = true ) {
		update_option(
			"woocommerce_{$gateway_id}_settings",
			array_merge(
				(array) get_option( "woocommerce_{$gateway_id}_settings", array() ),
				array(
					'enabled'            => 'yes',
					'enable_for_methods' => $enable_for_methods,
					'enable_for_virtual' => $enable_for_virtual ? 'yes' : 'no',
				)
			)
		);
		WC()->payment_gateways()->init();
	}

	/**
	 * @testdox Existing order payment should refuse an offline gateway the store does not enable for the order's shipping method.
	 * @testWith ["cod"]
	 *           ["bacs"]
	 *           ["cheque"]
	 *
	 * @param string $gateway_id Gateway ID.
	 */
	public function test_checkout_order_refuses_gateway_not_enabled_for_order_shipping_method( string $gateway_id ) {
		$this->restrict_offline_gateway( $gateway_id, array( 'local_pickup' ) );

		// The order ships by flat rate, and the cart of the session paying it is unrelated to it.
		$order = \WC_Helper_Order::create_order( 0 );
		wc_empty_cart();

		$response = $this->dispatch_pay_for_order_request( $order, array(), null, true, $gateway_id );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_payment_method_disabled', $response->get_data()['code'] );
		$this->assertEquals( OrderStatus::PENDING, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Existing order payment should refuse a gateway the order's shipping method excludes when the pay endpoint is renamed.
	 */
	public function test_checkout_order_refuses_gateway_not_enabled_for_order_shipping_method_with_renamed_pay_endpoint() {
		update_option( 'woocommerce_checkout_pay_endpoint', 'pay' );
		WC()->query->init_query_vars();

		try {
			$this->restrict_offline_gateway( 'cod', array( 'local_pickup' ) );

			$order = \WC_Helper_Order::create_order( 0 );
			wc_empty_cart();

			$response = $this->dispatch_pay_for_order_request( $order, array(), null, true, 'cod' );

			$this->assertEquals( 400, $response->get_status() );
			$this->assertEquals( 'woocommerce_rest_checkout_payment_method_disabled', $response->get_data()['code'] );
		} finally {
			delete_option( 'woocommerce_checkout_pay_endpoint' );
			WC()->query->init_query_vars();
		}
	}

	/**
	 * @testdox Existing order payment should refuse a gateway that does not accept virtual orders for an order that needs no shipping.
	 */
	public function test_checkout_order_refuses_gateway_not_enabled_for_virtual_order() {
		$this->restrict_offline_gateway( 'cod', array(), false );

		$order = \WC_Helper_Order::create_order( 0, \WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) ) );
		foreach ( array_keys( $order->get_shipping_methods() ) as $item_id ) {
			$order->remove_item( $item_id );
		}
		$order->calculate_totals();

		// The cart of the session holds physical products, which must not make the order count as shipped.
		$response = $this->dispatch_pay_for_order_request( $order, array(), null, true, 'cod' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_checkout_payment_method_disabled', $response->get_data()['code'] );
		$this->assertEquals( OrderStatus::PENDING, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Existing order payment should accept an offline gateway the store enables for the order's shipping method.
	 */
	public function test_checkout_order_accepts_gateway_enabled_for_order_shipping_method() {
		$this->restrict_offline_gateway( 'cod', array( 'flat_rate_shipping' ) );

		$order = \WC_Helper_Order::create_order( 0 );
		wc_empty_cart();

		$response = $this->dispatch_pay_for_order_request( $order, array(), null, true, 'cod' );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( OrderStatus::PROCESSING, $stored_order->get_status() );
		$this->assertEquals( 'cod', $stored_order->get_payment_method() );
	}

	/**
	 * @testdox Existing order payment should restore the order-pay endpoint after the request.
	 */
	public function test_checkout_order_restores_order_pay_endpoint() {
		$order = \WC_Helper_Order::create_order( 0 );

		$response = $this->dispatch_pay_for_order_request( $order, array(), null );

		$this->assertEquals( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertFalse( is_wc_endpoint_url( 'order-pay' ) );
		$this->assertSame( '', get_query_var( 'order-pay' ) );
	}

	/**
	 * @testdox Existing order payment should check each order's own shipping method when batched, and restore the order-pay endpoint after the batch.
	 */
	public function test_checkout_order_checks_each_batched_order_shipping_method() {
		$this->restrict_offline_gateway( 'cod', array( 'local_pickup' ) );

		$flat_rate_order = \WC_Helper_Order::create_order( 0 );
		$pickup_order    = \WC_Helper_Order::create_order( 0 );
		foreach ( $pickup_order->get_shipping_methods() as $shipping_item ) {
			$shipping_item->set_method_id( 'local_pickup' );
			$shipping_item->save();
		}
		wc_empty_cart();

		$nonce       = wp_create_nonce( 'wc_store_api' );
		$subrequests = array();
		foreach ( array( $flat_rate_order, $pickup_order ) as $order ) {
			$subrequests[] = array(
				'method'  => 'POST',
				'path'    => '/wc/store/v1/checkout/' . $order->get_id(),
				'headers' => array( 'Nonce' => $nonce ),
				'body'    => array(
					'key'             => $order->get_order_key(),
					'billing_email'   => $order->get_billing_email(),
					'billing_address' => $order->get_address( 'billing' ),
					'payment_method'  => 'cod',
				),
			);
		}
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', $nonce );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'requests' => $subrequests ) ) );

		$responses = rest_get_server()->dispatch( $request )->get_data()['responses'];

		$this->assertEquals( 400, $responses[0]['status'], 'Cash on delivery is refused for the flat rate order.' );
		$this->assertEquals( 'woocommerce_rest_checkout_payment_method_disabled', $responses[0]['body']['code'] );
		$this->assertEquals( OrderStatus::PENDING, wc_get_order( $flat_rate_order->get_id() )->get_status() );
		$this->assertEquals( 200, $responses[1]['status'], 'Cash on delivery is accepted for the local pickup order: ' . wp_json_encode( $responses[1]['body'] ) );
		$this->assertEquals( OrderStatus::PROCESSING, wc_get_order( $pickup_order->get_id() )->get_status() );
		$this->assertFalse( is_wc_endpoint_url( 'order-pay' ) );
		$this->assertSame( '', get_query_var( 'order-pay' ) );
	}

	/**
	 * @testdox Existing order payment should refuse a guest's own checkout draft even with a gateway the shipping method excludes.
	 */
	public function test_checkout_order_refuses_gateway_not_enabled_for_checkout_draft_shipping_method() {
		$this->restrict_offline_gateway( 'cod', array( 'local_pickup' ) );

		// What a refused checkout leaves behind: the guest's own draft, which they hold the key to.
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_status( 'checkout-draft' );
		$order->save();
		wc_empty_cart();

		$response = $this->dispatch_pay_for_order_request( $order, array(), null, true, 'cod' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_order_update_status', $response->get_data()['code'] );
		$this->assertEquals( 'checkout-draft', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Paying an existing order enforces the coupon's global usage limit.
	 * @testWith ["route-global-limited", true, 409, false]
	 *           ["route-global-ok", false, 200, true]
	 *
	 * @param string $code            Coupon code.
	 * @param bool   $exhaust         Exhaust the coupon's global limit first.
	 * @param int    $expected_status Expected HTTP status.
	 * @param bool   $keeps_coupon    Whether the coupon should remain on the order.
	 */
	public function test_checkout_order_enforces_coupon_global_usage_limit( $code, $exhaust, $expected_status, $keeps_coupon ) {
		$coupon = new \WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_amount( 2 );
		$coupon->set_usage_limit( 1 );
		$coupon->save();
		if ( $exhaust ) {
			$coupon->increase_usage_count();
		}

		$order = \WC_Helper_Order::create_order( 0 );
		$item  = new \WC_Order_Item_Coupon();
		$item->set_code( $coupon->get_code() );
		$order->add_item( $item );
		$order->set_recorded_coupon_usage_counts( false );
		$order->save();

		$response = $this->dispatch_pay_for_order_request( $order );

		$this->assertEquals( $expected_status, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$codes = wc_get_order( $order->get_id() )->get_coupon_codes();
		if ( $keeps_coupon ) {
			$this->assertContains( $code, $codes );
		} else {
			$this->assertNotContains( $code, $codes );
			$this->assertEquals( 'woocommerce_rest_order_coupon_errors', $response->get_data()['code'] );
		}
	}

	/**
	 * @testdox Rejecting payment because the new billing email cannot use the coupon releases the row this order added, not another order's row.
	 */
	public function test_checkout_order_with_changed_email_releases_own_coupon_row() {
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'route-changed-email' );
		$coupon->set_amount( 2 );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->save();

		$buyer_order = \WC_Helper_Order::create_order( 0 );
		$buyer_order->set_billing_email( 'buyer@example.com' );
		$buyer_order->set_status( OrderStatus::PENDING );
		$buyer_order->save();
		$buyer_order->apply_coupon( $coupon->get_code() );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'reserve@example.com' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->apply_coupon( $coupon->get_code() );

		// Only the email changes, so the address guard lets the request reach the coupon check.
		$response = $this->dispatch_pay_for_order_request( $order, array( 'email' => 'buyer@example.com' ) );

		$this->assertSame( 409, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 'woocommerce_rest_order_coupon_errors', $response->get_data()['code'] );

		$coupon = new \WC_Coupon( 'route-changed-email' );
		$this->assertSame( array( 'buyer@example.com' ), array_values( $coupon->get_used_by() ) );
		$this->assertEquals( 1, $coupon->get_usage_count() );
	}

	/**
	 * @testdox Checkout refuses to complete a cancelled unpaid non-zero order.
	 */
	public function test_checkout_rejects_cancelled_unpaid_non_zero_order(): void {
		$order_id = 0;
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( \WC_Order $order ) use ( &$order_id ) {
				$order->set_status( OrderStatus::CANCELLED );
				$order->save();
				$order_id = $order->get_id();
			}
		);

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertSame( 400, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 'woocommerce_rest_checkout_payment_required', $response->get_data()['code'] );
		$this->assertSame( 'This order cannot be completed without payment. Please try again.', $response->get_data()['message'] );
		$this->assertGreaterThan( 0, $order_id, 'Checkout should have created an order before refusing to complete it.' );

		$order = wc_get_order( $order_id );
		$this->assertSame( OrderStatus::CANCELLED, $order->get_status(), 'The order should remain cancelled.' );
		$this->assertNull( $order->get_date_paid(), 'The order should not be marked paid.' );
	}

	/**
	 * @testdox Checkout allows extensions to waive payment for a pending non-zero order.
	 */
	public function test_checkout_allows_waived_payment_for_pending_non_zero_order(): void {
		add_filter( 'woocommerce_order_needs_payment', '__return_false' );

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 'success', $response->get_data()['payment_result']['payment_status'] );

		$order = wc_get_order( $response->get_data()['order_id'] );
		$this->assertTrue( $order->is_paid(), 'The intentionally payment-free order should be completed.' );
		$this->assertNotNull( $order->get_date_paid(), 'The intentionally payment-free order should have a paid date.' );
	}

	/**
	 * Helper method to register custom order status.
	 *
	 * @param string $status_name             Custom status name to register.
	 * @param bool   $add_to_payment_statuses Whether to add the status to valid statuses for payment.
	 */
	private function register_custom_order_status( $status_name, $add_to_payment_statuses = false ) {
		add_filter(
			'woocommerce_register_shop_order_post_statuses',
			function ( $order_statuses ) use ( $status_name ) {
				$order_statuses[ 'wc-' . $status_name ] = array(
					'label'                     => 'Custom status for testing',
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
				);
				return $order_statuses;
			}
		);

		add_filter(
			'wc_order_statuses',
			function ( $order_statuses ) use ( $status_name ) {
				$order_statuses[ 'wc-' . $status_name ] = 'Custom status for testing';
				return $order_statuses;
			}
		);

		if ( $add_to_payment_statuses ) {
			add_filter(
				'woocommerce_valid_order_statuses_for_payment',
				function ( $statuses ) use ( $status_name ) {
					$statuses[] = $status_name;
					return $statuses;
				}
			);
		}
	}

	/**
	 * Test that custom status is retained for free orders.
	 */
	public function test_custom_status_retained_for_free_order() {
		$status_name = 'ready_for_pickup';
		$this->register_custom_order_status( $status_name );

		// Create a simple product and add to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '0' ); // Make the product free.
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Verify that the cart total is 0 (free order).
		$this->assertEquals( 0, WC()->cart->get_total( 'numeric' ), 'Cart total should be 0 for a free order' );

		// Hook into the checkout process to set the custom status.
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( \WC_Order $order ) use ( $status_name ) {
				$order->set_status( $status_name );
				$order->save();
			}
		);

		// Create an order via checkout route.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => WC_Gateway_BACS::ID, // Payment method might still be required, even if free.
				'expected_total'   => '0',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order_id = $response->get_data()['order_id'];
		$order    = wc_get_order( $order_id );

		// Assert status remains custom.
		$this->assertEquals( $status_name, $order->get_status(), 'Order status should remain custom for free orders.' );
	}

	/**
	 * Test that payment methods are not saved to free orders.
	 */
	public function test_payment_method_is_cleared_for_free_order() {
		// Create a simple product and add to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '0' ); // Make the product free.
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Verify that the cart total is 0 (free order).
		$this->assertEquals( 0, WC()->cart->get_total( 'numeric' ), 'Cart total should be 0 for a free order' );

		// Create an order via checkout route.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => '',
				'expected_total'   => '0',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order_id = $response->get_data()['order_id'];
		$order    = wc_get_order( $order_id );

		// Assert payment method is not saved.
		$this->assertEquals( '', $order->get_payment_method(), 'Payment method should be cleared for free orders.' );
	}

	/**
	 * Test that custom status is retained for non-free orders when the custom
	 * status is not in the valid statuses for payment list.
	 */
	public function test_custom_status_retained_for_non_free_order() {
		$status_name = 'ready_for_pickup';
		$this->register_custom_order_status( $status_name );

		// Create a simple product with a non-zero price and add to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10.00' ); // Make sure the product is not free.
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Verify that the cart total is NOT 0 (non-free order).
		$this->assertGreaterThan( 0, WC()->cart->get_total( 'numeric' ), 'Cart total should be greater than 0 for a non-free order' );

		// Hook into the checkout process to set the custom status.
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( \WC_Order $order ) use ( $status_name ) {
				$order->set_status( $status_name );
				$order->save();
			}
		);

		// Create an order via checkout route.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '1000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order_id = $response->get_data()['order_id'];
		$order    = wc_get_order( $order_id );

		// Assert status remains custom (the key test here - verifying needs_payment() returns false despite non-zero total).
		$this->assertEquals( $status_name, $order->get_status(), 'Order status should remain custom for non-free orders when the status is not valid for payment.' );
	}

	/**
	 * Test that custom status goes through payment flow
	 * when added to valid statuses for payment list.
	 */
	public function test_custom_status_with_payment_when_added_to_valid_statuses() {
		$status_name = 'ready_for_pickup';
		$this->register_custom_order_status( $status_name, true );

		// Create a simple product with a non-zero price and add to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10.00' ); // Make sure the product is not free.
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Verify that the cart total is NOT 0 (non-free order).
		$this->assertGreaterThan( 0, WC()->cart->get_total( 'numeric' ), 'Cart total should be greater than 0 for a non-free order' );

		// Add a hook to check the needs_payment() result and set the status.
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( \WC_Order $order ) use ( $status_name ) {
				// Set our custom status.
				$order->set_status( $status_name );
				$order->save();
			}
		);

		// Create an order via checkout route.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'email'                       => 'test@test.com',
					'plugin-namespace/student-id' => '12345678',
				),
				'shipping_address' => (object) array(
					'first_name'                  => 'test',
					'last_name'                   => 'test',
					'address_1'                   => 'test',
					'city'                        => 'test',
					'state'                       => 'CA',
					'postcode'                    => '12345',
					'country'                     => 'FR',
					'plugin-namespace/student-id' => '12345678',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '1000',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order_id = $response->get_data()['order_id'];
		$order    = wc_get_order( $order_id );

		// Order shouldn't stay in custom status, instead we let payment gateway set the correct status.
		$this->assertEquals( 'on-hold', $order->get_status(), 'Order status should be controlled by the payment gateway, not remain custom.' );
	}

	/**
	 * GET /wc/store/v1/checkout against a populated cart must not create a draft order.
	 *
	 * Prior to 10.8.0, simply rendering the checkout block triggered a draft order creation
	 * which was the largest source of orphaned `wc-checkout-draft` rows.
	 */
	public function test_get_does_not_create_draft_order() {
		$drafts_before = wc_get_orders(
			array(
				'status' => 'checkout-draft',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 0, $data['order_id'], 'GET response should report order_id 0 — no draft order materialised.' );

		$drafts_after = wc_get_orders(
			array(
				'status' => 'checkout-draft',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$this->assertCount(
			count( $drafts_before ),
			$drafts_after,
			'GET should not create any wc-checkout-draft rows.'
		);

		// Session should not hold a draft order id.
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * GET should not fire `woocommerce_store_api_checkout_update_order_meta` (only PATCH/POST do).
	 */
	public function test_get_does_not_fire_update_order_meta_action() {
		$fired = false;
		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		rest_get_server()->dispatch( $request );

		remove_all_actions( 'woocommerce_store_api_checkout_update_order_meta' );

		$this->assertFalse( $fired, 'update_order_meta should not fire on GET — it should only fire when the draft is materialised.' );
	}

	/**
	 * Phase 2: POST is the only place that materialises a draft order, and the
	 * `woocommerce_store_api_checkout_order_created` action fires once at that point.
	 */
	public function test_post_creates_order_and_fires_order_created_action() {
		$created_order_ids = array();
		add_action(
			'woocommerce_store_api_checkout_order_created',
			function ( $order ) use ( &$created_order_ids ) {
				$created_order_ids[] = $order->get_id();
			}
		);

		$post_request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$post_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$post_request->set_body_params(
			array(
				'billing_address'  => array(
					'first_name' => 'Phase',
					'last_name'  => 'Two',
					'company'    => '',
					'address_1'  => '123 Test St',
					'address_2'  => '',
					'city'       => 'Test City',
					'state'      => 'CA',
					'postcode'   => '94016',
					'country'    => 'US',
					'email'      => 'phasetwo@example.com',
					'phone'      => '5555555555',
				),
				'shipping_address' => array(
					'first_name' => 'Phase',
					'last_name'  => 'Two',
					'company'    => '',
					'address_1'  => '123 Test St',
					'address_2'  => '',
					'city'       => 'Test City',
					'state'      => 'CA',
					'postcode'   => '94016',
					'country'    => 'US',
					'phone'      => '5555555555',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);

		$post_response = rest_get_server()->dispatch( $post_request );

		remove_all_actions( 'woocommerce_store_api_checkout_order_created' );

		$this->assertEquals( 200, $post_response->get_status(), print_r( $post_response->get_data(), true ) );

		$order_id = $post_response->get_data()['order_id'];
		$this->assertGreaterThan( 0, $order_id );

		$this->assertCount( 1, $created_order_ids );
		$this->assertSame( $order_id, $created_order_ids[0] );
	}

	/**
	 * Phase 2: PATCH should not create a draft order row; the order is materialised at POST.
	 */
	public function test_put_does_not_create_draft_order() {
		$drafts_before = wc_get_orders(
			array(
				'status' => 'checkout-draft',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 0, $response->get_data()['order_id'], 'PATCH should report order_id 0 — order is now deferred to POST.' );

		$drafts_after = wc_get_orders(
			array(
				'status' => 'checkout-draft',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$this->assertCount( count( $drafts_before ), $drafts_after, 'PATCH should not create any wc-checkout-draft rows.' );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ), 'No session draft id should be set after PATCH under Phase 2.' );
	}

	/**
	 * The persisted-order hooks (`update_order_meta`, `update_order_from_request`)
	 * MUST NOT fire on PATCH — they are reserved for the real order materialised at
	 * place-order time.
	 */
	public function test_put_does_not_fire_persisted_order_hooks() {
		$update_meta_orders         = array();
		$update_from_request_orders = array();

		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function ( $order ) use ( &$update_meta_orders ) {
				$update_meta_orders[] = $order->get_id();
			}
		);
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order ) use ( &$update_from_request_orders ) {
				$update_from_request_orders[] = $order->get_id();
			}
		);

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
			)
		);
		$response = rest_get_server()->dispatch( $request );

		remove_all_actions( 'woocommerce_store_api_checkout_update_order_meta' );
		remove_all_actions( 'woocommerce_store_api_checkout_update_order_from_request' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( array(), $update_meta_orders, 'update_order_meta must not fire on PATCH.' );
		$this->assertSame( array(), $update_from_request_orders, 'update_order_from_request must not fire on PATCH.' );
	}

	/**
	 * The new `woocommerce_store_api_checkout_update_draft` action fires once per PATCH
	 * with the request, and is the only hook extensions should subscribe to for live
	 * PATCH-time observation. No `WC_Order` is constructed.
	 */
	public function test_put_fires_update_draft_action() {
		$received_requests = array();

		add_action(
			'woocommerce_store_api_checkout_update_draft',
			function ( $request ) use ( &$received_requests ) {
				$received_requests[] = $request;
			}
		);

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
			)
		);
		$response = rest_get_server()->dispatch( $request );

		remove_all_actions( 'woocommerce_store_api_checkout_update_draft' );

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertCount( 1, $received_requests, 'update_draft should fire exactly once per PATCH.' );
		$this->assertInstanceOf( \WP_REST_Request::class, $received_requests[0] );

		// The PATCH'd payment method should be reflected in the response and the session.
		$this->assertSame( WC_Gateway_BACS::ID, $response->get_data()['payment_method'] );
		$this->assertSame( WC_Gateway_BACS::ID, WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * On POST the persisted-order hooks fire against a real, persisted order — exactly
	 * as they did before deferred draft creation. The new draft action does NOT fire
	 * on POST.
	 */
	public function test_post_fires_persisted_hooks_against_real_order_only() {
		$meta_ids          = array();
		$from_request_ids  = array();
		$draft_request_ids = array();

		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function ( $order ) use ( &$meta_ids ) {
				$meta_ids[] = $order->get_id();
			}
		);
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order ) use ( &$from_request_ids ) {
				$from_request_ids[] = $order->get_id();
			}
		);
		add_action(
			'woocommerce_store_api_checkout_update_draft',
			function ( $request ) use ( &$draft_request_ids ) {
				$draft_request_ids[] = $request->get_method();
			}
		);

		$post_response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		remove_all_actions( 'woocommerce_store_api_checkout_update_order_meta' );
		remove_all_actions( 'woocommerce_store_api_checkout_update_order_from_request' );
		remove_all_actions( 'woocommerce_store_api_checkout_update_draft' );

		$this->assertEquals( 200, $post_response->get_status(), print_r( $post_response->get_data(), true ) );
		$order_id = $post_response->get_data()['order_id'];
		$this->assertGreaterThan( 0, $order_id );

		$this->assertSame( array( $order_id ), $meta_ids, 'update_order_meta must fire exactly once on POST against the real order.' );
		$this->assertSame( array( $order_id ), $from_request_ids, 'update_order_from_request must fire exactly once on POST against the real order.' );
		$this->assertSame( array(), $draft_request_ids, 'update_draft must NOT fire on POST.' );
	}

	/**
	 * Sample-extension round-trip: an extension uses `update_draft` on PATCH to persist
	 * live state to its own session bucket, then uses `update_order_meta` on POST to
	 * apply that state to the real order. State observed during PATCH lands on the
	 * persisted order. This is the documented pattern under deferred draft creation.
	 */
	public function test_extension_round_trip_via_update_draft_and_update_order_meta() {
		$session_key = 'sample_ext_pending_meta';

		// PATCH handler — capture live state from cart/customer/request into session.
		add_action(
			'woocommerce_store_api_checkout_update_draft',
			function ( $request ) use ( $session_key ) {
				WC()->session->set(
					$session_key,
					array(
						'_sample_ext_payment_method' => (string) $request['payment_method'],
						'_sample_ext_observed_total' => (string) WC()->cart->get_total( 'edit' ),
					)
				);
			}
		);

		// POST handler — apply session-stored state to the real order.
		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function ( $order ) use ( $session_key ) {
				$pending = WC()->session->get( $session_key );
				if ( ! is_array( $pending ) ) {
					return;
				}
				foreach ( $pending as $key => $value ) {
					$order->update_meta_data( $key, $value );
				}
				WC()->session->__unset( $session_key );
			}
		);

		$put_request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$put_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$put_request->set_body_params(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
			)
		);
		$put_response = rest_get_server()->dispatch( $put_request );
		$this->assertEquals( 200, $put_response->get_status(), print_r( $put_response->get_data(), true ) );
		$this->assertSame( 0, $put_response->get_data()['order_id'] );
		$this->assertIsArray( WC()->session->get( $session_key ) );

		$post_response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		remove_all_actions( 'woocommerce_store_api_checkout_update_draft' );
		remove_all_actions( 'woocommerce_store_api_checkout_update_order_meta' );

		$this->assertEquals( 200, $post_response->get_status(), print_r( $post_response->get_data(), true ) );
		$order_id = $post_response->get_data()['order_id'];
		$order    = wc_get_order( $order_id );
		$this->assertInstanceOf( \WC_Order::class, $order );

		$this->assertSame( WC_Gateway_BACS::ID, $order->get_meta( '_sample_ext_payment_method' ) );
		$this->assertNotEmpty( $order->get_meta( '_sample_ext_observed_total' ) );
		$this->assertEmpty( WC()->session->get( $session_key ) );
	}

	/**
	 * Sample-extension immediate-place-order case: a logged-in shopper lands on
	 * checkout with defaults populated and clicks Place Order without any PATCH first.
	 * The extension's POST handler still runs against the real order (no PATCH state
	 * to round-trip) and can write meta directly. This validates the user concern that
	 * a no-PATCH POST is fully supported.
	 */
	public function test_extension_immediate_post_without_patch() {
		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function ( $order ) {
				$order->update_meta_data( '_sample_ext_no_patch_meta', 'placed-directly' );
			}
		);

		$post_response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		remove_all_actions( 'woocommerce_store_api_checkout_update_order_meta' );

		$this->assertEquals( 200, $post_response->get_status(), print_r( $post_response->get_data(), true ) );
		$order = wc_get_order( $post_response->get_data()['order_id'] );
		$this->assertSame( 'placed-directly', $order->get_meta( '_sample_ext_no_patch_meta' ) );
	}

	/**
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/64792.
	 *
	 * After a failed payment, the customer's session holds a pointer to the pending
	 * order. A second POST on the same session must reuse that order — otherwise the
	 * session pointer is overwritten by `set_draft_order_id()` and the first order is
	 * orphaned. Prior to the fix, `create_or_update_draft_order()` did not consult the
	 * session and unconditionally created a new order on every POST.
	 */
	public function test_post_reuses_pending_order_from_session_on_retry() {
		// Force the first POST to fail at the order-processed hook, mirroring the
		// real failed-payment shape from issue #64792. The throw happens after the
		// order has been created and the session pointer set, but before the cart-
		// clear that would normally follow a successful checkout.
		$fail_hook = function () {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'woocommerce_rest_checkout_payment_failed',
				'Forced failure for issue #64792 repro',
				400
			);
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $fail_hook, 999 );

		$first_response = rest_get_server()->dispatch( $this->build_valid_post_request() );
		$this->assertEquals( 400, $first_response->get_status(), 'First POST should fail per the forced-failure hook.' );

		$first_order_id = (int) WC()->session->get( 'store_api_draft_order' );
		$this->assertGreaterThan( 0, $first_order_id, 'Session should hold the failed order id after a failed POST.' );

		$first_order = wc_get_order( $first_order_id );
		$this->assertInstanceOf( \WC_Order::class, $first_order );
		$this->assertTrue( $first_order->has_status( 'pending' ), 'First order should be left in pending status after payment failure.' );

		remove_action( 'woocommerce_store_api_checkout_order_processed', $fail_hook, 999 );

		// Second POST on the same session should reuse the existing pending order.
		$session_order_id_during_retry = null;
		$capture_session_order_id      = function () use ( &$session_order_id_during_retry ) {
			$session_order_id_during_retry = (int) WC()->session->get( 'store_api_draft_order' );
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $capture_session_order_id, 999, 0 );

		try {
			$second_response = rest_get_server()->dispatch( $this->build_valid_post_request() );
		} finally {
			remove_action( 'woocommerce_store_api_checkout_order_processed', $capture_session_order_id, 999 );
		}

		$this->assertEquals( 200, $second_response->get_status(), print_r( $second_response->get_data(), true ) );

		$second_order_id = (int) $second_response->get_data()['order_id'];
		$this->assertSame(
			$first_order_id,
			$second_order_id,
			'Second POST must reuse the existing pending order, not create a new one (regression: issue #64792).'
		);
		$this->assertSame( $first_order_id, $session_order_id_during_retry, 'Session pointer should reference the reused order while the second POST is being processed.' );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ), 'Successful checkout should clear the draft order pointer when the cart is emptied.' );
	}

	/**
	 * @testdox Checkout stops when the draft order could not be saved.
	 */
	public function test_post_rejects_draft_order_when_save_fails(): void {
		$order_id = $this->create_draft_order_then_remove_first_cart_line();
		$this->fail_saves_of_order( $order_id );

		$response = rest_get_server()->dispatch( $this->build_place_order_request() );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::CHECKOUT_DRAFT ), 'The order must not move on to payment.' );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ), 'The order whose save failed must not stay in the session to be resumed.' );
	}

	/**
	 * @testdox The next checkout after a failed save completes normally.
	 */
	public function test_post_after_failed_save_places_order(): void {
		$order_id = $this->create_draft_order_then_remove_first_cart_line();
		$fail     = $this->fail_saves_of_order( $order_id );
		rest_get_server()->dispatch( $this->build_place_order_request() );
		remove_action( 'woocommerce_update_order', $fail );

		$response = rest_get_server()->dispatch( $this->build_place_order_request() );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order = wc_get_order( $response->get_data()['order_id'] );
		$items = array_values( $order->get_items() );
		$this->assertCount( 1, $items, 'The order should hold the cart line.' );
		$this->assertSame( $this->products[1]->get_id(), $items[0]->get_product_id() );
		$this->assertSame( (float) $items[0]->get_total() + (float) $order->get_shipping_total(), (float) $order->get_total() );
	}

	/**
	 * @testdox Checkout stops before payment when setting the order to pending fails to save.
	 */
	public function test_post_rejects_order_when_pending_status_fails_to_save(): void {
		$fail = function ( $order ) {
			if ( $order->has_status( OrderStatus::PENDING ) ) {
				throw new \Exception( 'Forced save failure' );
			}
		};
		add_action( 'woocommerce_before_order_object_save', $fail );
		$processed = false;
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function () use ( &$processed ) {
				$processed = true;
			}
		);

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertFalse( $processed, 'The order must not reach the processed action or payment.' );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * @testdox Checkout stops before payment when an extension's save of the order fails on $hook.
	 * @testWith ["woocommerce_store_api_checkout_order_processed"]
	 *           ["woocommerce_valid_order_statuses_for_payment"]
	 *           ["woocommerce_order_needs_payment"]
	 *
	 * @param string $hook Action or filter that receives the order after its last save point.
	 */
	public function test_post_rejects_order_when_extension_save_fails_before_payment( string $hook ): void {
		$order_id = 0;
		$this->record_processed_order_id( $order_id );
		add_filter(
			$hook,
			function ( $value, $order = null ) use ( &$order_id ) {
				$order = $value instanceof \WC_Order ? $value : $order;
				// The needs_payment() filters also run before the processed action, which the earlier save points cover.
				if ( $order_id && $order instanceof \WC_Order ) {
					$this->save_order_with_forced_failure( $order );
				}
				return $value;
			},
			10,
			2
		);
		$payment_attempted = false;
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function () use ( &$payment_attempted ) {
				$payment_attempted = true;
			}
		);

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertFalse( $payment_attempted, 'Payment must not be attempted for an order that could not be saved.' );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::PENDING ), 'The order must stay pending.' );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ), 'The order whose save failed must not stay in the session to be resumed.' );
	}

	/**
	 * @testdox Checkout of an order that needs no payment fails when marking it paid cannot save it.
	 */
	public function test_post_without_payment_rejects_order_when_payment_complete_fails_to_save(): void {
		add_filter( 'woocommerce_order_needs_payment', '__return_false' );
		$order_id = 0;
		$this->record_processed_order_id( $order_id );
		// As an extension saving invalid data from this filter would, make the payment_complete() save fail.
		add_filter(
			'woocommerce_payment_complete_order_status',
			function ( $status ) use ( &$order_id ) {
				if ( $order_id ) {
					add_action(
						'woocommerce_before_order_object_save',
						function () {
							throw new \Exception( 'Forced save failure' );
						}
					);
				}
				return $status;
			}
		);

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$order = wc_get_order( $order_id );
		$this->assertTrue( $order->has_status( OrderStatus::PENDING ), 'The order must stay pending.' );
		$this->assertNull( $order->get_date_paid(), 'The order must not be marked paid.' );
	}

	/**
	 * Record the ID of the order passed to the processed action, which also marks that the last save point has passed.
	 *
	 * @param int $order_id Set to the order ID when the action fires.
	 */
	private function record_processed_order_id( int &$order_id ): void {
		$order_id = 0;
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( $order ) use ( &$order_id ) {
				$order_id = $order->get_id();
			},
			1
		);
	}

	/**
	 * Save the order while a callback makes the save fail, as saving a value too long for its column would.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function save_order_with_forced_failure( \WC_Order $order ): void {
		$fail = function () {
			throw new \Exception( 'Forced save failure' );
		};
		add_action( 'woocommerce_before_order_object_save', $fail );
		$order->save();
		remove_action( 'woocommerce_before_order_object_save', $fail );
	}

	/**
	 * @testdox Updating the checkout with PATCH fails and forgets the draft order when the order cannot be saved.
	 */
	public function test_put_rejects_draft_order_that_fails_to_save(): void {
		$order_id = $this->create_draft_order_then_remove_first_cart_line();
		$this->fail_saves_of_order( $order_id );

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'customer_note' => 'Leave at the door' ) );
		$this->simulate_fresh_request();
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertEmpty( WC()->session->get( 'store_api_draft_order' ) );
	}

	/**
	 * @testdox Pay for order stops before payment when the order cannot be saved.
	 */
	public function test_checkout_order_rejects_order_that_fails_to_save(): void {
		$order    = \WC_Helper_Order::create_order( 0 );
		$order_id = $order->get_id();
		$this->fail_saves_of_order( $order_id );

		$response = rest_get_server()->dispatch( $this->build_pay_for_order_request( $order ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertTrue( wc_get_order( $order_id )->has_status( OrderStatus::PENDING ), 'The order must not move on to payment.' );
	}

	/**
	 * @testdox Pay for order stops before payment when an extension's save of the order fails on $hook.
	 * @testWith ["woocommerce_store_api_checkout_order_processed"]
	 *           ["woocommerce_valid_order_statuses_for_payment"]
	 *           ["woocommerce_order_needs_payment"]
	 *
	 * @param string $hook Action or filter that receives the order after its last save point.
	 */
	public function test_checkout_order_rejects_order_when_extension_save_fails_before_payment( string $hook ): void {
		$order        = \WC_Helper_Order::create_order( 0 );
		$processed_id = 0;
		$this->record_processed_order_id( $processed_id );
		add_filter(
			$hook,
			function ( $value, $filtered_order = null ) use ( &$processed_id ) {
				$filtered_order = $value instanceof \WC_Order ? $value : $filtered_order;
				// The needs_payment() filters also run before the processed action, which the earlier save points cover.
				if ( $processed_id && $filtered_order instanceof \WC_Order ) {
					$this->save_order_with_forced_failure( $filtered_order );
				}
				return $value;
			},
			10,
			2
		);
		$payment_attempted = false;
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function () use ( &$payment_attempted ) {
				$payment_attempted = true;
			}
		);

		$response = rest_get_server()->dispatch( $this->build_pay_for_order_request( $order ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_checkout_order_not_saved', $response->get_data()['code'] );
		$this->assertFalse( $payment_attempted, 'Payment must not be attempted for an order that could not be saved.' );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::PENDING ), 'The order must stay pending.' );
	}

	/**
	 * Build a pay for order request for an existing order, sending the order's own billing address.
	 *
	 * The order's own address keeps the request from being refused for re-pricing the order.
	 *
	 * @param \WC_Order $order Order to pay for.
	 * @return \WP_REST_Request
	 */
	private function build_pay_for_order_request( \WC_Order $order ): \WP_REST_Request {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout/' . $order->get_id() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_query_params(
			array(
				'key'           => $order->get_order_key(),
				'billing_email' => $order->get_billing_email(),
			)
		);
		$request->set_body_params(
			array(
				'billing_address' => $order->get_address( 'billing' ),
				'payment_method'  => WC_Gateway_BACS::ID,
			)
		);
		return $request;
	}

	/**
	 * Store a draft order for the seeded cart, then remove one cart line.
	 *
	 * @return int Draft order ID.
	 */
	private function create_draft_order_then_remove_first_cart_line(): int {
		// A registered but disabled gateway is refused after the draft order is stored.
		$request = $this->build_valid_post_request();
		$request->set_param( 'payment_method', 'cheque' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );

		$order_id = (int) WC()->session->get( 'store_api_draft_order' );
		$this->assertCount( 2, wc_get_order( $order_id )->get_items(), 'The draft order should hold both cart lines.' );

		wc()->cart->remove_cart_item( wc()->cart->generate_cart_id( $this->products[0]->get_id() ) );

		return $order_id;
	}

	/**
	 * Build a place order request for the current cart, as a new HTTP request would send it.
	 *
	 * @return \WP_REST_Request
	 */
	private function build_place_order_request(): \WP_REST_Request {
		$request = $this->build_valid_post_request();
		$params  = $request->get_body_params();
		unset( $params['expected_total'] );
		$request->set_body_params( $params );
		$this->simulate_fresh_request();
		return $request;
	}

	/**
	 * Make every save of the given order fail, as a database error would.
	 *
	 * @param int $order_id Order ID.
	 * @return callable The callback added to woocommerce_update_order.
	 */
	private function fail_saves_of_order( int $order_id ): callable {
		$fail = function ( $updated_order_id ) use ( $order_id ) {
			if ( (int) $updated_order_id === $order_id ) {
				throw new \Exception( 'Forced save failure' );
			}
		};
		add_action( 'woocommerce_update_order', $fail );
		return $fail;
	}

	/**
	 * @testdox Checkout places a zero-total pending or failed order from the session instead of creating a new one.
	 * @testWith ["pending"]
	 *           ["failed"]
	 *
	 * @param string $status Status of the order in the session.
	 */
	public function test_post_reuses_zero_total_order_from_session( string $status ): void {
		$this->apply_full_discount_coupon();
		$order = $this->create_session_order_from_cart( $status );
		$order->update_meta_data( '_subscription_renewal', 123 );
		$order->save();
		$this->assertSame( 0.0, (float) $order->get_total(), 'The coupon must take the order to a zero total, which is the case this test covers.' );

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( $order->get_id(), (int) $response->get_data()['order_id'], 'Checkout must place the zero-total order from the session, not a new one without its data.' );

		$placed_order = wc_get_order( $order->get_id() );
		$this->assertTrue( $placed_order->is_paid(), 'The zero-total order from the session should be completed by checkout.' );
		$this->assertSame( '123', (string) $placed_order->get_meta( '_subscription_renewal' ), 'Renewal metadata on the order from the session should be kept.' );
	}

	/**
	 * @testdox Checkout answers with the zero-total order from the session, without placing another, once that order has moved past awaiting payment.
	 */
	public function test_post_answers_with_completed_zero_total_order_from_session(): void {
		$this->apply_full_discount_coupon();
		$order = $this->create_session_order_from_cart( OrderStatus::COMPLETED );

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$order_ids = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
				'return' => 'ids',
			)
		);

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( array( $order->get_id() ), $order_ids, 'A completed order must not be placed again.' );
		$this->assertSame( $order->get_checkout_order_received_url(), $response->get_data()['payment_result']['redirect_url'], 'The shopper should be sent to the order received page of the completed order.' );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::COMPLETED ), 'The completed order should be left as it was.' );
	}

	/**
	 * @testdox Checkout does not reuse a positive-total order from the session when woocommerce_order_needs_payment says it needs no payment.
	 */
	public function test_post_respects_needs_payment_filter_for_positive_total_order_from_session(): void {
		$order = $this->create_session_order_from_cart( OrderStatus::PENDING );
		add_filter(
			'woocommerce_order_needs_payment',
			function ( $needs_payment, $filtered_order ) use ( $order ) {
				return $filtered_order->get_id() === $order->get_id() ? false : $needs_payment;
			},
			10,
			2
		);

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertNotSame( $order->get_id(), (int) $response->get_data()['order_id'], 'An order the filter says needs no payment must not be reused.' );
	}

	/**
	 * @testdox Checkout does not reuse a pending order from the session when the shipping destination moves but the total does not.
	 * @testWith [{"city": "Other City"}]
	 *           [{"city": "London", "state": "", "postcode": "SW1A 1AA", "country": "GB"}]
	 *
	 * @param array $destination Shipping address fields the checkout request changes.
	 */
	public function test_post_does_not_reuse_pending_order_from_session_when_shipping_destination_changes( array $destination ): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		$order          = $this->create_pending_order_for_us_customer();
		$original_total = $order->get_total();

		$this->assertTrue( $order->needs_payment(), 'Baseline: the placed order must still need payment.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'Baseline: the order is the session draft while its destination matches the customer.' );
		$this->assertSame( $order->get_id(), (int) WC()->session->get( 'store_api_draft_order' ), 'Baseline: the session must point at the placed order.' );

		$request = $this->build_valid_post_request();
		$posted  = array_merge( $request->get_param( 'shipping_address' ), $destination );
		$request->set_param( 'shipping_address', $posted );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$new_order = wc_get_order( (int) $response->get_data()['order_id'] );
		$this->assertNotSame( $order->get_id(), $new_order->get_id(), 'Checkout must place a new order rather than readdress the placed one.' );
		$this->assertSame( $original_total, $new_order->get_total(), 'The total must not change, so only the destination check can stop reuse.' );
		$this->assertSame( array( $posted['country'], $posted['city'] ), array( $new_order->get_shipping_country(), $new_order->get_shipping_city() ), 'The new order should ship to the posted destination.' );

		$order = wc_get_order( $order->get_id() );
		$this->assertTrue( $order->has_status( OrderStatus::PENDING ), 'The placed order should stay pending.' );
		$this->assertSame( array( 'US', 'Test City' ), array( $order->get_shipping_country(), $order->get_shipping_city() ), 'The placed order must keep the destination it was rated for.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order once the customer moves to a destination in another shipping zone.
	 */
	public function test_placed_order_is_not_reused_when_shipping_destination_changes(): void {
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		$this->assertTrue( $order->needs_payment(), 'Baseline: the placed order must still need payment.' );
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its destination matches the customer.' );

		// Move the customer without recalculating, so the cart hash stays the same.
		$destination = $this->get_different_destination();
		WC()->customer->set_shipping_city( $destination['city'] );
		WC()->customer->set_shipping_state( $destination['state'] );
		WC()->customer->set_shipping_postcode( $destination['postcode'] );
		WC()->customer->set_shipping_country( $destination['country'] );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), 'A placed order whose destination moved to another zone must not be reused as the session draft.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when a change cannot alter the shipping rate: the street line, the city's letter case, the postcode spacing or the billing address.
	 */
	public function test_placed_order_is_reused_when_rate_location_is_unchanged(): void {
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		// Apply the changes one at a time so a failure points at the one that wrongly forced a fresh order.
		WC()->customer->set_shipping_address_1( '456 Other Ave' );
		$this->assertTrue( $checker->is_draft( $order ), 'A changed street line cannot alter the rate, so the order must still be reused.' );

		WC()->customer->set_shipping_city( 'TEST CITY' );
		$this->assertTrue( $checker->is_draft( $order ), 'A change of city letter case must not force a fresh order.' );

		WC()->customer->set_shipping_postcode( '940 16' );
		$this->assertTrue( $checker->is_draft( $order ), 'A change of postcode spacing must not force a fresh order.' );

		WC()->customer->set_billing_country( 'GB' );
		$this->assertTrue( $checker->is_draft( $order ), 'A billing-only change must not force a fresh order while tax follows the shipping address.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when the cart needs no shipping, whatever the destination.
	 */
	public function test_placed_order_is_reused_when_cart_needs_no_shipping(): void {
		wc_empty_cart();
		WC()->cart->add_to_cart( $this->products[2]->get_id(), 1 );
		$this->assertFalse( WC()->cart->needs_shipping(), 'Test cart is expected to not need shipping.' );

		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		WC()->customer->set_shipping_country( 'GB' );

		$this->assertTrue( $checker->is_draft( $order ), 'A cart with nothing to ship has no destination to protect, so the order must still be reused.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order when only the shipping $field changes.
	 * @testWith ["country", "GB"]
	 *           ["state", "NY"]
	 *           ["postcode", "10001"]
	 *           ["city", "Other City"]
	 *
	 * @param string $field Destination field to change.
	 * @param string $value New value for that field.
	 */
	public function test_placed_order_is_not_reused_when_one_destination_field_changes( string $field, string $value ): void {
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its destination matches the customer.' );

		WC()->customer->{"set_shipping_{$field}"}( $value );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), "A placed order must not be reused once only its shipping {$field} changes." );
	}

	/**
	 * @testdox is_valid_draft_order() takes the billing address as the destination of a placed order without a shipping address, and stops reusing it when that moves.
	 */
	public function test_placed_order_without_shipping_address_is_checked_against_billing(): void {
		$checker = $this->get_draft_order_checker();

		WC()->customer->set_shipping_country( '' );
		WC()->customer->set_billing_city( 'Test City' );
		WC()->customer->set_billing_state( 'CA' );
		WC()->customer->set_billing_postcode( '94016' );
		WC()->customer->set_billing_country( 'US' );
		$order = $this->create_session_order_from_cart( OrderStatus::PENDING );

		$this->assertSame( '', $order->get_shipping_country(), 'Baseline: the order must have no shipping address, so its billing address is the destination.' );
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its billing destination matches the customer.' );

		WC()->customer->set_billing_state( 'NY' );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), 'Moving the billing address must stop reuse when it is the only destination.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a zero-total pending order when the customer moves, since only checkout, which recalculates it, can place it.
	 */
	public function test_zero_total_order_is_reused_when_shipping_destination_changes(): void {
		$checker = $this->get_draft_order_checker();
		$this->apply_full_discount_coupon();
		$order = $this->create_pending_order_for_us_customer();

		$this->assertSame( 0.0, (float) $order->get_total(), 'The coupon must take the order to a zero total, which is the case this test covers.' );
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the zero-total order is the session draft while its destination matches the customer.' );

		$destination = $this->get_different_destination();
		WC()->customer->set_shipping_city( $destination['city'] );
		WC()->customer->set_shipping_state( $destination['state'] );
		WC()->customer->set_shipping_postcode( $destination['postcode'] );
		WC()->customer->set_shipping_country( $destination['country'] );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check could change the outcome.' );
		$this->assertTrue( $checker->is_draft( $order ), 'A zero-total order, such as a renewal, must stay the session draft when the address changes.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a zero-total order that a filter makes payable once the customer moves, as it would a priced order.
	 */
	public function test_zero_total_order_made_payable_is_not_reused_when_shipping_destination_changes(): void {
		add_filter( 'woocommerce_order_needs_payment', '__return_true' );
		$checker = $this->get_draft_order_checker();
		$this->apply_full_discount_coupon();
		$order = $this->create_pending_order_for_us_customer();

		$this->assertSame( 0.0, (float) $order->get_total(), 'The coupon must take the order to a zero total, which is the case this test covers.' );
		$this->assertTrue( $order->needs_payment(), 'Baseline: the filter must make the zero-total order payable through pay-for-order.' );
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its destination matches the customer.' );

		WC()->customer->set_shipping_country( 'GB' );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), 'A zero-total order that can be paid must get the same destination check as a priced one.' );
	}

	/**
	 * @testdox is_valid_draft_order() lets a placed order created via $created_via with billing country $billing_country take a first address only if it has none and did not come from the Store API.
	 * @testWith ["admin", "", true]
	 *           ["store-api", "", false]
	 *           ["admin", "US", false]
	 *
	 * @param string $created_via     Where the order was created.
	 * @param string $billing_country Billing country left on the order; any country means it already has an address.
	 * @param bool   $reused          Whether the order should still be reused.
	 */
	public function test_order_without_address_takes_its_first_one_unless_from_the_store_api( string $created_via, string $billing_country, bool $reused ): void {
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		$address = array();
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( array( 'country', 'state', 'postcode', 'city', 'address_1' ) as $field ) {
				$address[ "{$type}_{$field}" ] = '';
			}
		}
		$order->set_props( $address );
		$order->set_billing_country( $billing_country );
		$order->set_created_via( $created_via );
		$order->save();

		$this->assertSame( $billing_country, $order->get_billing_country() . $order->get_shipping_country(), 'Baseline: the order must carry only the billing country under test.' );
		$this->assertTrue( WC()->cart->needs_shipping(), 'Baseline: the cart needs shipping, so the destination check applies.' );
		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must match so only the address checks decide.' );
		$this->assertSame( $reused, $checker->is_draft( $order ), $reused ? 'An order made outside the Store API with no address may take its first one, as on the pay-for-order page.' : 'A Store API order always has an address, so one without must not be reused for a new destination.' );
	}

	/**
	 * @testdox GET checkout keeps an order made outside the Store API that awaits its first address as the session draft, and gives it the customer's address.
	 */
	public function test_get_checkout_reuses_an_order_awaiting_its_first_address(): void {
		$order = $this->create_pending_order_for_us_customer();

		$address = array();
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( array( 'country', 'state', 'postcode', 'city', 'address_1' ) as $field ) {
				$address[ "{$type}_{$field}" ] = '';
			}
		}
		$order->set_props( $address );
		$order->set_created_via( 'admin' );
		$order->save();

		$response = $this->dispatch_store_api_request( 'POST', '/wc/store/v1/cart/update-customer', array( 'shipping_address' => (object) $this->get_fallback_shipping_address() ) );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'Baseline: the cart total must not change, so only the address checks decide reuse.' );

		$response = $this->dispatch_store_api_request( 'GET', '/wc/store/v1/checkout' );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( $order->get_id(), (int) $response->get_data()['order_id'], 'An order made outside the Store API with no address must stay the session draft, as on the pay-for-order page.' );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( array( 'GB', 'Oxford' ), array( $order->get_shipping_country(), $order->get_shipping_city() ), 'GET checkout should give the reused order the shipping address the customer entered.' );
	}

	/**
	 * @testdox GET checkout does not reuse a placed pending order once the customer moves to another destination, and leaves its address and total alone.
	 */
	public function test_get_checkout_does_not_reuse_a_placed_order_after_a_destination_change(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		$order = $this->create_pending_order_for_us_customer();
		$total = $order->get_total();

		$response = $this->dispatch_store_api_request( 'POST', '/wc/store/v1/cart/update-customer', array( 'shipping_address' => (object) $this->get_fallback_shipping_address() ) );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'Baseline: the cart total must not change, so only the destination check can stop reuse.' );

		$response = $this->dispatch_store_api_request( 'GET', '/wc/store/v1/checkout' );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 0, (int) $response->get_data()['order_id'], 'GET checkout must answer with a draft response, not the placed order.' );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'US', $order->get_shipping_country(), 'The placed order must keep the destination it was rated for.' );
		$this->assertSame( $total, $order->get_total(), 'The placed order\'s total must not change.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order once the billing address moves to another tax location on a store that taxes on billing, but not for a change that cannot alter the rate.
	 */
	public function test_placed_order_is_not_reused_when_billing_tax_location_changes(): void {
		$this->set_taxes_based_on( 'billing' );
		$this->give_customer_us_billing_address();

		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its tax location matches the customer.' );

		WC()->customer->set_billing_address_1( '456 Other Ave' );
		$this->assertTrue( $checker->is_draft( $order ), 'A changed billing street line cannot alter the tax, so the order must still be reused.' );

		// Move the billing address without recalculating, so the cart hash stays the same.
		WC()->customer->set_billing_state( 'NY' );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the tax location check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), 'A placed order whose billing tax location moved must not be reused as the session draft.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when the billing address moves but tax follows the shipping address.
	 */
	public function test_placed_order_is_reused_when_billing_moves_and_tax_follows_shipping(): void {
		$this->set_taxes_based_on( 'shipping' );
		$this->give_customer_us_billing_address();

		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();

		WC()->customer->set_billing_state( 'NY' );

		$this->assertTrue( $checker->is_draft( $order ), 'A billing change cannot alter tax worked out on the shipping address, so the order must still be reused.' );
	}

	/**
	 * @testdox GET checkout does not reuse a placed pending order once the customer's billing address moves to another tax location on a store that taxes on billing.
	 */
	public function test_get_checkout_does_not_reuse_a_placed_order_after_a_billing_tax_location_change(): void {
		$this->set_taxes_based_on( 'billing' );
		$this->give_customer_us_billing_address();
		$order = $this->create_pending_order_for_us_customer();

		// Move the customer's billing address the way a My Account address save does: without touching the cart.
		WC()->customer->set_billing_state( 'NY' );
		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'Baseline: the cart hash must still match, so only the tax location check can stop reuse.' );

		$response = $this->dispatch_store_api_request( 'GET', '/wc/store/v1/checkout' );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( 0, (int) $response->get_data()['order_id'], 'GET checkout must answer with a draft response, not the placed order.' );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'CA', $order->get_billing_state(), 'The placed order must keep the billing address it was taxed for.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order when the customer clears only the shipping country.
	 */
	public function test_placed_order_is_not_reused_when_shipping_country_is_cleared(): void {
		$checker = $this->get_draft_order_checker();

		// Billing matches the shipping country, so on the unfixed code the order's US shipping would wrongly match
		// the customer's US billing once the shipping country is cleared.
		$this->give_customer_us_billing_address();
		$order = $this->create_pending_order_for_us_customer();
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its destination matches the customer.' );

		// Clear only the shipping country, leaving billing in place. The order's shipping lines are still rated for
		// the old country, so reuse must stop rather than fall back to the matching billing destination.
		WC()->customer->set_shipping_country( '' );

		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash must stay the same so only the destination check can change the outcome.' );
		$this->assertFalse( $checker->is_draft( $order ), 'Clearing the shipping country must stop reuse; billing is not a fallback once a shipping address was set.' );
	}

	/**
	 * @testdox is_valid_draft_order() does not reuse a placed pending order when the tax location filter returns a non-array for the $side.
	 * @testWith ["order"]
	 *           ["readdressed copy"]
	 *
	 * @param string $side Which of the two compared tax locations the filter replaces with a non-array.
	 */
	public function test_placed_order_is_not_reused_when_tax_location_is_not_an_array( string $side ): void {
		$this->set_taxes_based_on( 'shipping' );

		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_us_customer();
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its tax location matches the customer.' );

		// Runs after core's own callback on this filter. The order is compared by identity; the readdressed copy is a clone.
		add_filter(
			'woocommerce_order_get_tax_location',
			static function ( $location, $filtered_order ) use ( $order, $side ) {
				$is_order = $filtered_order === $order;
				$replace  = 'order' === $side ? $is_order : ! $is_order;

				return $replace ? 'not-an-array' : $location;
			},
			PHP_INT_MAX,
			2
		);

		$this->assertFalse( $checker->is_draft( $order ), "A tax location that is not an array on the {$side} must stop reuse rather than reach locations_differ()." );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order in $country when the session fills the customer's empty billing state with the default location's state on the next request.
	 * @testWith ["GB", "SW1A 1AA"]
	 *           ["FR", "75001"]
	 *
	 * @param string $country  Country of the customer and the order: one with free-text states, one with none.
	 * @param string $postcode Postcode of the customer and the order.
	 */
	public function test_placed_order_is_reused_when_session_fills_empty_billing_state( string $country, string $postcode ): void {
		$this->set_taxes_based_on( 'billing' );
		$order = $this->create_pending_order_for_customer_without_state( $country, $postcode );

		$this->reload_customer_from_session();

		$this->assertSame( 'CA', WC()->customer->get_billing_state(), 'Baseline: the session must fill the empty billing state with the shop state.' );
		$this->assertSame( '', $order->get_billing_state(), 'Baseline: the placed order must have no billing state.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'A state the session filled in is not a move to another tax location, so the order must still be reused.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when the default state reaches the shipping address of a customer who left the state empty.
	 */
	public function test_placed_order_is_reused_when_default_state_reaches_shipping_address(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		$order = $this->create_pending_order_for_customer_without_state( 'GB', 'SW1A 1AA' );

		$this->reload_customer_from_session();
		// A client that sends the billing address as the shipping address passes the filled-in state along.
		WC()->customer->set_shipping_state( WC()->customer->get_billing_state() );

		$this->assertSame( 'CA', WC()->customer->get_shipping_state(), 'Baseline: the customer must carry the shop state as the shipping state.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'A state the session filled in is not a move to another destination, so the order must still be reused.' );
	}

	/**
	 * @testdox Checkout reuses a placed pending order when the retry sends back the billing state the session filled in.
	 */
	public function test_post_reuses_pending_order_when_request_returns_session_default_state(): void {
		$this->set_taxes_based_on( 'billing' );
		$order = $this->create_pending_order_for_customer_without_state( 'GB', 'SW1A 1AA' );

		$this->reload_customer_from_session();

		$cart          = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) )->get_data();
		$billing_state = ( (array) $cart['billing_address'] )['state'];
		$this->assertSame( 'CA', $billing_state, 'Baseline: the cart response must hand the client the filled-in billing state.' );

		$address = array(
			'address_1' => '1 High St',
			'city'      => 'Test City',
			'postcode'  => 'SW1A 1AA',
			'country'   => 'GB',
		);
		$request = $this->build_valid_post_request();
		$request->set_param( 'billing_address', array_merge( $request->get_param( 'billing_address' ), $address, array( 'state' => $billing_state ) ) );
		$request->set_param( 'shipping_address', array_merge( $request->get_param( 'shipping_address' ), $address, array( 'state' => '' ) ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( $order->get_id(), (int) $response->get_data()['order_id'], 'A retry from the same address must pay the placed order rather than place a second one.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order that took the session's default state on an earlier retry when the customer sends none.
	 */
	public function test_placed_order_is_reused_when_only_the_order_has_the_default_state(): void {
		$this->set_taxes_based_on( 'billing' );
		$order = $this->create_pending_order_for_customer_without_state( 'GB', 'SW1A 1AA' );
		$order->set_billing_state( 'CA' );
		$order->save();

		$this->assertSame( '', WC()->customer->get_billing_state(), 'Baseline: the customer must have no billing state.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'The default state is read as empty on the order too, so the order must still be reused.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when the default location changes after the session filled in the state.
	 */
	public function test_placed_order_is_reused_when_default_location_changes_after_fill(): void {
		$this->set_taxes_based_on( 'billing' );
		$order = $this->create_pending_order_for_customer_without_state( 'GB', 'SW1A 1AA' );

		$this->reload_customer_from_session();
		// As when geolocation resolves the visitor on a later request.
		add_filter(
			'woocommerce_customer_default_location_array',
			static fn() => array(
				'country' => 'GB',
				'state'   => '',
			)
		);

		$this->assertSame( 'CA', WC()->customer->get_billing_state(), 'Baseline: the customer must keep the state filled in from the shop base.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'A state filled in from the shop base is still read as empty once the default location is another one.' );
	}

	/**
	 * @testdox is_valid_draft_order() keeps reusing a placed pending order when the filled-in state comes from a filtered default location.
	 */
	public function test_placed_order_is_reused_when_filled_state_comes_from_filtered_default(): void {
		add_filter( 'woocommerce_customer_default_location', static fn() => 'US:NY' );
		$this->set_taxes_based_on( 'billing' );
		$order = $this->create_pending_order_for_customer_without_state( 'GB', 'SW1A 1AA' );

		$this->reload_customer_from_session();

		$this->assertSame( 'NY', WC()->customer->get_billing_state(), 'Baseline: the session must fill the empty billing state from the filtered default.' );
		$this->assertTrue( $this->get_draft_order_checker()->is_draft( $order ), 'A state filled in from the default location is read as empty when it is not the shop base state either.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order with no billing state when the customer in $country gets the state $state on a shop based in $base.
	 * @testWith ["US:CA", "GB", "SW1A 1AA", "KENT"]
	 *           ["US:WA", "AU", "2000", "WA"]
	 *           ["GB:KENT", "GB", "SW1A 1AA", "KENT"]
	 *
	 * @param string $base     Shop base location, which is the default customer location.
	 * @param string $country  Country of the customer and the order.
	 * @param string $postcode Postcode of the customer and the order.
	 * @param string $state    Billing state the customer gets after the order is placed.
	 */
	public function test_placed_order_is_not_reused_when_state_is_not_a_session_default( string $base, string $country, string $postcode, string $state ): void {
		$this->set_taxes_based_on( 'billing' );
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_customer_without_state( $country, $postcode, $base );

		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: the order is the session draft while its tax location matches the customer.' );

		WC()->customer->set_billing_state( $state );

		$this->assertFalse( $checker->is_draft( $order ), 'Only the default state on another country, where it is not one of its states, is read as empty; any other state is a move.' );
	}

	/**
	 * @testdox is_valid_draft_order() stops reusing a placed pending order when the customer gets the shop state and their country code is stored in lowercase.
	 */
	public function test_placed_order_is_not_reused_when_country_differs_from_base_only_in_case(): void {
		$this->set_taxes_based_on( 'billing' );
		$checker = $this->get_draft_order_checker();
		$order   = $this->create_pending_order_for_customer_without_state( 'NZ', '1010', 'NZ:AUK' );

		WC()->customer->set_billing_country( 'nz' );
		$this->assertTrue( $checker->is_draft( $order ), 'Baseline: a country code that differs only in case is the same tax location.' );

		WC()->customer->set_billing_state( 'AUK' );

		$this->assertFalse( $checker->is_draft( $order ), 'The shop state on the shop country is a real state whatever the case of the country code, so it is a move.' );
	}

	/**
	 * Gives the customer a US billing address matching the shipping address create_pending_order_for_us_customer() sets.
	 */
	private function give_customer_us_billing_address(): void {
		WC()->customer->set_billing_address_1( '123 Test St' );
		WC()->customer->set_billing_city( 'Test City' );
		WC()->customer->set_billing_state( 'CA' );
		WC()->customer->set_billing_postcode( '94016' );
		WC()->customer->set_billing_country( 'US' );
	}

	/**
	 * Dispatches a Store API request with a valid nonce.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 * @param array  $body   Body parameters.
	 * @return \WP_REST_Response
	 */
	private function dispatch_store_api_request( string $method, string $route, array $body = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( $method, $route );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Returns an object that exposes DraftOrderTrait::is_valid_draft_order() as is_draft().
	 *
	 * @return object
	 */
	private function get_draft_order_checker() {
		return new class() {
			use \Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

			/**
			 * Expose the protected check for the test.
			 *
			 * @param \WC_Order $order_object Order to check.
			 * @return bool
			 */
			public function is_draft( $order_object ): bool {
				return $this->is_valid_draft_order( $order_object );
			}
		};
	}

	/**
	 * Gives the customer a US shipping address and places a pending order for it from the cart.
	 *
	 * @return \WC_Order
	 */
	private function create_pending_order_for_us_customer(): \WC_Order {
		WC()->customer->set_shipping_address_1( '123 Test St' );
		WC()->customer->set_shipping_city( 'Test City' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '94016' );
		WC()->customer->set_shipping_country( 'US' );

		return $this->create_session_order_from_cart( OrderStatus::PENDING );
	}

	/**
	 * Gives the customer a billing and shipping address in the given country with no state and places a pending
	 * order for it from the cart, on a shop whose default customer location is its base address.
	 *
	 * @param string $country  Country code.
	 * @param string $postcode Postcode.
	 * @param string $base     Shop base location.
	 * @return \WC_Order
	 */
	private function create_pending_order_for_customer_without_state( string $country, string $postcode, string $base = 'US:CA' ): \WC_Order {
		update_option( 'woocommerce_default_country', $base );
		update_option( 'woocommerce_default_customer_address', 'base' );

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			WC()->customer->{"set_{$type}_address_1"}( '1 High St' );
			WC()->customer->{"set_{$type}_city"}( 'Test City' );
			WC()->customer->{"set_{$type}_state"}( '' );
			WC()->customer->{"set_{$type}_postcode"}( $postcode );
			WC()->customer->{"set_{$type}_country"}( $country );
		}

		return $this->create_session_order_from_cart( OrderStatus::PENDING );
	}

	/**
	 * Saves the customer to the session and reads it back, as the next request does.
	 */
	private function reload_customer_from_session(): void {
		WC()->customer->save();
		WC()->customer = new \WC_Customer( get_current_user_id(), true );
	}

	/**
	 * Apply a 100% coupon to the cart, taking its total to zero.
	 */
	private function apply_full_discount_coupon(): void {
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'full_discount_coupon' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 100 );
		$coupon->save();

		WC()->cart->apply_coupon( 'full_discount_coupon' );
	}

	/**
	 * Create an order from the cart and point the session at it, as Subscriptions does with a renewal order.
	 *
	 * @param string $status Order status.
	 * @return \WC_Order
	 */
	private function create_session_order_from_cart( string $status ): \WC_Order {
		WC()->cart->calculate_totals();

		$order = ( new OrderController() )->create_order_from_cart();
		$order->set_status( $status );
		$order->save();

		WC()->session->set( 'store_api_draft_order', $order->get_id() );

		return $order;
	}

	/**
	 * @testdox Store API checkout stores the order awaiting payment before invoking the payment gateway.
	 */
	public function test_post_sets_order_awaiting_payment_before_processing_payment(): void {
		WC()->session->set_customer_session_cookie( true );
		WC()->session->save_data();

		$order_id_during_payment = 0;
		$payment_handler         = function ( $context, $payment_result ) use ( &$order_id_during_payment ) {
			unset( $context );
			$order_id_during_payment = (int) WC()->session->get( 'order_awaiting_payment' );
			$payment_result->set_status( 'success' );
		};

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1, 2 );

		try {
			$response = rest_get_server()->dispatch( $this->build_valid_post_request() );
		} finally {
			remove_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1 );
		}

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$order_id = (int) $response->get_data()['order_id'];
		$this->assertGreaterThan( 0, $order_id, 'Checkout should create an order.' );
		$this->assertSame( $order_id, $order_id_during_payment, 'The order should be linked to the session before payment processing starts.' );
		$persisted_session_data = WC()->session->get_session_data();
		$this->assertArrayHasKey( 'order_awaiting_payment', $persisted_session_data, 'Redirect payments should persist the order link in the shopper session.' );
		$this->assertSame( $order_id, (int) $persisted_session_data['order_awaiting_payment'], 'Redirect payments should leave the persisted order linked to the shopper session.' );
	}

	/**
	 * @testdox Store API checkout remembers the order it hands to the gateway before the gateway runs.
	 */
	public function test_post_remembers_the_order_sent_to_gateway(): void {
		$remembered_during_payment = null;
		$payment_handler           = function ( $context, $payment_result ) use ( &$remembered_during_payment ) {
			unset( $context );
			$remembered_during_payment = WC()->session->get( 'order_sent_to_gateway' );
			$payment_result->set_status( 'success' );
		};

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1, 2 );

		try {
			$response = rest_get_server()->dispatch( $this->build_valid_post_request() );
		} finally {
			remove_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1 );
		}

		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( (int) $response->get_data()['order_id'], $remembered_during_payment, 'The gateway should run with its order already remembered, so a request that dies inside it can be traced back.' );
	}

	/**
	 * @testdox Each checkout request handled in the same PHP request receives its own payment data, as Store API batch subrequests do.
	 */
	public function test_payment_data_is_not_reused_across_checkout_requests_in_one_php_request(): void {
		$received_markers = array();
		$payment_handler  = function ( $context, $payment_result ) use ( &$received_markers ) {
			$received_markers[] = $context->payment_data['marker'] ?? '';
			$payment_result->set_status( 'failure' );
		};

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1, 2 );

		try {
			foreach ( array( 'ALPHA', 'BETA' ) as $marker ) {
				$request = $this->build_valid_post_request();
				$request->set_param(
					'payment_data',
					array(
						array(
							'key'   => 'marker',
							'value' => $marker,
						),
					)
				);
				$response = rest_get_server()->dispatch( $request );
				$data     = $response->get_data();
				$this->assertSame( 'failure', $data['payment_result']['payment_status'] ?? null, "Checkout {$marker} should end with the handler's failed payment: " . print_r( $data, true ) );
			}
		} finally {
			remove_action( 'woocommerce_rest_checkout_process_payment_with_context', $payment_handler, 1 );
		}

		$this->assertSame( array( 'ALPHA', 'BETA' ), $received_markers, 'The second checkout must not receive payment data from the first.' );
	}

	/**
	 * @testdox A logged in customer's VAT exemption should survive a failed checkout that saves their account.
	 */
	public function test_failed_checkout_keeps_logged_in_customer_vat_exemption() {
		$customer_id = \WC_Helper_Customer::create_customer( 'vat_exempt_retry', 'password', 'vat-exempt-retry@example.com' )->get_id();
		// Backdate the account so checkout's save gives it a newer timestamp than the session.
		update_user_meta( $customer_id, 'last_update', time() - MINUTE_IN_SECONDS );
		wp_set_current_user( $customer_id );
		WC()->session->init();
		WC()->customer = new \WC_Customer( $customer_id, true );
		WC()->customer->set_is_vat_exempt( true );
		WC()->customer->save();

		$fail_hook = function () {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'woocommerce_rest_checkout_payment_failed', 'Payment declined.', 400 );
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $fail_hook, 999 );

		$response = rest_get_server()->dispatch( $this->build_valid_post_request() );
		$this->assertEquals( 400, $response->get_status(), 'Checkout should fail per the forced-failure hook.' );

		// The session customer is saved on shutdown in a real request.
		WC()->customer->save();

		$this->assertNotSame(
			(string) WC()->session->get( 'customer' )['date_modified'],
			(string) ( new \WC_Customer( $customer_id ) )->get_date_modified( 'edit' ),
			'Checkout should have saved the account, leaving the session snapshot behind it.'
		);

		$reloaded_customer = new \WC_Customer( $customer_id, true );
		$this->assertTrue( $reloaded_customer->get_is_vat_exempt(), 'VAT exemption should survive the account save so the retry is not charged tax.' );
		$this->assertSame( '123 Test St', $reloaded_customer->get_billing_address_1(), 'The address should come from the saved account.' );
	}

	/**
	 * Build a valid checkout POST request body for use by the sample-extension tests.
	 *
	 * @return \WP_REST_Request
	 */
	private function build_valid_post_request() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => array(
					'first_name' => 'Sample',
					'last_name'  => 'Extension',
					'company'    => '',
					'address_1'  => '123 Test St',
					'address_2'  => '',
					'city'       => 'Test City',
					'state'      => 'CA',
					'postcode'   => '94016',
					'country'    => 'US',
					'email'      => 'sample-ext@example.com',
					'phone'      => '5555555555',
				),
				'shipping_address' => array(
					'first_name' => 'Sample',
					'last_name'  => 'Extension',
					'company'    => '',
					'address_1'  => '123 Test St',
					'address_2'  => '',
					'city'       => 'Test City',
					'state'      => 'CA',
					'postcode'   => '94016',
					'country'    => 'US',
					'phone'      => '5555555555',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
				'expected_total'   => '3000',
			)
		);
		return $request;
	}

	/**
	 * @testdox Checkout route does not resolve the available payment gateways when the request carries no payment method.
	 */
	public function test_get_request_payment_method_skips_gateway_resolution_when_missing() {
		$schema_controller = new SchemaController( $this->mock_extend );
		$sut               = new CheckoutRoute( $schema_controller, $schema_controller->get( 'checkout' ) );

		$gateway_resolution_count = 0;
		$counter                  = function ( $gateways ) use ( &$gateway_resolution_count ) {
			++$gateway_resolution_count;
			return $gateways;
		};
		add_filter( 'woocommerce_available_payment_gateways', $counter );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		$method = new \ReflectionMethod( CheckoutRoute::class, 'get_request_payment_method' );
		$method->setAccessible( true );

		try {
			$result = $method->invoke( $sut, $request );
		} finally {
			remove_filter( 'woocommerce_available_payment_gateways', $counter );
		}

		$this->assertNull( $result, 'No payment method should resolve to a null gateway.' );
		$this->assertSame( 0, $gateway_resolution_count, 'Available payment gateways must not be resolved when no payment method is supplied.' );
	}

	/**
	 * @testdox Checkout-order route does not resolve the available payment gateways when the request carries no payment method and the order needs no payment.
	 */
	public function test_order_get_request_payment_method_skips_gateway_resolution_when_missing() {
		$schema_controller = new SchemaController( $this->mock_extend );
		$sut               = new CheckoutOrderRoute( $schema_controller, $schema_controller->get( 'checkout-order' ) );

		$order = new \WC_Order();
		$order->save();

		// This scenario depends on the order not needing payment, so the empty-method branch returns null instead of throwing.
		$this->assertFalse( $order->needs_payment(), 'A zero-total order should not need payment in this scenario.' );

		$order_property = new \ReflectionProperty( CheckoutOrderRoute::class, 'order' );
		$order_property->setAccessible( true );
		$order_property->setValue( $sut, $order );

		$gateway_resolution_count = 0;
		$counter                  = function ( $gateways ) use ( &$gateway_resolution_count ) {
			++$gateway_resolution_count;
			return $gateways;
		};
		add_filter( 'woocommerce_available_payment_gateways', $counter );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout/' . $order->get_id() );

		$method = new \ReflectionMethod( CheckoutOrderRoute::class, 'get_request_payment_method' );
		$method->setAccessible( true );

		try {
			$result = $method->invoke( $sut, $request );
		} finally {
			remove_filter( 'woocommerce_available_payment_gateways', $counter );
			$order->delete( true );
		}

		$this->assertNull( $result, 'No payment method on a zero-total order should resolve to a null gateway.' );
		$this->assertSame( 0, $gateway_resolution_count, 'Available payment gateways must not be resolved when no payment method is supplied.' );
	}

	/**
	 * @testdox Should return an error response when restoring the cart session throws.
	 */
	public function test_cart_session_failure_returns_error_response() {
		wc()->session->set( 'cart', wc()->cart->get_cart_for_session() );

		// The route restores the cart only when this action has not run yet, so reset
		// the counter to put the process back into the state a REST request starts in.
		$load_action_count = $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] ?? null;
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );

		$cart_backup = WC()->cart;
		$callback    = static function () {
			throw new \RuntimeException( 'Synthetic Store API cart-session failure.' );
		};
		add_filter( 'woocommerce_get_cart_item_from_session', $callback );

		try {
			$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' ) );
		} finally {
			remove_filter( 'woocommerce_get_cart_item_from_session', $callback );
			\WC_Cart_Session::set_updates_enabled_for_cart( $cart_backup, true );
			WC()->cart = $cart_backup;
			if ( null === $load_action_count ) {
				unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action count changed by the test.
				$GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] = $load_action_count;
			}
		}

		$this->assertSame( 500, $response->get_status(), 'A cart session failure should return a Store API error response.' );
		$this->assertSame( 'woocommerce_rest_unknown_server_error', $response->get_data()['code'] );
	}

	/**
	 * Adds a payment participant that takes payment and then throws, simulating a
	 * post-payment integration (transactional email, CRM, fulfilment) failing after
	 * the money has already changed hands.
	 *
	 * Priority 998 runs before Legacy::process_legacy_payment (999), so the cart is
	 * left populated exactly as it is when a gateway dies part-way through.
	 */
	private function fail_after_payment_is_taken(): void {
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context ) {
				$context->order->payment_complete();
				throw new \Exception( 'Transactional email integration failed.' );
			},
			998
		);
	}

	/**
	 * Re-registers the checkout route so the next dispatch behaves like a separate
	 * HTTP request. Without this the route object, and the order it is holding, is
	 * carried over from the previous dispatch.
	 */
	private function simulate_fresh_request(): void {
		$schema_controller = new SchemaController( $this->mock_extend );
		$route             = new CheckoutRoute( $schema_controller, $schema_controller->get( 'checkout' ) );
		register_rest_route( $route->get_namespace(), $route->get_path(), $route->get_args(), true );
	}

	/**
	 * Builds a valid place-order request for the seeded cart.
	 */
	private function build_checkout_post_request(): \WP_REST_Request {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address'  => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
					'email'      => 'testaccount@test.com',
				),
				'shipping_address' => (object) array(
					'first_name' => 'test',
					'last_name'  => 'test',
					'company'    => '',
					'address_1'  => 'test',
					'address_2'  => '',
					'city'       => 'test',
					'state'      => '',
					'postcode'   => 'cb241ab',
					'country'    => 'GB',
					'phone'      => '',
				),
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);

		return $request;
	}

	/**
	 * @testdox A failure raised after the gateway took payment is not reported to the shopper as a failed order.
	 */
	public function test_failure_after_payment_is_taken_is_not_reported_as_a_failed_order() {
		$this->fail_after_payment_is_taken();

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertEquals(
			200,
			$response->get_status(),
			'A failure after payment was taken must not be reported as a failed checkout: ' . print_r( $data, true )
		);

		// A 200 on its own proves little: the route returns one whenever process_payment() does not
		// throw, so the payload is what shows the shopper was actually sent to their order.
		$this->assertArrayHasKey( 'payment_result', $data );
		$this->assertSame( 'success', $data['payment_result']['payment_status'], 'Recovery must report the payment as successful.' );
		$this->assertSame(
			wc_get_order( $data['order_id'] )->get_checkout_order_received_url(),
			$data['payment_result']['redirect_url'],
			'Recovery must redirect the shopper to the order they just paid for.'
		);
	}

	/**
	 * @testdox A single attempt that fails after payment leaves one order, and the shopper is sent to it.
	 */
	public function test_failure_after_payment_is_taken_leaves_the_order_paid() {
		$this->fail_after_payment_is_taken();

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$this->assertCount( 1, $orders, 'Exactly one order should exist after a single place-order attempt.' );
		$this->assertFalse( $orders[0]->needs_payment(), 'The order took payment, so it must not be left awaiting payment.' );

		// The helper pays the order itself, so the assertion above holds with or without recovery.
		// Tying the response to that same order is what shows recovery ran.
		$this->assertSame(
			$orders[0]->get_id(),
			$response->get_data()['order_id'],
			'The shopper must be sent to the order that took payment, not told to place another.'
		);
	}

	/**
	 * @testdox Retrying after a failure raised post-payment does not place a duplicate paid order.
	 */
	public function test_retry_after_failure_post_payment_does_not_duplicate_the_order() {
		$this->fail_after_payment_is_taken();

		rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$order_ids_after_first_attempt = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
				'return' => 'ids',
			)
		);

		$this->simulate_fresh_request();
		rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$order_ids_after_retry = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
				'return' => 'ids',
			)
		);

		$this->assertSame(
			$order_ids_after_first_attempt,
			$order_ids_after_retry,
			'Retrying after a post-payment failure must not create a second paid order.'
		);
	}

	/**
	 * @testdox Retrying after the request died between payment_complete() and the cart being emptied does not place a second paid order.
	 */
	public function test_retry_after_the_request_died_post_payment_does_not_duplicate_the_order() {
		// PHPUnit cannot survive a real exit, so arrange the state one leaves behind: the first
		// POST stops before payment, as an exit inside the transition would stop before the
		// catch, and the gateway's payment_complete() is then run on the order directly.
		$stop_before_payment = function () {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'woocommerce_rest_checkout_payment_failed',
				'Stands in for the request dying after payment_complete()',
				400
			);
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $stop_before_payment, 999 );

		try {
			rest_get_server()->dispatch( $this->build_checkout_post_request() );
		} finally {
			remove_action( 'woocommerce_store_api_checkout_order_processed', $stop_before_payment, 999 );
		}

		$first_order_id = (int) WC()->session->get( 'store_api_draft_order' );
		$this->assertGreaterThan( 0, $first_order_id, 'The session should point at the order the first attempt created.' );

		$first_order = wc_get_order( $first_order_id );
		$first_order->payment_complete( 'txn_died_after_payment' );

		$this->assertFalse( wc_get_order( $first_order_id )->needs_payment(), 'The gateway took payment before the request died.' );
		$this->assertFalse( WC()->cart->is_empty(), 'The request died before anything emptied the cart.' );
		$this->assertSame( $first_order_id, (int) WC()->session->get( 'store_api_draft_order' ), 'payment_complete() leaves the Store API pointer in place.' );

		$this->simulate_fresh_request();
		$retry = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$order_ids_after_retry = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
				'return' => 'ids',
			)
		);

		$this->assertSame(
			array( $first_order_id ),
			$order_ids_after_retry,
			'Retrying after the request died post-payment must not create a second paid order. Retry answered: ' . print_r( $retry->get_data(), true )
		);
		$this->assertSame( $first_order_id, $retry->get_data()['order_id'], 'The retry should answer with the order that was already paid.' );
		$this->assertSame( 'success', $retry->get_data()['payment_result']['payment_status'] );
		$this->assertSame( wc_get_order( $first_order_id )->get_checkout_order_received_url(), $retry->get_data()['payment_result']['redirect_url'], 'The shopper should be sent to the order received page of the paid order.' );
	}

	/**
	 * @testdox Retrying after the request died post-payment is answered with the paid order, not a stock error, when that order took the last unit.
	 */
	public function test_retry_after_the_paid_order_took_the_last_unit_is_answered_with_that_order() {
		// Its own product rather than a class fixture, so enabling stock management here cannot
		// leak into the other tests in this class.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'no' );
		$product->save();
		update_option( 'woocommerce_manage_stock', 'yes' );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Same arrangement as the test above: the first POST stops before payment, then the gateway's
		// payment_complete() runs on the order directly, which takes the last unit.
		$stop_before_payment = function () {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'woocommerce_rest_checkout_payment_failed',
				'Stands in for the request dying after payment_complete()',
				400
			);
		};
		add_action( 'woocommerce_store_api_checkout_order_processed', $stop_before_payment, 999 );

		try {
			rest_get_server()->dispatch( $this->build_checkout_post_request() );
		} finally {
			remove_action( 'woocommerce_store_api_checkout_order_processed', $stop_before_payment, 999 );
		}

		$first_order_id = (int) WC()->session->get( 'store_api_draft_order' );
		wc_get_order( $first_order_id )->payment_complete( 'txn_took_the_last_unit' );
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_stock_quantity(), 'The paid order took the last unit.' );

		// The retry is a new request, so its cart holds the product as it is now: out of stock.
		$this->simulate_fresh_request();
		WC()->cart->get_cart_from_session();
		$retry = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertEquals( 200, $retry->get_status(), 'The shopper should be sent to the order, not told the product sold out: ' . print_r( $retry->get_data(), true ) );
		$this->assertSame( $first_order_id, $retry->get_data()['order_id'], 'The retry should answer with the order that took the last unit.' );
		$this->assertSame( 'success', $retry->get_data()['payment_result']['payment_status'] );
	}

	/**
	 * @testdox A recovered checkout empties the cart the gateway never got to.
	 */
	public function test_recovered_checkout_empties_the_cart() {
		$this->fail_after_payment_is_taken();

		rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertTrue( WC()->cart->is_empty(), 'Recovery should leave the shopper with an empty cart, as a successful checkout does.' );
	}

	/**
	 * @testdox A recovered checkout leaves a cart that no longer belongs to the order alone.
	 */
	public function test_recovered_checkout_keeps_a_cart_that_no_longer_matches_the_order() {
		// Pay-for-order runs through the same trait with a cart unrelated to the order, and a
		// shopper checking out in a second tab has moved the cart on. Model that by changing the
		// cart after the order was built from it, so the hashes no longer agree.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context ) {
				$context->order->payment_complete();
				WC()->cart->add_to_cart( $this->products[0]->get_id(), 1 );
				throw new \Exception( 'Transactional email integration failed.' );
			},
			998
		);

		rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertFalse( WC()->cart->is_empty(), 'A cart that no longer matches the order must survive recovery.' );
	}

	/**
	 * @testdox A failure raised after the gateway has run is recovered too.
	 */
	public function test_failure_raised_after_the_gateway_ran_is_recovered() {
		// Priority 1000 runs after Legacy::process_legacy_payment (999), so BACS has really
		// processed the payment and moved the order on before this throws.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function () {
				throw new \Exception( 'Transactional email integration failed.' );
			},
			1000
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertEquals(
			200,
			$response->get_status(),
			'A failure after the gateway ran must not be reported as a failed checkout: ' . print_r( $response->get_data(), true )
		);

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$this->assertCount( 1, $orders, 'Exactly one order should exist.' );
		$this->assertFalse( $orders[0]->needs_payment(), 'BACS moved the order on, so it must not be left awaiting payment.' );
	}

	/**
	 * @testdox A recovered checkout records what went wrong on the order.
	 */
	public function test_recovered_checkout_records_the_failure_on_the_order() {
		$this->fail_after_payment_is_taken();

		rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$notes  = wc_get_order_notes( array( 'order_id' => $orders[0]->get_id() ) );
		$notes  = wp_list_pluck( $notes, 'content' );

		$this->assertNotEmpty(
			array_filter(
				$notes,
				function ( $note ) {
					return false !== strpos( $note, 'Transactional email integration failed.' );
				}
			),
			'The merchant needs a trace of the failure on the order, since the shopper was told the checkout succeeded. Notes: ' . print_r( $notes, true )
		);
	}

	/**
	 * Failures an extension can raise while the order is still a draft.
	 *
	 * @return array<string, array{\Throwable}>
	 */
	public function provider_draft_order_failures() {
		return array(
			'ordinary exception' => array( new \Exception( 'Extension failed while the order was still a draft.' ) ),
			'engine error'       => array( new \TypeError( 'Extension raised an engine error while the order was still a draft.' ) ),
		);
	}

	/**
	 * @testdox A failure while the order is still a draft releases the stock it had reserved: $_dataName.
	 * @dataProvider provider_draft_order_failures
	 *
	 * @param \Throwable $failure The failure raised while the order is still a draft.
	 */
	public function test_failure_before_the_order_leaves_draft_releases_held_stock( \Throwable $failure ) {
		// Its own product rather than a class fixture, so enabling stock management here cannot
		// leak into the other tests in this class.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->save();

		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_hold_stock_minutes', 60 );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );

		// The only extension hooks between wc_reserve_stock_for_order() and update_status( 'pending' )
		// are deprecated ones, and that window is exactly what this test covers, so the notice is
		// expected rather than a signal to hook something else.
		$this->setExpectedDeprecated( 'woocommerce_blocks_checkout_order_processed' );

		// Fires while the order is still checkout-draft and holding stock.
		$state_at_failure = null;
		add_action(
			'woocommerce_blocks_checkout_order_processed',
			function () use ( &$state_at_failure, $product, $failure ) {
				$draft_ids = wc_get_orders(
					array(
						'limit'  => 1,
						'status' => 'checkout-draft',
						'return' => 'ids',
					)
				);

				$state_at_failure = array(
					'held'   => (int) wc_get_held_stock_quantity( wc_get_product( $product->get_id() ) ),
					'status' => $draft_ids ? wc_get_order( $draft_ids[0] )->get_status() : 'none',
				);
				throw $failure;
			}
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertEquals( 500, $response->get_status(), 'The checkout should still be reported as failed: ' . print_r( $response->get_data(), true ) );

		// Without these the test could pass for the wrong reason: no hold placed, or the order
		// already past checkout-draft by the time the failure lands.
		$this->assertSame( 2, $state_at_failure['held'], 'The order must actually be holding stock when the failure happens, or this test proves nothing.' );
		$this->assertSame( 'checkout-draft', $state_at_failure['status'], 'The failure must land while the order is still a draft, which is the window this test covers.' );

		$this->assertSame(
			0,
			(int) wc_get_held_stock_quantity( wc_get_product( $product->get_id() ) ),
			'A draft order that never took payment must not keep its stock hold when the checkout fails.'
		);
	}

	/**
	 * @testdox An Error raised before any payment was taken is reported as a failed checkout.
	 */
	public function test_error_raised_before_payment_is_not_converted_into_a_successful_checkout() {
		// No payment_complete() here: the order is still awaiting payment when this lands, so the
		// recovery path must not claim it, and the Error must reach the client as a failure.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function () {
				// Raises Error: call to a member function on null.
				$integration = null;
				$integration->push_order();
			},
			998
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertSame( 500, $response->get_status(), 'An Error with no payment taken must be reported as a failed checkout: ' . print_r( $response->get_data(), true ) );
		$this->assertSame( 'woocommerce_rest_unknown_server_error', $response->get_data()['code'] );

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$this->assertCount( 1, $orders, 'Exactly one order should exist.' );
		$this->assertTrue( $orders[0]->has_status( OrderStatus::PENDING ), 'The order never took payment, so it must be left awaiting payment.' );
	}

	/**
	 * Checkout failures and whether the debug log should survive each one.
	 *
	 * @return array<string, array{\Throwable, int, string, bool}>
	 */
	public function provider_checkout_failures_and_expected_debug_log_outcome(): array {
		return array(
			'unexpected server error'                  => array(
				new \Error( 'Extension raised an engine error while taking payment.' ),
				500,
				'woocommerce_rest_unknown_server_error',
				true,
			),
			'ordinary client error (declined payment)' => array(
				new \Exception( 'Card declined.' ),
				400,
				'woocommerce_rest_checkout_process_payment_error',
				false,
			),
		);
	}

	/**
	 * @testdox The place-order debug log is kept only for an unexpected server error, not an ordinary client error: $_dataName.
	 * @dataProvider provider_checkout_failures_and_expected_debug_log_outcome
	 *
	 * @param \Throwable $failure         The failure raised while the gateway is taking payment.
	 * @param int        $expected_status The HTTP status the checkout should fail with.
	 * @param string     $expected_code   The REST error code the checkout should fail with.
	 * @param bool       $log_is_kept     Whether the debug log trail should survive this failure.
	 */
	public function test_debug_log_kept_only_for_unexpected_server_errors( \Throwable $failure, int $expected_status, string $expected_code, bool $log_is_kept ) {
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function () use ( $failure ) {
				throw $failure;
			},
			998
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertSame( $expected_status, $response->get_status(), print_r( $response->get_data(), true ) );
		$this->assertSame( $expected_code, $response->get_data()['code'] );

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$this->assertCount( 1, $orders, 'Exactly one order should exist.' );

		if ( $log_is_kept ) {
			$this->assertStringStartsWith( 'place-order-debug-', $orders[0]->get_meta( '_debug_log_source' ), 'The debug log trail must be kept for an unexpected server error, not cleared.' );
			$this->assertEmpty( $orders[0]->get_meta( '_debug_log_source_pending_deletion' ), 'The debug log must not be scheduled for deletion on an unexpected server error.' );
		} else {
			$this->assertEmpty( $orders[0]->get_meta( '_debug_log_source' ), 'The debug log trail must be cleared for an ordinary client error.' );
			$this->assertStringStartsWith( 'place-order-debug-', $orders[0]->get_meta( '_debug_log_source_pending_deletion' ), 'The debug log must be scheduled for deletion on an ordinary client error.' );
		}
	}

	/**
	 * @testdox The place-order debug log is kept when a gateway reports an error without throwing.
	 */
	public function test_debug_log_kept_when_gateway_reports_error_without_throwing() {
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context, &$payment_result ) {
				$payment_result->set_status( 'error' );
			},
			998,
			2
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertSame( 500, $response->get_status(), print_r( $response->get_data(), true ) );

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'status' => 'any',
			)
		);
		$this->assertCount( 1, $orders, 'Exactly one order should exist.' );

		$this->assertStringStartsWith( 'place-order-debug-', $orders[0]->get_meta( '_debug_log_source' ), 'The debug log trail must be kept when a gateway reports an error, not cleared.' );
		$this->assertEmpty( $orders[0]->get_meta( '_debug_log_source_pending_deletion' ), 'The debug log must not be scheduled for deletion when a gateway reports an error.' );
	}

	/**
	 * @testdox An Error raised inside a status transition after the gateway moved the order on is recovered.
	 */
	public function test_error_raised_inside_a_status_transition_after_the_gateway_ran_is_recovered() {
		// Core catches Exception, not Error, around status-transition hooks, so an integration
		// dying there is the failure that escapes the gateway and reaches the route. BACS moves
		// the order on-hold before it empties the cart, so this lands between the two, as the
		// report did. This is the test that fails if the catch narrows back to \Exception.
		add_action(
			'woocommerce_order_status_on-hold',
			function () {
				// Raises Error: call to a member function on null.
				$integration = null;
				$integration->push_order();
			}
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'An Error after the gateway moved the order on must be recovered, not surfaced: ' . print_r( $data, true ) );
		$this->assertSame( 'success', $data['payment_result']['payment_status'], 'Recovery must report the payment as successful.' );

		$order = wc_get_order( $data['order_id'] );
		$this->assertTrue( $order->has_status( OrderStatus::ON_HOLD ), 'The gateway had already moved the order on when the Error was raised.' );
		$this->assertTrue( WC()->cart->is_empty(), 'The gateway never reached empty_cart(), so recovery must empty the cart.' );

		$notes = wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' );
		$this->assertNotEmpty(
			array_filter(
				$notes,
				function ( $note ) {
					return false !== strpos( $note, 'push_order() on null' );
				}
			),
			'The Error must be recorded on the order. Notes: ' . print_r( $notes, true )
		);
	}

	/**
	 * @testdox A failure while the order sits in a custom status the site declares payable is reported, not recovered.
	 */
	public function test_failure_in_a_custom_payable_status_is_not_recovered() {
		// Short on purpose: the status column holds twenty characters, prefix included.
		$status_name = 'awaiting_review';
		$this->register_custom_order_status( $status_name, true );

		// A gateway that parks the order in the custom status and then declines. No payment was
		// taken, so the status must read as awaiting payment rather than as moved past it.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context ) use ( $status_name ) {
				$context->order->update_status( $status_name );
				throw new \Exception( 'Your card was declined.' );
			},
			998
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertEquals( 400, $response->get_status(), 'A decline while the order is awaiting payment must be reported: ' . print_r( $response->get_data(), true ) );
		$this->assertSame( 'woocommerce_rest_checkout_process_payment_error', $response->get_data()['code'] );
		$this->assertFalse( WC()->cart->is_empty(), 'The cart must survive so the shopper can retry.' );
	}

	/**
	 * Gateways read the redirect back out of payment_details on the client rather than out of
	 * redirect_url, so a recovered result has to carry it in both. WooPayments reads
	 * paymentDetails.redirect and calls String.match() on it, which throws on undefined and
	 * strands the shopper on the checkout with a JavaScript error even though the recovery
	 * itself succeeded.
	 */
	public function test_recovery_reports_the_redirect_in_the_payment_details() {
		$this->fail_after_payment_is_taken();

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), print_r( $data, true ) );
		$this->assertSame( 'success', $data['payment_result']['payment_status'] );

		$details = wp_list_pluck( $data['payment_result']['payment_details'], 'value', 'key' );

		$this->assertArrayHasKey(
			'redirect',
			$details,
			'A success result without a redirect in payment_details breaks gateway client code that reads it from there.'
		);
		$this->assertSame(
			$data['payment_result']['redirect_url'],
			$details['redirect'],
			'The redirect reported in payment_details must match the one in redirect_url.'
		);
	}

	/**
	 * A redirect the gateway set before failing is kept in payment_details too, rather than
	 * being replaced with the order confirmation.
	 */
	public function test_recovery_reports_a_gateway_set_redirect_in_the_payment_details() {
		$gateway_redirect = 'https://example.com/3ds-challenge';

		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context, &$payment_result ) use ( $gateway_redirect ) {
				$context->order->update_status( OrderStatus::ON_HOLD );
				$payment_result->set_redirect_url( $gateway_redirect );
				throw new \Exception( 'Gateway failed after parking the order for authentication.' );
			},
			998,
			2
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();
		$details  = wp_list_pluck( $data['payment_result']['payment_details'], 'value', 'key' );

		$this->assertSame( $gateway_redirect, $details['redirect'] );
	}

	/**
	 * @testdox Recovery keeps a redirect the gateway set before it failed.
	 */
	public function test_recovery_keeps_a_redirect_the_gateway_already_set() {
		$gateway_redirect = 'https://example.com/3ds-challenge';

		// Parks the order on-hold with an authentication step still outstanding, sets the redirect
		// that step needs, then fails. The shopper still has to complete the challenge.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context, &$payment_result ) use ( $gateway_redirect ) {
				$context->order->update_status( OrderStatus::ON_HOLD );
				$payment_result->set_redirect_url( $gateway_redirect );
				throw new \Exception( 'Gateway failed after parking the order for authentication.' );
			},
			998,
			2
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'The order moved past awaiting payment, so the checkout is recovered: ' . print_r( $data, true ) );
		$this->assertSame( 'success', $data['payment_result']['payment_status'] );
		$this->assertSame(
			$gateway_redirect,
			$data['payment_result']['redirect_url'],
			'Overwriting the gateway redirect would walk the shopper past the step it was pointing at.'
		);
	}

	/**
	 * @testdox A failure on a fully discounted order still releases its stock and coupon holds.
	 */
	public function test_failure_on_a_zero_total_order_releases_held_stock_and_coupons() {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10' );
		$product->set_virtual( true );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->save();

		$coupon = new \WC_Coupon();
		$coupon->set_code( 'zero_total_coupon' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 100 );
		$coupon->set_usage_limit( 1 );
		$coupon->save();

		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_hold_stock_minutes', 60 );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->apply_coupon( 'zero_total_coupon' );

		$state_at_failure = null;
		add_action(
			'woocommerce_store_api_checkout_order_processed',
			function ( $order ) use ( &$state_at_failure, $product ) {
				$state_at_failure = array(
					'total'  => (float) $order->get_total(),
					'status' => $order->get_status(),
					'held'   => (int) wc_get_held_stock_quantity( wc_get_product( $product->get_id() ) ),
					'coupon' => $order->get_meta( '_coupon_held_keys' ),
				);
				throw new \Exception( 'Extension failed on a fully discounted order.' );
			}
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );

		$this->assertEquals( 500, $response->get_status(), 'The checkout should be reported as failed: ' . print_r( $response->get_data(), true ) );

		// Preconditions: without these the release assertions could pass for the wrong reason.
		$this->assertSame( 0.0, $state_at_failure['total'], 'The coupon must take the order to a zero total, which is the case this test covers.' );
		$this->assertSame( 2, $state_at_failure['held'], 'The order must actually be holding stock when the failure happens.' );
		$this->assertNotEmpty( $state_at_failure['coupon'], 'The order must actually be holding the coupon when the failure happens.' );

		$this->assertSame(
			0,
			(int) wc_get_held_stock_quantity( wc_get_product( $product->get_id() ) ),
			'A zero-total order that never took payment must not keep its stock hold when the checkout fails.'
		);

		$orders = wc_get_orders(
			array(
				'limit'  => 1,
				'status' => 'pending',
				'return' => 'ids',
			)
		);
		$this->assertNotEmpty( $orders, 'The failed order should still exist to inspect.' );
		$this->assertEmpty(
			wc_get_order( $orders[0] )->get_meta( '_coupon_held_keys' ),
			'A zero-total order that never took payment must release its coupon hold, or the shopper cannot retry with the same coupon.'
		);
	}

	/**
	 * @testdox Recovery survives a gateway that replaces the payment result with an invalid value.
	 */
	public function test_recovery_handles_a_gateway_that_discards_the_payment_result() {
		// Takes payment, then hands back something that is not a PaymentResult. The route rejects
		// the result, and recovery has to cope with the replacement rather than fatal on it.
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			function ( $context, &$payment_result ) {
				$context->order->payment_complete();
				$payment_result = null;
			},
			998,
			2
		);

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertEquals(
			200,
			$response->get_status(),
			'A discarded payment result must not turn a paid order into a fatal: ' . print_r( $data, true )
		);
		$this->assertArrayHasKey( 'payment_result', $data );
		$this->assertSame( 'success', $data['payment_result']['payment_status'], 'Recovery must still report success on the result the response uses.' );
	}

	/**
	 * @testdox A legacy gateway that adds an error notice and returns nothing shows that notice to the shopper.
	 */
	public function test_legacy_gateway_returning_nothing_shows_its_error_notice() {
		$this->use_bacs_gateway_returning_nothing( 'The test gateway declined the card.' );

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status(), 'Unexpected response: ' . print_r( $data, true ) );
		$this->assertSame( 'woocommerce_rest_checkout_process_payment_error', $data['code'] );
		$this->assertSame( 'The test gateway declined the card.', $data['message'] );
	}

	/**
	 * @testdox A legacy gateway that returns nothing without a notice ends the checkout as a failed payment.
	 */
	public function test_legacy_gateway_returning_nothing_without_a_notice_fails_the_payment() {
		$this->use_bacs_gateway_returning_nothing( null );

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status(), 'Unexpected response: ' . print_r( $data, true ) );
		$this->assertArrayHasKey( 'payment_result', $data );
		$this->assertSame( 'failure', $data['payment_result']['payment_status'] );
	}

	/**
	 * @testdox A legacy gateway that takes payment and then returns nothing still completes the checkout.
	 */
	public function test_legacy_gateway_returning_nothing_after_taking_payment_completes_the_checkout() {
		$this->use_bacs_gateway_returning_nothing( 'An error the paid shopper should not see later.', true );

		$response = rest_get_server()->dispatch( $this->build_checkout_post_request() );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Unexpected response: ' . print_r( $data, true ) );
		$this->assertArrayHasKey( 'payment_result', $data );
		$this->assertSame( 'success', $data['payment_result']['payment_status'] );
		$this->assertSame( 0, wc_notice_count( 'error' ), 'The gateway notice should not stay queued for a later page.' );

		$notes = wp_list_pluck( wc_get_order_notes( array( 'order_id' => $data['order_id'] ) ), 'content' );
		$this->assertNotEmpty(
			array_filter(
				$notes,
				function ( $note ) {
					return false !== strpos( $note, 'The bacs payment method returned no result.' );
				}
			),
			'The order note should name the gateway that returned no result.'
		);
	}

	/**
	 * Replaces BACS with a gateway whose process_payment() returns nothing, as legacy gateways do when they bail out early.
	 *
	 * @param string|null $notice           Error notice the gateway adds before returning, or null for none.
	 * @param bool        $complete_payment Whether the gateway marks the order paid before returning.
	 */
	private function use_bacs_gateway_returning_nothing( ?string $notice, bool $complete_payment = false ): void {
		$gateway = new class( $notice, $complete_payment ) extends WC_Gateway_BACS {
			/**
			 * Error notice to add, or null for none.
			 *
			 * @var string|null
			 */
			private $notice;

			/**
			 * Whether to mark the order paid before returning.
			 *
			 * @var bool
			 */
			private $complete_payment;

			/**
			 * Constructor.
			 *
			 * @param string|null $notice           Error notice to add, or null for none.
			 * @param bool        $complete_payment Whether to mark the order paid before returning.
			 */
			public function __construct( ?string $notice, bool $complete_payment ) {
				parent::__construct();
				$this->notice           = $notice;
				$this->complete_payment = $complete_payment;
			}

			/**
			 * Marks the order paid and adds the error notice when asked, then returns nothing.
			 *
			 * @param int $order_id Order ID.
			 */
			public function process_payment( $order_id ) {
				if ( $this->complete_payment ) {
					wc_get_order( $order_id )->payment_complete();
				}
				if ( null !== $this->notice ) {
					wc_add_notice( $this->notice, 'error' );
				}
			}
		};

		foreach ( WC()->payment_gateways()->payment_gateways as $key => $existing ) {
			if ( WC_Gateway_BACS::ID === $existing->id ) {
				WC()->payment_gateways()->payment_gateways[ $key ] = $gateway;
			}
		}
	}
}
