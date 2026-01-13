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
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as CheckoutRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use WC_Gateway_BACS;

/**
 * Checkout Controller Tests.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WooCommerce.Commenting.CommentHooks.MissingHookComment
 */
class Checkout extends MockeryTestCase {

	const TEST_COUPON_CODE = 'test_coupon_code';
	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Set jetpack_activation_source option to prevent "Cannot use bool as array" error
		// in Jetpack Connection Manager's apply_activation_source_to_args method.
		update_option( 'jetpack_activation_source', array( '', '' ) );

		add_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ), 10, 4 );

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		wp_set_current_user( 0 );

		$coupon = new \WC_Coupon();
		$coupon->set_code( self::TEST_COUPON_CODE );
		$coupon->set_amount( 2 );
		$coupon->save();

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

		$fixtures = new FixtureData();
		$fixtures->payments_enable_bacs();
		$fixtures->shipping_add_pickup_location();
		$fixtures->shipping_add_flat_rate_instance();

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
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Virtual Test Product 2',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
					'virtual'       => true,
				)
			),
		);
		wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

	/**
	 * Tear down Rest API server.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		remove_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ) );

		remove_all_filters( 'woocommerce_get_country_locale' );
		remove_all_filters( 'woocommerce_register_shop_order_post_statuses' );
		remove_all_filters( 'wc_order_statuses' );
		remove_all_actions( 'woocommerce_checkout_validate_order_before_payment' );
		remove_all_actions( 'woocommerce_store_api_checkout_order_processed' );
		remove_all_actions( 'woocommerce_valid_order_statuses_for_payment' );

		delete_option( 'woocommerce_feature_fraud_protection_enabled' );
		delete_option( 'jetpack_activation_source' );
		wc_get_container()->get( SessionClearanceManager::class )->reset_session();

		update_option( 'woocommerce_ship_to_countries', 'all' );
		update_option( 'woocommerce_allowed_countries', 'all' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );

		$fixtures = new FixtureData();
		$fixtures->shipping_remove_pickup_location();
		$fixtures->shipping_remove_methods_from_default_zone();

		$coupon_to_delete = new \WC_Coupon( self::TEST_COUPON_CODE );
		$coupon_to_delete->delete( true );

		$customer_to_delete = get_user_by( 'email', 'testaccount@test.com' );
		if ( $customer_to_delete ) {
			wp_delete_user( $customer_to_delete->ID );
		}

		unset( WC()->countries->locale );
		WC()->cart->empty_cart();
		WC()->session->destroy_session();

		$GLOBALS['wp_rest_server'] = null;
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
		$this->assertEquals( 200, $response->get_status() );
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

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
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

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$order = wc_get_order( $response->get_data()['order_id'] );

		$checkout_fields           = Package::container()->get( CheckoutFields::class );
		$additional_fields_address = $checkout_fields->get_order_additional_fields_with_values( $order, 'address', 'other', 'view' );
		$additional_fields_contact = $checkout_fields->get_order_additional_fields_with_values( $order, 'contact', 'other', 'view' );
		$additional_fields_order   = $checkout_fields->get_order_additional_fields_with_values( $order, 'order', 'other', 'view' );

		// Verify that address fields are not updated, but contact and order fields are.
		$this->assertArrayNotHasKey( 'plugin-namespace/student-id', $additional_fields_address );
		$this->assertEquals( 'engineering', $additional_fields_contact['plugin-namespace/job-function']['value'] );
		$this->assertEquals( true, $additional_fields_order['plugin-namespace/leave-on-porch']['value'] );
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
		$this->assertStringContainsString( 'Sorry, we do not allow orders from the provided country (FR)', $response->get_data()['message'] );
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
	 * Test that checkout is blocked when fraud protection blocks the session.
	 */
	public function test_checkout_blocked_when_session_blocked() {
		// Enable fraud protection and block the session.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );
		wc_get_container()->get( SessionClearanceManager::class )->block_session();

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

		$this->assertEquals( 403, $response->get_status(), 'Should return 403 when session is blocked' );
		$this->assertEquals( 'woocommerce_rest_checkout_error', $response->get_data()['code'] );
		$this->assertStringContainsString( 'unable to process this request online', $response->get_data()['message'] );
		$this->assertStringContainsString( 'to complete your purchase', $response->get_data()['message'], 'Should use checkout-specific message' );
	}
}
