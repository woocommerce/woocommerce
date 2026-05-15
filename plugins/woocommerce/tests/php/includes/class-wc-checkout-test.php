<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Checkout.
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Class WC_Checkout
 */
class WC_Checkout_Test extends \WC_Unit_Test_Case {

	/**
	 * @var object The system under test.
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_Checkout {
			public function validate_posted_data( &$data, &$errors ) {
				return parent::validate_posted_data( $data, $errors );
			}

			public function validate_checkout( &$data, &$errors ) {
				return parent::validate_checkout( $data, $errors );
			}

			public function should_create_customer_account( $data ) {
				return parent::should_create_customer_account( $data );
			}
		};
		// phpcs:enable Generic.CodeAnalysis, Squiz.Commenting

		WC()->cart->empty_cart();

		add_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
	}

	/**
	 * @testdox 'validate_posted_data' adds errors for non-existing billing/shipping countries.
	 *
	 * @testWith [true, true]
	 *           [false, false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 * @param bool $expect_error_message_for_shipping_country True to expect an error to be generated for the shipping country.
	 */
	public function test_validate_posted_data_adds_error_for_non_existing_country( $ship_to_different_address, $expect_error_message_for_shipping_country ) {
		$data = array(
			'billing_country'           => 'XX',
			'shipping_country'          => 'YY',
			'ship_to_different_address' => $ship_to_different_address,
		);

		add_filter(
			'woocommerce_cart_needs_shipping_address',
			function () {
				return true;
			}
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEquals( "'XX' is not a valid country code.", $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEquals(
			$expect_error_message_for_shipping_country ? "'YY' is not a valid country code." : '',
			$errors->get_error_message( 'shipping_country_validation' )
		);
	}

	/**
	 * @testdox the customer notes are correctly sanitized.
	 */
	public function test_order_notes() {
		$data = array(
			'ship_to_different_address' => false,
			'order_comments'            => '<a href="http://attackerpage.com/csrf.html">This text should not save inside an anchor.</a><script>alert("alert")</script>',
			'payment_method'            => WC_Gateway_BACS::ID,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );
		$result = $this->sut->create_order( $data );

		$content = wc_get_template_html(
			'order/order-details.php',
			array(
				'order_id'       => $result,
				'show_downloads' => false,
			)
		);
		$this->assertStringNotContainsString( '<a href="http://attackerpage.com/csrf.html">', $content );
		$this->assertStringNotContainsString( '<script>', $content );
		$this->assertStringContainsString( 'This text should not save inside an anchor.', $content );
	}

	/**
	 * @testdox the customer notes can have linebreaks.
	 */
	public function test_order_notes_linebreaks() {
		$data = array(
			'ship_to_different_address' => false,
			'order_comments'            => 'A string' . PHP_EOL . 'with linebreaks' . PHP_EOL . 'in it.',
			'payment_method'            => WC_Gateway_BACS::ID,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );
		$result = $this->sut->create_order( $data );

		$content = wc_get_template_html(
			'order/order-details.php',
			array(
				'order_id'       => $result,
				'show_downloads' => false,
			)
		);
		// The preg_replace is necessary because the template outputs a lot of whitespace, we can just make sure the <br /> tags are there as the other whitespace doesn't matter.
		$this->assertStringContainsString( 'A string<br />with linebreaks<br />in it.', preg_replace( '/[\t\n\r]+/', '', $content ) );
	}

	/**
	 * @testdox 'validate_posted_data' doesn't add errors for existing billing/shipping countries.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 */
	public function test_validate_posted_data_does_not_add_error_for_existing_country( $ship_to_different_address ) {
		$data = array(
			'billing_country'           => 'ES',
			'shipping_country'          => 'ES',
			'ship_to_different_address' => $ship_to_different_address,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty( $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEmpty( $errors->get_error_message( 'shipping_country_validation' ) );
	}

	/**
	 * @testdox 'validate_posted_data' doesn't add errors for empty billing/shipping countries.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $ship_to_different_address True to simulate shipping to a different address than the billing address.
	 */
	public function test_validate_posted_data_does_not_add_error_for_empty_country( $ship_to_different_address ) {
		$data = array(
			'billing_country'           => '',
			'shipping_country'          => '',
			'ship_to_different_address' => $ship_to_different_address,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty( $errors->get_error_message( 'billing_country_validation' ) );
		$this->assertEmpty( $errors->get_error_message( 'shipping_country_validation' ) );
	}

	/**
	 * @testdox 'validate_checkout' adds a "We don't ship to country X" error but only if the country exists.
	 *
	 * @testWith [ "XX", false ]
	 *           [ "JP", true ]
	 *
	 * @param string $country The billing/shipping country.
	 * @param bool   $expect_we_dont_ship_error True to expect a "We don't ship to X" error.
	 */
	public function test_validate_checkout_adds_we_dont_ship_error_only_if_country_exists( $country, $expect_we_dont_ship_error ) {
		add_filter(
			'woocommerce_countries_allowed_countries',
			function () {
				return array( 'ES' );
			}
		);

		add_filter(
			'woocommerce_cart_needs_shipping',
			function () {
				return true;
			}
		);

		add_filter(
			'wc_shipping_enabled',
			function () {
				return true;
			}
		);

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_get_shipping_method_count' => function ( $include_legacy = false, $enabled_only = false ) {
					return 1;
				},
			)
		);

		$data = array(
			'billing_country'           => $country,
			'shipping_country'          => $country,
			'ship_to_different_address' => false,
		);

		$errors = new WP_Error();

		$this->sut->validate_checkout( $data, $errors );

		$this->assertEquals(
			$expect_we_dont_ship_error ? 'Unfortunately, <strong>we do not ship to Japan</strong>. Please enter an alternative shipping address.' : '',
			$errors->get_error_message( 'shipping' )
		);
		remove_all_filters( 'woocommerce_countries_allowed_countries' );
	}

	/**
	 * @testdox If the WooCommerce class's customer object is null (like if WC has not been fully initialized yet),
	 *          calling WC_Checkout::get_value should not throw an error.
	 */
	public function test_get_value_no_error_on_null_customer() {
		$sut = WC_Checkout::instance();

		$orig_customer = WC()->customer;
		WC()->customer = null;

		$this->assertNull( $sut->get_value( 'billing_country' ) );

		WC()->customer = $orig_customer;
	}

	/**
	 * @testdox `should_create_customer_account` returns false when the shopper is already logged in.
	 */
	public function test_should_create_customer_account_returns_false_when_logged_in() {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( $this->sut->should_create_customer_account( array( 'createaccount' => 1 ) ) );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox `should_create_customer_account` returns false when checkout signup is disabled, even if `createaccount` is present.
	 */
	public function test_should_create_customer_account_returns_false_when_registration_disabled() {
		wp_set_current_user( 0 );

		// Override the global setUp filter that forces registration enabled.
		remove_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
		add_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );

		$this->assertFalse( $this->sut->should_create_customer_account( array( 'createaccount' => 1 ) ) );

		remove_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );
		add_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
	}

	/**
	 * @testdox `should_create_customer_account` returns true when registration is required (guest checkout disabled).
	 */
	public function test_should_create_customer_account_returns_true_when_registration_required() {
		wp_set_current_user( 0 );

		add_filter( 'woocommerce_checkout_registration_required', '__return_true' );

		$this->assertTrue( $this->sut->should_create_customer_account( array( 'createaccount' => 0 ) ) );

		remove_filter( 'woocommerce_checkout_registration_required', '__return_true' );
	}

	/**
	 * @testdox `should_create_customer_account` returns false for guests who did not tick the create-account checkbox.
	 */
	public function test_should_create_customer_account_returns_false_when_checkbox_not_ticked() {
		wp_set_current_user( 0 );

		add_filter( 'woocommerce_checkout_registration_required', '__return_false' );

		$this->assertFalse( $this->sut->should_create_customer_account( array( 'createaccount' => 0 ) ) );
		$this->assertFalse( $this->sut->should_create_customer_account( array() ) );

		remove_filter( 'woocommerce_checkout_registration_required', '__return_false' );
	}

	/**
	 * @testdox `should_create_customer_account` returns true for guests who ticked the create-account checkbox.
	 */
	public function test_should_create_customer_account_returns_true_when_checkbox_ticked() {
		wp_set_current_user( 0 );

		add_filter( 'woocommerce_checkout_registration_required', '__return_false' );

		$this->assertTrue( $this->sut->should_create_customer_account( array( 'createaccount' => 1 ) ) );

		remove_filter( 'woocommerce_checkout_registration_required', '__return_false' );
	}

	/**
	 * @testdox Checkout page contains login form for guests.
	 */
	public function test_checkout_page_contains_login_form_for_guests() {
		// Ensure the user is logged out.
		wp_logout();

		// Add a product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		// Simulate visiting the checkout page.
		ob_start();
		echo do_shortcode( '[woocommerce_checkout]' );
		$output = ob_get_clean();

		// Assert that the login form is present.
		$this->assertStringContainsString( 'woocommerce-form-login', $output );
	}
}
