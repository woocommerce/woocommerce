<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

// phpcs:ignore Squiz.Commenting.ClassComment.Missing
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
	 * @testdox create_order reuses the pending checkout order for repeated attempts from the same cart.
	 */
	public function test_create_order_reuses_pending_order_for_same_cart_retry() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		try {
			$first_order_id  = $this->sut->create_order( $data );
			$second_order_id = $this->sut->create_order( $data );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->__unset( 'order_awaiting_payment' );
		}

		$this->assertIsInt( $first_order_id );
		$this->assertSame( $first_order_id, $second_order_id, 'Repeated order creation for the same cart should resume the pending order.' );

		$order = wc_get_order( $first_order_id );
		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 1, count( $order->get_items() ) );
		$this->assertSame( 'pending', $order->get_status() );
	}

	/**
	 * @testdox create_order creates a new order when the cart changes after a pending order was created.
	 */
	public function test_create_order_creates_new_order_when_cart_changes_after_pending_order() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		try {
			$first_order_id = $this->sut->create_order( $data );

			$second_product = WC_Helper_Product::create_simple_product();
			WC()->cart->add_to_cart( $second_product->get_id() );
			WC()->cart->calculate_totals();

			$second_order_id = $this->sut->create_order( $data );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->__unset( 'order_awaiting_payment' );
		}

		$this->assertIsInt( $first_order_id );
		$this->assertIsInt( $second_order_id );
		$this->assertNotSame( $first_order_id, $second_order_id, 'A changed cart should not reuse the previous pending order.' );

		$second_order = wc_get_order( $second_order_id );
		$this->assertInstanceOf( WC_Order::class, $second_order );
		$this->assertSame( 2, count( $second_order->get_items() ) );
	}

	/**
	 * @testdox create_order does not reuse a completed order for the same cart.
	 */
	public function test_create_order_does_not_reuse_completed_order_for_same_cart_retry() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		try {
			$first_order_id = $this->sut->create_order( $data );
			$first_order    = wc_get_order( $first_order_id );
			$first_order->set_status( 'completed' );
			$first_order->save();

			$second_order_id = $this->sut->create_order( $data );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->__unset( 'order_awaiting_payment' );
		}

		$this->assertIsInt( $first_order_id );
		$this->assertIsInt( $second_order_id );
		$this->assertNotSame( $first_order_id, $second_order_id, 'A completed order should not be reused for a checkout retry.' );
	}

	/**
	 * @testdox create_order reuses a failed order for the same cart retry.
	 */
	public function test_create_order_reuses_failed_order_for_same_cart_retry() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		try {
			$first_order_id = $this->sut->create_order( $data );
			$first_order    = wc_get_order( $first_order_id );
			$first_order->set_status( 'failed' );
			$first_order->save();

			$second_order_id = $this->sut->create_order( $data );
		} finally {
			WC()->cart->empty_cart();
			WC()->session->__unset( 'order_awaiting_payment' );
		}

		$this->assertIsInt( $first_order_id );
		$this->assertSame( $first_order_id, $second_order_id, 'A failed order should be reused for a same-cart checkout retry.' );
	}
}
