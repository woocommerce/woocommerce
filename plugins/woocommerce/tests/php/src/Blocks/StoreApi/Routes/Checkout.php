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
use Automattic\WooCommerce\StoreApi\SchemaController;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\MockSessionHandler;
use Mockery\Adapter\Phpunit\MockeryTestCase;

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

		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$coupon = new \WC_Coupon();
		$coupon->set_code( self::TEST_COUPON_CODE );
		$coupon->set_amount( 2 );
		$coupon->save();

		wp_set_current_user( 0 );
		$customer = get_user_by( 'email', 'testaccount@test.com' );

		if ( $customer ) {
			wp_delete_user( $customer->ID );
		}

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

		// Add a flat rate to the default zone.
		$flat_rate    = WC()->shipping()->get_shipping_methods()['flat_rate'];
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		$default_zone->add_shipping_method( $flat_rate->id );
		$default_zone->save();

		$fixtures->payments_enable_bacs();
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
					'name'          => 'Virtual Test Product 2',
					'stock_status'  => 'instock',
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
		unset( WC()->countries->locale );
		$default_zone     = \WC_Shipping_Zones::get_zone( 0 );
		$shipping_methods = $default_zone->get_shipping_methods();
		foreach ( $shipping_methods as $method ) {
			$default_zone->delete_shipping_method( $method->instance_id );
		}
		$default_zone->save();
		remove_all_filters( 'woocommerce_get_country_locale' );

		$coupon_to_delete = new \WC_Coupon( self::TEST_COUPON_CODE );
		$coupon_to_delete->delete( true );

		global $wp_rest_server;
		$wp_rest_server = null;
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
				'payment_method'   => 'bacs',
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
		WC()->cart->empty_cart();
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
				'payment_method'   => 'bacs',
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
		$product->set_stock_status( 'outofstock' );
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}
	/**
	 * Check that accounts are created on request.
	 */
	public function test_checkout_create_account() {
		// We need to replace the WC_Session with a mock because this test relies on cookies being set which
		// is not easy with PHPUnit. This is a simpler approach.
		$old_session  = WC()->session;
		WC()->session = new MockSessionHandler();
		WC()->session->init();

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
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
				'create_account'   => true,
				'payment_method'   => 'bacs',
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

		$this->assertEquals( $status, 200, print_r( $data, true ) );
		$this->assertTrue( $data['customer_id'] > 0 );

		$customer = get_user_by( 'id', $data['customer_id'] );
		$this->assertEquals( $customer->user_email, 'testaccount@test.com' );

		// Return WC_Session to original state.
		WC()->session = $old_session;
	}

	/**
	 * Test account creation options.
	 */
	public function test_checkout_do_not_create_account() {
		// We need to replace the WC_Session with a mock because this test relies on cookies being set which
		// is not easy with PHPUnit. This is a simpler approach.
		$old_session  = WC()->session;
		WC()->session = new MockSessionHandler();
		WC()->session->init();

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
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
				'create_account'   => false,
				'payment_method'   => 'bacs',
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

		$this->assertEquals( $status, 200 );
		$this->assertEquals( $data['customer_id'], 0 );

		// Return WC_Session to original state.
		WC()->session = $old_session;
	}

	/**
	 * Test account creation options.
	 */
	public function test_checkout_force_create_account() {
		// We need to replace the WC_Session with a mock because this test relies on cookies being set which
		// is not easy with PHPUnit. This is a simpler approach.
		$old_session  = WC()->session;
		WC()->session = new MockSessionHandler();
		WC()->session->init();

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
				'payment_method'   => 'bacs',
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

		// Return WC_Session to original state.
		WC()->session = $old_session;
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
				'payment_method'   => 'bacs',
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
				'payment_method'   => 'bacs',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		$this->assertEquals( 400, $status, print_r( $data, true ) );
		$this->assertEquals( 'woocommerce_rest_invalid_shipping_option', $data['code'], print_r( $data, true ) );
		$this->assertEquals( 'Sorry, this order requires a shipping option.', $data['message'], print_r( $data, true ) );
	}
}
