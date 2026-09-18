<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Checkout.
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Class WC_Checkout
 */
class WC_Checkout_Test extends \WC_Unit_Test_Case {

	/**
	 * The least create_order() needs from a posted classic checkout form, paying by cash on delivery.
	 */
	private const COD_POSTED_DATA = array(
		'payment_method' => WC_Gateway_COD::ID,
		'billing_email'  => 'customer@example.com',
	);

	/**
	 * @var object The system under test.
	 */
	private $sut;

	/**
	 * @var callable[] Callbacks registering extra checkout fields, all removed on tear down.
	 */
	private $extra_field_filters = array();

	/**
	 * @var WC_Session|null The session the test base installed, put back after a test swapped in a real handler.
	 */
	private $original_session;

	/**
	 * @var WC_Customer|null The customer the test base installed, put back after a test ran a full checkout into a fresh one.
	 */
	private $original_customer;

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
		WC()->countries->locale = array();

		foreach ( $this->extra_field_filters as $extra_field_filter ) {
			remove_filter( 'woocommerce_checkout_fields', $extra_field_filter );
		}

		$this->extra_field_filters = array();

		if ( $this->original_session ) {
			WC()->session            = $this->original_session;
			WC()->customer           = $this->original_customer;
			$this->original_session  = null;
			$this->original_customer = null;
		}

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
	 * @testdox 'validate_posted_data' skips the required check only for fields whose locale hidden flag is exactly true.
	 *
	 * @testWith [true, false]
	 *           [false, true]
	 *           ["yes", true]
	 *           [1, true]
	 *
	 * @param mixed $hidden                Value of the locale's hidden flag for the postcode field.
	 * @param bool  $expect_required_error Whether a required-field error is expected.
	 */
	public function test_validate_posted_data_skips_required_check_for_hidden_fields( $hidden, $expect_required_error ) {
		$locale_filter = function ( $locale ) use ( $hidden ) {
			$locale['ES']['postcode']['hidden'] = $hidden;
			return $locale;
		};
		add_filter( 'woocommerce_get_country_locale', $locale_filter );
		WC()->countries->locale   = array();
		$_POST['billing_country'] = 'ES';

		$data   = array(
			'billing_country'           => 'ES',
			'billing_postcode'          => '',
			'ship_to_different_address' => false,
		);
		$errors = new WP_Error();

		$this->sut->validate_posted_data( $data, $errors );

		$required_error = $errors->get_error_message( 'billing_postcode_required' );
		if ( $expect_required_error ) {
			$this->assertNotEmpty( $required_error, 'Fields not hidden with exactly true should still trigger a required-field error.' );
		} else {
			$this->assertEmpty( $required_error, 'Hidden fields should not trigger a required-field error.' );
		}
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
	 * @testdox create_order_fee_lines sets tax status to 'none' for non-taxable cart fees and 'taxable' for taxable ones.
	 *
	 * @testWith [true, "taxable", ""]
	 *           [false, "none", ""]
	 *
	 * @param bool   $taxable Whether the cart fee is taxable.
	 * @param string $expected_tax_status The expected tax status for the created fee order item.
	 * @param string $expected_tax_class The expected tax class for the created fee order item.
	 */
	public function test_create_order_fee_lines_sets_correct_tax_status( $taxable, $expected_tax_status, $expected_tax_class ): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$add_fee = static function ( $cart ) use ( $taxable ) {
			$cart->add_fee( 'Test fee', 10, $taxable );
		};
		add_action( 'woocommerce_cart_calculate_fees', $add_fee );

		try {
			WC()->cart->calculate_totals();
			$order = wc_get_order( $this->sut->create_order( array( 'payment_method' => WC_Gateway_BACS::ID ) ) );
		} finally {
			remove_action( 'woocommerce_cart_calculate_fees', $add_fee );
		}

		$fee_items = $order->get_fees();

		$this->assertCount( 1, $fee_items );

		/** @var WC_Order_Item_Fee $fee_item */
		$fee_item = array_values( $fee_items )[0];
		$this->assertSame( $expected_tax_status, $fee_item->get_tax_status() );
		$this->assertSame( $expected_tax_class, $fee_item->get_tax_class() );
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
	 * @testdox create_order() decides a repeat submit by the session order's status: "$status" leads to "$expected".
	 *
	 * @testWith ["processing", "refuse"]
	 *           ["completed", "refuse"]
	 *           ["on-hold", "refuse"]
	 *           ["pending", "resume"]
	 *           ["failed", "resume"]
	 *           ["cancelled", "new-order"]
	 *           ["refunded", "new-order"]
	 *
	 * @param string $status   Status the session order is in when the form is submitted again.
	 * @param string $expected What create_order() does with it: refuse, resume or new-order.
	 */
	public function test_create_order_decides_a_repeat_submit_by_the_session_order_status( string $status, string $expected ): void {
		$first_order_id = $this->leave_session_order_in_status( $status );

		$result = $this->sut->create_order( self::COD_POSTED_DATA );

		switch ( $expected ) {
			case 'refuse':
				$this->assertWPError( $result, 'A repeat submit for an order that went through must not build a new order.' );
				$this->assertSame( 'checkout-order-already-placed', $result->get_error_code() );
				$this->assertSame( $first_order_id, $result->get_error_data()['order_id'] );
				$this->assertSame(
					array( $first_order_id ),
					wc_get_orders(
						array(
							'return' => 'ids',
							'limit'  => -1,
						)
					),
					'No second order should exist.'
				);
				break;
			case 'resume':
				$this->assertSame( $first_order_id, $result, 'The order awaiting payment should be resumed.' );
				break;
			case 'new-order':
				$this->assertNotWPError( $result );
				$this->assertNotSame( $first_order_id, $result, 'A new order should be built.' );
				break;
			default:
				$this->fail( "Unknown expectation '{$expected}'." );
		}
	}

	/**
	 * @testdox create_order() does not treat a status the site declares payable as a repeat submit.
	 */
	public function test_create_order_does_not_treat_a_site_declared_payable_status_as_a_repeat_submit(): void {
		add_filter(
			'woocommerce_valid_order_statuses_for_payment',
			function ( $statuses ) {
				$statuses[] = OrderStatus::ON_HOLD;

				return $statuses;
			}
		);
		$this->leave_session_order_in_status( OrderStatus::ON_HOLD );

		$result = $this->sut->create_order( self::COD_POSTED_DATA );

		$this->assertNotWPError( $result, 'An on-hold order the site still lets the shopper pay for is not a repeat submit.' );
	}

	/**
	 * Leave the session the way a gateway that died right after its status change does: the order it points at
	 * has the cart's hash and the given status, and the cart was never emptied.
	 *
	 * @param string $status Status to put the order in.
	 * @return int The order ID.
	 */
	private function leave_session_order_in_status( string $status ): int {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$order_id = $this->sut->create_order( self::COD_POSTED_DATA );
		$this->assertNotWPError( $order_id );
		WC()->session->set( 'order_awaiting_payment', $order_id );
		wc_get_order( $order_id )->update_status( $status );

		return $order_id;
	}

	/**
	 * @testdox process_checkout() answers a repeat submit with the order-received redirect when the session order already went through the gateway.
	 */
	public function test_process_checkout_answers_a_repeat_submit_with_the_order_received_redirect(): void {
		$this->use_real_session();
		$this->make_gateway_available( WC_Gateway_BACS::ID );
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC()->cart->add_to_cart( $product->get_id() );
		$this->post_checkout_form( WC_Gateway_BACS::ID );

		// A plugin dies inside the status transition the gateway triggers, after the order moved on but before the cart was emptied.
		$die_in_transition = function () {
			throw new Error( 'Call to a member function push_order() on null' );
		};
		add_action( 'woocommerce_order_status_on-hold', $die_in_transition );
		try {
			$this->submit_checkout_over_ajax();
			$this->fail( 'The first submit should have died inside the status transition.' );
		} catch ( Error $e ) {
			$this->assertSame( 'Call to a member function push_order() on null', $e->getMessage() );
		}
		remove_action( 'woocommerce_order_status_on-hold', $die_in_transition );

		$order_ids = wc_get_orders(
			array(
				'return' => 'ids',
				'limit'  => -1,
			)
		);
		$this->assertCount( 1, $order_ids, 'The first submit should have created the order before dying.' );
		$order = wc_get_order( $order_ids[0] );
		$this->assertTrue( $order->has_status( OrderStatus::ON_HOLD ) );
		$this->assertFalse( WC()->cart->is_empty(), 'The gateway never got to empty the cart.' );

		$response = $this->submit_checkout_over_ajax();

		$this->assertSame( 'success', $response['result'] ?? null, wp_json_encode( $response ) );
		$this->assertSame( $order->get_checkout_order_received_url(), $response['redirect'] ?? null );
		$this->assertSame(
			$order_ids,
			wc_get_orders(
				array(
					'return' => 'ids',
					'limit'  => -1,
				)
			),
			'The retry must not build a second order.'
		);
		$latest_note = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'limit'    => 1,
			)
		)[0] ?? null;
		$this->assertStringContainsString( 'No second order was created', $latest_note->content ?? '', 'The merchant should see the repeat submit on the order.' );
	}

	/**
	 * Swap the test base's in-memory session for a real handler, which is what process_order_payment() needs (it calls save_data()).
	 *
	 * The customer is swapped too, because a full checkout writes the posted address into it and nothing else resets that singleton.
	 */
	private function use_real_session(): void {
		$this->original_session  = WC()->session;
		$this->original_customer = WC()->customer;

		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set_customer_session_cookie( true );
		WC()->customer = new WC_Customer( 0, true );
	}

	/**
	 * Make a registered gateway available at checkout without touching its stored settings.
	 *
	 * @param string $gateway_id Gateway ID.
	 */
	private function make_gateway_available( string $gateway_id ): void {
		add_filter(
			'woocommerce_available_payment_gateways',
			function ( $gateways ) use ( $gateway_id ) {
				$gateways[ $gateway_id ] = WC()->payment_gateways()->payment_gateways()[ $gateway_id ];

				return $gateways;
			}
		);
	}

	/**
	 * Fill the request the way the classic checkout form posts it, for a guest buying a virtual product.
	 *
	 * Call it after use_real_session(): the nonce takes its logged-out user ID from the session, so one created
	 * before the swap fails verification after it.
	 *
	 * @param string $payment_method Gateway ID.
	 */
	private function post_checkout_form( string $payment_method ): void {
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );

		$_POST    = array(
			'woocommerce-process-checkout-nonce' => wp_create_nonce( 'woocommerce-process_checkout' ),
			'payment_method'                     => $payment_method,
			'billing_first_name'                 => 'Ada',
			'billing_last_name'                  => 'Lovelace',
			'billing_address_1'                  => '1 Analytical Engine Way',
			'billing_city'                       => 'Los Angeles',
			'billing_state'                      => 'CA',
			'billing_postcode'                   => '90210',
			'billing_country'                    => 'US',
			'billing_email'                      => 'customer@example.com',
			'billing_phone'                      => '555-555-5555',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture; the request carries the nonce created just above.
	}

	/**
	 * Run process_checkout() as the checkout JS does, returning the JSON it answers with.
	 *
	 * Anything other than the wp_send_json() exit, such as an Error a hook raises, propagates to the caller.
	 * The exit is an Error rather than an Exception because process_checkout() catches Exception itself.
	 *
	 * @return array Decoded JSON response.
	 */
	private function submit_checkout_over_ajax(): array {
		$throw_instead_of_dying = function () {
			return function () {
				throw new Error( 'wp_die' );
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $throw_instead_of_dying );
		$outer_buffer_level = ob_get_level();
		ob_start();

		try {
			$this->sut->process_checkout();
			$this->fail( 'process_checkout() should end the request with wp_send_json().' );
		} catch ( Error $e ) {
			if ( 'wp_die' !== $e->getMessage() ) {
				throw $e;
			}
			$output = (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $outer_buffer_level ) {
				ob_end_clean();
			}
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', $throw_instead_of_dying );
		}

		$decoded = json_decode( $output, true );
		$this->assertIsArray( $decoded, $output );

		return $decoded;
	}

	/**
	 * @testdox Checkout tolerates non-array cart variation data when building contextual item names.
	 */
	public function test_create_order_line_items_tolerates_malformed_variation_data(): void {
		list( $product, $variation ) = WC_Helper_Product::create_variation_product_with_global_attributes(
			'Checkout Malformed Variation Product',
			array(
				'pa_size'   => 'huge',
				'pa_number' => '',
			)
		);

		$order = wc_create_order();

		$malformed_variation_filter = function ( $cart_contents ) {
			foreach ( $cart_contents as &$cart_item ) {
				$cart_item['variation'] = 'malformed variation data';
			}
			unset( $cart_item );

			return $cart_contents;
		};

		try {
			$this->add_variation_to_cart( $product, $variation );
			add_filter( 'woocommerce_get_cart_contents', $malformed_variation_filter );

			$this->sut->create_order_line_items( $order, WC()->cart );
			$order->save();

			$items = array_values( wc_get_order( $order->get_id() )->get_items() );
			$this->assertCount( 1, $items );
			$this->assertSame( $variation->get_name(), $items[0]->get_name() );
			$this->assertCount( 0, $items[0]->get_meta_data(), 'Malformed variation data should not be stored as item meta.' );
		} finally {
			WC()->cart->empty_cart();
			$order->delete( true );
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox Checkout merges selected taxonomy Any attributes into the item name without duplicating them as metadata.
	 */
	public function test_create_order_line_items_merges_taxonomy_any_attributes_and_dedupes_meta(): void {
		list( $product, $variation ) = WC_Helper_Product::create_variation_product_with_global_attributes(
			'Checkout Taxonomy Any Product',
			array(
				'pa_size'   => 'huge',
				'pa_number' => '',
			)
		);

		$order = wc_create_order();

		try {
			$this->add_variation_to_cart( $product, $variation );
			$this->sut->create_order_line_items( $order, WC()->cart );
			$order->save();

			$items = array_values( $order->get_items() );
			$this->assertCount( 1, $items );
			$this->assertSame( 'Checkout Taxonomy Any Product - huge, 1', $items[0]->get_name() );
			$this->assertSame( '1', $items[0]->get_meta( 'pa_number' ), 'The selected Any value should remain stored as item meta.' );
			$this->assertCount( 0, $items[0]->get_formatted_meta_data(), 'Selected Any values included in the item name should not be duplicated as metadata.' );
		} finally {
			WC()->cart->empty_cart();
			$order->delete( true );
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox Persisted order item names use raw custom Any values so order meta dedup keeps working.
	 *
	 * @dataProvider custom_any_value_provider
	 *
	 * @param string $selected_value Selected custom attribute value.
	 * @param string $expected_name  Expected persisted order item name.
	 */
	public function test_create_order_line_items_persists_raw_custom_any_values( string $selected_value, string $expected_name ): void {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'finish' );
		$attribute->set_options( array( 'gloss', 'matte', 'Black & White' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Canonical Name Product' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'finish' => '' ) );
		$variation->set_regular_price( '10' );
		$variation->save();

		$filter_option_name = static function ( $value ) {
			return 'gloss' === $value ? 'Polished' : $value;
		};
		add_filter( 'woocommerce_variation_option_name', $filter_option_name );

		$order = wc_create_order();

		try {
			$this->add_variation_to_cart( $product, $variation, array( 'attribute_finish' => $selected_value ) );
			$this->sut->create_order_line_items( $order, WC()->cart );
			$order->save();

			$items = array_values( $order->get_items() );
			$this->assertCount( 1, $items );
			$this->assertSame( $expected_name, $items[0]->get_name(), 'Persisted names must use raw values, not woocommerce_variation_option_name output.' );
			$this->assertCount( 0, $items[0]->get_formatted_meta_data(), 'The raw value in the name must keep order meta dedup working.' );
		} finally {
			WC()->cart->empty_cart();
			$order->delete( true );
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * Provides selected custom Any values and their expected persisted names.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function custom_any_value_provider(): array {
		return array(
			'raw value wins over filtered label' => array( 'gloss', 'Canonical Name Product - gloss' ),
			'entity-bearing value dedupes'       => array( 'Black & White', 'Canonical Name Product - Black & White' ),
		);
	}

	/**
	 * Adds a variation to the cart, asserting success, and calculates totals.
	 *
	 * @param WC_Product $product    Variable product.
	 * @param WC_Product $variation  Variation to add.
	 * @param array      $attributes Selected variation attributes.
	 */
	private function add_variation_to_cart( $product, $variation, array $attributes = array(
		'attribute_pa_size'   => 'huge',
		'attribute_pa_number' => '1',
	) ): void {
		$cart_item_key = WC()->cart->add_to_cart( $product->get_id(), 1, $variation->get_id(), $attributes );
		$this->assertNotFalse( $cart_item_key, 'The variation should be added to the cart.' );
		WC()->cart->calculate_totals();
	}
}
