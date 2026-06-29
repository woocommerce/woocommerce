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
	 * Value of woocommerce_manage_stock captured in setUp so tearDown can
	 * restore it instead of deleting (delete_option resolves to WC defaults
	 * rather than the actual pre-test state, which leaks into other tests).
	 *
	 * @var string|false
	 */
	private $prev_manage_stock;

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

		$this->prev_manage_stock = get_option( 'woocommerce_manage_stock', false );

		WC()->cart->empty_cart();

		add_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_checkout_registration_enabled', '__return_true' );
		delete_option( 'woocommerce_calc_taxes' );
		// Restore rather than delete so we don't leave the option absent when
		// it had a real value before this test ran.
		if ( false === $this->prev_manage_stock ) {
			delete_option( 'woocommerce_manage_stock' );
		} else {
			update_option( 'woocommerce_manage_stock', $this->prev_manage_stock );
		}
		// Clear the session key set during resume-order tests so it doesn't
		// bleed into tests that follow in the same PHP process.
		WC()->session->set( 'order_awaiting_payment', null );
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
	 * @testdox Resuming a failed order preserves _reduced_stock across the item rebuild.
	 *
	 * Regression for #36702: when remove_order_items() runs during order resumption, the
	 * item-level _reduced_stock metadata is destroyed along with the items, and
	 * set_data_from_cart() rebuilds fresh line items with no _reduced_stock. The order stays
	 * flagged _order_stock_reduced=true, so on the next payment attempt wc_reduce_stock_levels()
	 * sees no _reduced_stock on the new items and reduces a SECOND time (double reduction).
	 *
	 * The fix keeps the order in its reduced state and carries the per-item _reduced_stock
	 * accounting across the rebuild rather than restoring stock and clearing the flag (which
	 * desyncs when woocommerce_can_restore_order_stock blocks the restore). This test drives
	 * real stock reduction via an on-hold transition, resumes the order through create_order(),
	 * and asserts the resumed line item still carries _reduced_stock so a subsequent reduce is
	 * a no-op instead of double-counting.
	 */
	public function test_resuming_failed_order_preserves_reduced_stock() {
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product = WC_Helper_Product::create_simple_product(
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		// First checkout creates the order (pending via BACS).
		$order_id = $this->sut->create_order( $data );
		$this->assertIsInt( $order_id );

		$order = wc_get_order( $order_id );

		// Transition to on-hold: fires wc_maybe_reduce_stock_levels, which calls
		// wc_reduce_stock_levels() to set _reduced_stock on the items and the
		// _order_stock_reduced flag to true. This is the real reduction path; the meta is
		// never injected manually.
		$order->update_status( 'on-hold' );

		$this->assertSame(
			9,
			wc_get_product( $product->get_id() )->get_stock_quantity(),
			'Stock must be reduced to 9 after the on-hold transition.'
		);
		$this->assertTrue(
			wc_get_order( $order_id )->get_data_store()->get_stock_reduced( $order_id ),
			'_order_stock_reduced must be true after stock is reduced.'
		);

		// Simulate payment failure: on-hold -> failed. No stock hook fires for 'failed'.
		$order->update_status( 'failed' );

		// Second checkout attempt: same cart, same cart hash, so create_order() resumes the
		// existing failed order instead of creating a new one.
		WC()->session->set( 'order_awaiting_payment', $order_id );

		$order_id_2 = $this->sut->create_order( $data );
		$this->assertIsInt( $order_id_2 );
		$this->assertSame( $order_id, $order_id_2, 'create_order() should resume the existing failed order.' );

		// Stock is intentionally NOT restored on resume; the order stays reduced.
		$this->assertSame(
			9,
			wc_get_product( $product->get_id() )->get_stock_quantity(),
			'Stock must remain at 9 after resume; the resumed order keeps its reduced state.'
		);

		// The order-level flag stays true because the reduction was preserved, not undone.
		$this->assertTrue(
			wc_get_order( $order_id )->get_data_store()->get_stock_reduced( $order_id ),
			'_order_stock_reduced must remain true after resume.'
		);

		// The core guard: the rebuilt line item must carry the preserved _reduced_stock.
		// This is what fails if restore_reduced_stock_meta() is removed; without it the
		// fresh items have no _reduced_stock and this assertion sees an empty value.
		$resumed_order = wc_get_order( $order_id_2 );
		$resumed_items = $resumed_order->get_items();
		$this->assertCount( 1, $resumed_items, 'Resumed order should have exactly one line item.' );

		$resumed_item = current( $resumed_items );
		$this->assertSame(
			1.0,
			wc_stock_amount( $resumed_item->get_meta( '_reduced_stock', true ) ),
			'Resumed line item must carry the preserved _reduced_stock of 1.'
		);

		// Delete-the-fix consequence, asserted directly: a further reduce must be a no-op
		// because _reduced_stock is present. Without the fix, _reduced_stock would be 0/empty
		// and this call would drop stock from 9 to 8 (the double reduction this PR prevents).
		wc_reduce_stock_levels( $order_id_2 );

		$this->assertSame(
			9,
			wc_get_product( $product->get_id() )->get_stock_quantity(),
			'A subsequent reduce must not double-count; stock stays at 9.'
		);

		WC()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * @testdox Resuming a failed order reconciles _reduced_stock when the cart quantity shrinks.
	 *
	 * Companion to test_resuming_failed_order_preserves_reduced_stock covering the reconcile
	 * branch: when the resumed cart asks for fewer units than were originally reduced, the
	 * preserved _reduced_stock is clamped to the new quantity so a line never records more
	 * reduced stock than its own quantity (which would make wc_maybe_adjust_line_item_product_stock()
	 * compute a negative delta and spuriously restore stock on a later admin update).
	 */
	public function test_resuming_failed_order_clamps_reduced_stock_on_quantity_decrease() {
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product = WC_Helper_Product::create_simple_product(
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		// Reduce 3 units on the first pass.
		WC()->cart->add_to_cart( $product->get_id(), 3 );

		$data = array(
			'ship_to_different_address' => false,
			'payment_method'            => WC_Gateway_BACS::ID,
			'billing_email'             => 'customer@example.com',
		);

		$order_id = $this->sut->create_order( $data );
		$order    = wc_get_order( $order_id );
		$order->update_status( 'on-hold' );

		$this->assertSame(
			7,
			wc_get_product( $product->get_id() )->get_stock_quantity(),
			'Stock must be reduced to 7 after reducing 3 units.'
		);

		$order->update_status( 'failed' );

		// Resume with a smaller cart: 1 unit. The cart hash will differ, so to exercise the
		// resume path we rebuild the cart to the new quantity and force the awaiting-payment
		// session key; create_order() recomputes the hash from the current cart.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();
		WC()->session->set( 'order_awaiting_payment', $order_id );

		$order_id_2 = $this->sut->create_order( $data );
		$this->assertSame( $order_id, $order_id_2, 'create_order() should resume the existing failed order.' );

		$resumed_item = current( wc_get_order( $order_id_2 )->get_items() );
		$this->assertSame(
			1.0,
			wc_stock_amount( $resumed_item->get_quantity() ),
			'Resumed line item quantity should reflect the smaller cart (1).'
		);
		$this->assertSame(
			1.0,
			wc_stock_amount( $resumed_item->get_meta( '_reduced_stock', true ) ),
			'_reduced_stock must be clamped to the new quantity (1), not the original 3.'
		);

		WC()->cart->empty_cart();
		$product->delete( true );
	}
}
