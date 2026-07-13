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
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WC_Gateway_BACS;

/**
 * Checkout Controller Tests.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WooCommerce.Commenting.CommentHooks.MissingHookComment
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
			remove_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ) );

			remove_all_filters( 'woocommerce_get_country_locale' );
			remove_all_filters( 'woocommerce_register_shop_order_post_statuses' );
			remove_all_filters( 'wc_order_statuses' );
			remove_all_actions( 'woocommerce_checkout_validate_order_before_payment' );
			remove_all_actions( 'woocommerce_store_api_checkout_order_processed' );
			remove_all_actions( 'woocommerce_valid_order_statuses_for_payment' );

			update_option( 'woocommerce_ship_to_countries', 'all' );
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
			}
		}
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
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), print_r( $response->get_data(), true ) );
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
		remove_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $action_callback, 'do_callback' ), 10, 2 );
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
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_address_country', $response->get_data()['code'] );
		$this->assertStringContainsString( 'Sorry, we do not allow orders from the provided country (France)', $response->get_data()['message'] );
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

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/checkout/' . $order->get_id() );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_query_params(
			array(
				'key'           => $order->get_order_key(),
				'billing_email' => $order->get_billing_email(),
			)
		);
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
		$request->set_body_params(
			array(
				'billing_address'  => array_merge( $invalid_address, array( 'email' => $order->get_billing_email() ) ),
				'shipping_address' => $invalid_address,
				'payment_method'   => WC_Gateway_BACS::ID,
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_address_country', $response->get_data()['code'] );

		$stored_order = wc_get_order( $order->get_id() );
		$this->assertEquals( $original_billing_country, $stored_order->get_billing_country() );
		$this->assertEquals( $original_shipping_country, $stored_order->get_shipping_country() );
		$this->assertEquals( $original_total, $stored_order->get_total() );
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
}
