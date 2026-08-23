<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Checkout.
 */

use Automattic\WooCommerce\Internal\Checkout\CheckoutOrderLock;
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
	 * @var callable[] Callbacks registering extra checkout fields, all removed on tear down.
	 */
	private $extra_field_filters = array();

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:disable Generic.CodeAnalysis, Squiz.Commenting
		$this->sut = new class() extends WC_Checkout {
			public function validate_posted_data( &$data, &$errors ) {
				return parent::validate_posted_data( $data, $errors );
			}

			public function validate_checkout( &$data, &$errors ) {
				return parent::validate_checkout( $data, $errors );
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
		delete_option( 'woocommerce_calc_taxes' );

		foreach ( $this->extra_field_filters as $extra_field_filter ) {
			remove_filter( 'woocommerce_checkout_fields', $extra_field_filter );
		}

		$this->extra_field_filters = array();

		parent::tearDown();
	}

	/**
	 * Register an extra checkout field in an arbitrary fieldset, the way checkout field editor plugins do.
	 *
	 * Can be called more than once per test: each callback is tracked and removed on tear down.
	 *
	 * @param string $fieldset_key Fieldset the field belongs to.
	 * @param string $key          Field key.
	 * @param array  $field        Field definition.
	 */
	private function register_extra_checkout_field( $fieldset_key, $key, $field ) {
		$extra_field_filter = function ( $fields ) use ( $fieldset_key, $key, $field ) {
			$fields[ $fieldset_key ][ $key ] = $field;

			return $fields;
		};

		$this->extra_field_filters[] = $extra_field_filter;

		add_filter( 'woocommerce_checkout_fields', $extra_field_filter );
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
	 * @testdox 'validate_posted_data' reports a required field error, and doesn't throw, for an empty phone field in a fieldset without a country.
	 *
	 * @testWith ["order"]
	 *           ["custom"]
	 *
	 * @param string $fieldset_key The fieldset the phone field belongs to.
	 */
	public function test_validate_posted_data_does_not_throw_for_empty_phone_field_without_a_country( $fieldset_key ) {
		$this->register_extra_checkout_field(
			$fieldset_key,
			'extra_phone',
			array(
				'label'    => 'Phone number',
				'validate' => array( 'phone' ),
				'required' => true,
			)
		);

		$data = array(
			'ship_to_different_address' => false,
			'extra_phone'               => '',
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEquals(
			'<strong>Phone number</strong> is a required field.',
			$errors->get_error_message( 'extra_phone_required' ),
			'An empty required phone field should be reported as missing, not blow up while resolving the country.'
		);
		$this->assertEmpty( $errors->get_error_message( 'extra_phone_validation' ) );
	}

	/**
	 * @testdox 'validate_posted_data' validates a phone field in a fieldset without a country, without country specific rules.
	 *
	 * @testWith ["not a phone number", true]
	 *           ["+34 600 000 000", false]
	 *
	 * @param string $phone        The posted phone number.
	 * @param bool   $expect_error True to expect a validation error for the phone field.
	 */
	public function test_validate_posted_data_validates_phone_field_in_order_fieldset( $phone, $expect_error ) {
		$this->register_extra_checkout_field(
			'order',
			'order_phone',
			array(
				'label'    => 'Phone number',
				'validate' => array( 'phone' ),
			)
		);

		$data = array(
			'ship_to_different_address' => false,
			'order_phone'               => $phone,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEquals(
			$expect_error ? '<strong>Phone number</strong> is not a valid phone number.' : '',
			$errors->get_error_message( 'order_phone_validation' )
		);
	}

	/**
	 * @testdox 'validate_posted_data' validates a postcode field in a fieldset without a country, without country specific rules.
	 *
	 * 'ABCDE' is deliberately valid only without a country: it passes the country agnostic check but fails the
	 * store's own base country rules, so this fails if the fieldset ever resolves to a real country.
	 *
	 * @testWith ["INVALID!", true]
	 *           ["ABCDE", false]
	 *
	 * @param string $postcode     The posted postcode.
	 * @param bool   $expect_error True to expect a validation error for the postcode field.
	 */
	public function test_validate_posted_data_validates_postcode_field_in_order_fieldset( $postcode, $expect_error ) {
		$this->register_extra_checkout_field(
			'order',
			'order_postcode',
			array(
				'label'    => 'Delivery postcode',
				'validate' => array( 'postcode' ),
			)
		);

		$data = array(
			'ship_to_different_address' => false,
			'order_postcode'            => $postcode,
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEquals(
			$expect_error ? '<strong>Delivery postcode</strong> is not a valid postcode / ZIP.' : '',
			$errors->get_error_message( 'order_postcode_validation' )
		);
	}

	/**
	 * @testdox 'validate_posted_data' skips state validation, and doesn't throw, for a state field in a fieldset without a country.
	 */
	public function test_validate_posted_data_does_not_throw_for_state_field_in_order_fieldset() {
		$this->register_extra_checkout_field(
			'order',
			'order_state',
			array(
				'label'    => 'Delivery state',
				'validate' => array( 'state' ),
			)
		);

		$data = array(
			'ship_to_different_address' => false,
			'order_state'               => 'Not a real state',
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty( $errors->get_error_message( 'order_state_validation' ) );
	}

	/**
	 * @testdox 'validate_posted_data' ignores customer getters that only match a fieldset key by accident.
	 *
	 * 'WC_Customer::get_default_country()' is public, so looking the getter up from the fieldset key made a
	 * fieldset named 'default' validate against the store's base country and emit a deprecation notice.
	 * 'ABCDE' passes the country agnostic postcode check but fails the store's base country rules.
	 */
	public function test_validate_posted_data_ignores_incidentally_matching_customer_getters() {
		$this->register_extra_checkout_field(
			'default',
			'default_postcode',
			array(
				'label'    => 'Default postcode',
				'validate' => array( 'postcode' ),
			)
		);

		$data = array(
			'ship_to_different_address' => false,
			'default_postcode'          => 'ABCDE',
		);

		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$this->assertEmpty(
			$errors->get_error_message( 'default_postcode_validation' ),
			"Only billing and shipping have a country, so the 'default' fieldset must not resolve to one."
		);
	}

	/**
	 * @testdox 'validate_posted_data' doesn't throw when the customer object isn't set up yet.
	 *
	 * 'WC()->customer' is null until 'WC_Woocommerce::initialize_cart()' runs, and 'validate_posted_data'
	 * has to survive that. 'ABCDE' passes the country agnostic postcode check but fails the store's base
	 * country rules, so this also pins that no country is assumed when the customer can't supply one.
	 */
	public function test_validate_posted_data_does_not_throw_without_a_customer_object() {
		$original_customer = WC()->customer;

		WC()->customer = null;

		$data = array(
			'ship_to_different_address' => false,
			'billing_postcode'          => 'ABCDE',
		);

		$errors = new WP_Error();

		try {
			$this->sut->validate_posted_data( $data, $errors );
		} finally {
			WC()->customer = $original_customer;
		}

		$this->assertEmpty(
			$errors->get_error_message( 'billing_postcode_validation' ),
			'Without a customer object there is no country to validate against.'
		);
	}

	/**
	 * @testdox 'validate_posted_data' prefers the posted country over the one stored on the customer.
	 */
	public function test_validate_posted_data_prefers_the_posted_country() {
		$original_billing_country = WC()->customer->get_billing_country();

		WC()->customer->set_billing_country( 'GB' );

		$data = array(
			'ship_to_different_address' => false,
			'billing_country'           => 'US',
			'billing_postcode'          => '12345',
		);

		$errors = new WP_Error();

		try {
			$this->sut->validate_posted_data( $data, $errors );
		} finally {
			WC()->customer->set_billing_country( $original_billing_country );
		}

		$this->assertEmpty(
			$errors->get_error_message( 'billing_postcode_validation' ),
			"'12345' is a valid US postcode, so the posted country must win over the customer's GB country."
		);
	}

	/**
	 * @testdox 'validate_posted_data' still falls back to the customer country for billing and shipping fields.
	 *
	 * @testWith ["billing", "GB", true]
	 *           ["billing", "US", false]
	 *           ["shipping", "GB", true]
	 *           ["shipping", "US", false]
	 *
	 * @param string $fieldset_key     The fieldset holding the postcode field.
	 * @param string $customer_country The country stored on the customer object.
	 * @param bool   $expect_error     True to expect a validation error for the postcode field.
	 */
	public function test_validate_posted_data_falls_back_to_the_customer_country( $fieldset_key, $customer_country, $expect_error ) {
		add_filter( 'woocommerce_cart_needs_shipping_address', '__return_true' );

		$original_billing_country  = WC()->customer->get_billing_country();
		$original_shipping_country = WC()->customer->get_shipping_country();

		WC()->customer->set_billing_country( $customer_country );
		WC()->customer->set_shipping_country( $customer_country );

		// The country is deliberately not posted, so it has to be resolved from the customer object.
		$data = array(
			'ship_to_different_address' => true,
			$fieldset_key . '_postcode' => '12345',
		);

		$errors = new WP_Error();

		try {
			$this->sut->validate_posted_data( $data, $errors );
		} finally {
			WC()->customer->set_billing_country( $original_billing_country );
			WC()->customer->set_shipping_country( $original_shipping_country );
			remove_filter( 'woocommerce_cart_needs_shipping_address', '__return_true' );
		}

		$this->assertEquals(
			$expect_error,
			! empty( $errors->get_error_message( $fieldset_key . '_postcode_validation' ) ),
			"'12345' should " . ( $expect_error ? 'not ' : '' ) . "be accepted as a {$customer_country} postcode."
		);
	}

	/**
	 * @testdox 'get_posted_data' respects the selected shipping address.
	 *
	 * @testWith [null, false]
	 *           ["0", false]
	 *           ["1", true]
	 *
	 * @param string|null $posted_value              Raw posted 'ship_to_different_address' value, or null to omit the field.
	 * @param bool        $ship_to_different_address Whether a separate shipping address is expected to be selected.
	 */
	public function test_get_posted_data_respects_shipping_address_selection( $posted_value, $ship_to_different_address ) {
		add_filter( 'woocommerce_cart_needs_shipping_address', '__return_true' );

		$posted_data = array(
			'woocommerce-process-checkout-nonce' => 'test-nonce',
			'billing_first_name'                 => 'Billing',
			'billing_last_name'                  => 'Customer',
			'billing_company'                    => 'Billing Company',
			'billing_address_1'                  => '123 Billing Street',
			'billing_address_2'                  => 'Suite 4',
			'billing_city'                       => 'Billington',
			'billing_postcode'                   => '12345',
			'billing_country'                    => 'US',
			'billing_state'                      => 'CA',
			'shipping_first_name'                => 'Hidden',
			'shipping_last_name'                 => 'Autofill',
			'shipping_company'                   => 'Hidden Company',
			'shipping_address_1'                 => '999 Hidden Street',
			'shipping_address_2'                 => 'Hidden Suite',
			'shipping_city'                      => 'Hidden City',
			'shipping_postcode'                  => '99999',
			'shipping_country'                   => 'CA',
			'shipping_state'                     => 'BC',
		);
		if ( null !== $posted_value ) {
			$posted_data['ship_to_different_address'] = $posted_value;
		}

		$original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request data.
		$_POST         = $posted_data;

		try {
			$data = $this->sut->get_posted_data();
		} finally {
			$_POST = $original_post;
			remove_filter( 'woocommerce_cart_needs_shipping_address', '__return_true' );
		}

		$this->assertSame( $ship_to_different_address, $data['ship_to_different_address'] );
		$expected_address_type = $ship_to_different_address ? 'shipping' : 'billing';

		foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state' ) as $field ) {
			$this->assertArrayHasKey( 'shipping_' . $field, $data, "Shipping {$field} should be present after checkout normalization." );
			$this->assertSame(
				$posted_data[ $expected_address_type . '_' . $field ],
				$data[ 'shipping_' . $field ],
				"Shipping {$field} should use the {$expected_address_type} {$field} value."
			);
		}
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
	 * @testdox create_order_tax_lines sets rate_code, label, compound and rate_percent on order tax items.
	 */
	public function test_create_order_tax_lines_sets_correct_tax_item_props(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// German standard 19% non-compound VAT rate.
		$tax_rate = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		WC()->customer->set_billing_country( 'DE' );
		WC()->customer->set_shipping_country( 'DE' );
		WC()->customer->set_is_vat_exempt( false );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		$order     = wc_get_order( $this->sut->create_order( array( 'payment_method' => WC_Gateway_BACS::ID ) ) );
		$tax_items = $order->get_taxes();

		$this->assertCount( 1, $tax_items );

		/** @var WC_Order_Item_Tax $tax_item */
		$tax_item = array_values( $tax_items )[0];
		$this->assertSame( 'DE-VAT-1', $tax_item->get_rate_code() );
		$this->assertSame( 'VAT', $tax_item->get_label() );
		$this->assertFalse( $tax_item->get_compound() );
		$this->assertSame( 19.0, $tax_item->get_rate_percent() );
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

	/**
	 * @testdox Returns WP_Error when line items fail to persist to the DB despite save() completing.
	 */
	public function test_create_order_returns_error_when_items_not_persisted() {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		$simulate_silent_insert_failure = function ( $order ) use ( $wpdb ) {
			$wpdb->delete(
				$wpdb->prefix . 'woocommerce_order_items',
				array( 'order_id' => $order->get_id() )
			);
			wp_cache_flush();
		};
		add_action( 'woocommerce_after_order_object_save', $simulate_silent_insert_failure );

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		try {
			$result = $this->sut->create_order( $data );
		} finally {
			remove_action( 'woocommerce_after_order_object_save', $simulate_silent_insert_failure );
			WC()->cart->empty_cart();
		}

		$this->assertInstanceOf( WP_Error::class, $result, 'create_order() should return a WP_Error when line items were not persisted.' );
		$this->assertSame( 'checkout-error', $result->get_error_code(), 'Error code should come from the checkout try/catch path.' );
		$this->assertStringContainsString( 'Order items could not be saved', $result->get_error_message(), 'Error message should surface the defense-in-depth guard message.' );
	}

	/**
	 * @testdox process_checkout() should fail fast without creating a duplicate order when another request already holds this shopper's checkout lock.
	 */
	public function test_process_checkout_fails_fast_when_lock_is_held_by_another_request() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC()->cart->add_to_cart( $product->get_id() );

		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		// BACS is disabled by default in the test install; force it available regardless of its stored settings.
		$bacs_gateway          = WC()->payment_gateways()->payment_gateways()[ WC_Gateway_BACS::ID ];
		$bacs_was_enabled      = $bacs_gateway->enabled;
		$bacs_gateway->enabled = 'yes';

		add_filter(
			'woocommerce_countries_allowed_countries',
			function () {
				return array( 'US' => 'United States (US)' );
			}
		);

		$_POST    = array(
			'woocommerce-process-checkout-nonce' => wp_create_nonce( 'woocommerce-process_checkout' ),
			'ship_to_different_address'          => '0',
			'payment_method'                     => WC_Gateway_BACS::ID,
			'billing_first_name'                 => 'Jane',
			'billing_last_name'                  => 'Doe',
			'billing_address_1'                  => '123 Main St',
			'billing_city'                       => 'New York',
			'billing_state'                      => 'NY',
			'billing_postcode'                   => '10001',
			'billing_country'                    => 'US',
			'billing_email'                      => 'jane@example.com',
			'billing_phone'                      => '5555555555',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Building the request fixture, not processing real input.

		// Simulate another request already in the middle of creating an order for this same shopper.
		$checkout_lock = wc_get_container()->get( CheckoutOrderLock::class );
		$lock_key      = (string) WC()->session->get_customer_id();
		$lock_token    = $checkout_lock->acquire( $lock_key );
		$this->assertNotNull( $lock_token, 'Test setup: must be able to acquire the lock to simulate a concurrent holder.' );

		$orders_before = wc_get_orders(
			array(
				'customer' => $customer_id,
				'return'   => 'ids',
			)
		);

		try {
			$this->sut->process_checkout();
		} finally {
			$checkout_lock->release( $lock_key, $lock_token );
			remove_all_filters( 'woocommerce_countries_allowed_countries' );
			$bacs_gateway->enabled = $bacs_was_enabled;
			$_POST                 = array();
			$_REQUEST              = array();
			WC()->cart->empty_cart();
		}

		$orders_after = wc_get_orders(
			array(
				'customer' => $customer_id,
				'return'   => 'ids',
			)
		);

		$this->assertSame( $orders_before, $orders_after, 'No order should be created while another request holds this shopper\'s lock.' );

		$notices      = wc_get_notices( 'error' );
		$found_notice = false;
		foreach ( $notices as $notice ) {
			if ( false !== strpos( $notice['notice'], 'already being processed' ) ) {
				$found_notice = true;
				break;
			}
		}
		$this->assertTrue( $found_notice, 'The concurrent-checkout notice must be shown to the shopper.' );

		wc_clear_notices();
	}

	/**
	 * @testdox process_checkout() should resume an order another request already durably saved for this shopper, rather than creating a duplicate, even though this request's own session data was loaded before that write happened.
	 */
	public function test_process_checkout_resumes_an_order_saved_by_another_request_after_this_requests_session_was_loaded() {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'virtual'       => true,
				'regular_price' => 0,
				'price'         => 0,
			)
		);
		WC()->cart->add_to_cart( $product->get_id() );

		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		add_filter(
			'woocommerce_countries_allowed_countries',
			function () {
				return array( 'US' => 'United States (US)' );
			}
		);

		// Create the order this test will simulate another, concurrent request having already created and durably
		// saved. This mirrors what that other request's create_order() call would have produced: same cart, so the
		// same cart hash create_order()'s own resume check matches on.
		$existing_order_id = $this->sut->create_order(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
				'billing_email'  => 'jane@example.com',
			)
		);
		$this->assertIsInt( $existing_order_id, 'Test setup: the pre-existing order must be created successfully.' );

		// Simulate this request's own normal session bootstrap: restore_session_data() calls get_session() for
		// this exact customer on every request (via 'init'), before checkout code ever runs, which warms the
		// cache-aware get_session() lookup with whatever's in the persisted store at that point (still nothing,
		// here). Without this, the test wouldn't prove the refresh survives an already-warm cache entry - only
		// that it works against a key that was never read before, which isn't the real-world timing.
		WC()->session->get_session( (string) WC()->session->get_customer_id() );

		// This request's own WC()->session already has no 'order_awaiting_payment' in memory (create_order() above
		// never writes it - only process_checkout() does) - matching a request that loaded its session snapshot
		// before another request's write. Write directly to the persisted sessions table, bypassing WC()->session's
		// own set()/save_data(), to simulate that other request's write landing in the shared, persisted store
		// without this request's already-loaded in-memory copy knowing about it.
		global $wpdb;
		$sessions_table = $wpdb->prefix . 'woocommerce_sessions';
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$sessions_table} (session_key, session_value, session_expiry) VALUES (%s, %s, %d) ON DUPLICATE KEY UPDATE session_value = VALUES(session_value), session_expiry = VALUES(session_expiry)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(string) WC()->session->get_customer_id(),
				maybe_serialize( array( 'order_awaiting_payment' => $existing_order_id ) ),
				time() + HOUR_IN_SECONDS
			)
		);

		$this->assertSame(
			0,
			absint( WC()->session->get( 'order_awaiting_payment' ) ),
			'Test setup: this request\'s in-memory session must still show no pending order at this point.'
		);

		$_POST    = array(
			'woocommerce-process-checkout-nonce' => wp_create_nonce( 'woocommerce-process_checkout' ),
			'ship_to_different_address'          => '0',
			'billing_first_name'                 => 'Jane',
			'billing_last_name'                  => 'Doe',
			'billing_address_1'                  => '123 Main St',
			'billing_city'                       => 'New York',
			'billing_state'                      => 'NY',
			'billing_postcode'                   => '10001',
			'billing_country'                    => 'US',
			'billing_email'                      => 'jane@example.com',
			'billing_phone'                      => '5555555555',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Building the request fixture, not processing real input.

		// process_checkout() ends, on success, by redirecting or sending a JSON response and terminating the
		// script - neither of which a PHPUnit process should be allowed to do. Order creation/resumption (what
		// this test actually verifies) is fully resolved by the time woocommerce_checkout_order_processed fires,
		// still well before that terminal response - throw from it to stop process_checkout() right there. The
		// whole method body is one try/catch, so this is caught and turned into a harmless notice, same as any
		// other Exception raised during checkout.
		$stop_before_terminal_response = function () {
			throw new Exception( 'Stopping before process_checkout() reaches its terminal response, for this test only.' );
		};
		add_action( 'woocommerce_checkout_order_processed', $stop_before_terminal_response );

		try {
			$this->sut->process_checkout();
		} finally {
			remove_action( 'woocommerce_checkout_order_processed', $stop_before_terminal_response );
			remove_all_filters( 'woocommerce_countries_allowed_countries' );
			$_POST    = array();
			$_REQUEST = array();
			WC()->cart->empty_cart();
		}

		$orders = wc_get_orders(
			array(
				'customer' => $customer_id,
				'return'   => 'ids',
			)
		);

		$this->assertSame(
			array( $existing_order_id ),
			$orders,
			'The pre-existing order must be resumed, not duplicated, once this request refreshes the stale session value.'
		);
	}
}
