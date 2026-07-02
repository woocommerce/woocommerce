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
		update_option( 'woocommerce_manage_stock', 'no' );
		WC()->session->set( 'order_awaiting_payment', null );
		WC()->cart->empty_cart();
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
	 * Create a pending, stock-reduced order and mark it as awaiting payment so a subsequent
	 * create_order() call resumes it.
	 *
	 * @param int $stock Initial product stock quantity.
	 * @param int $qty   Quantity to order.
	 * @return array Order ID (int), product (WC_Product) and the checkout data used to resume (array).
	 */
	private function create_pending_reduced_stock_order( $stock = 10, $qty = 1 ) {
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $stock );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), $qty );
		WC()->cart->calculate_totals();

		$data = array(
			'payment_method' => WC_Gateway_BACS::ID,
			'billing_email'  => 'customer@example.com',
		);

		$order_id = $this->sut->create_order( $data );

		// Reduce stock as a payment attempt would, then leave the order pending so it can be resumed.
		wc_maybe_reduce_stock_levels( $order_id );
		$order = wc_get_order( $order_id );
		$order->set_status( 'pending' );
		$order->save();

		WC()->session->set( 'order_awaiting_payment', $order_id );

		return array( $order_id, wc_get_product( $product->get_id() ), $data );
	}

	/**
	 * @testdox Resuming an unchanged order keeps its line items and their reduced-stock accounting.
	 */
	public function test_resuming_order_preserves_line_items_and_reduced_stock() {
		list( $order_id, $product, $data ) = $this->create_pending_reduced_stock_order( 10, 1 );

		$order_before   = wc_get_order( $order_id );
		$items_before   = $order_before->get_items();
		$item_before    = reset( $items_before );
		$item_id_before = $item_before->get_id();

		$this->assertEquals( 1, $item_before->get_meta( '_reduced_stock' ), 'First pass should record the reduced stock on the line item.' );
		$this->assertEquals( 9, wc_get_product( $product->get_id() )->get_stock_quantity(), 'First pass should reduce stock by the ordered quantity.' );

		$resumed_id = $this->sut->create_order( $data );

		$this->assertSame( $order_id, $resumed_id, 'The unchanged order should be resumed, not replaced by a new one.' );

		$resumed     = wc_get_order( $resumed_id );
		$items_after = $resumed->get_items();

		$this->assertCount( 1, $items_after, 'The resumed order should still have exactly one line item.' );

		$item_after = reset( $items_after );
		$this->assertSame( $item_id_before, $item_after->get_id(), 'The line item should be preserved (same row), not deleted and recreated.' );
		$this->assertEquals( 1, $item_after->get_meta( '_reduced_stock' ), 'The per-item reduced-stock meta should survive the resume.' );
		$this->assertTrue( (bool) $resumed->get_data_store()->get_stock_reduced( $resumed ), 'The order-level stock-reduced flag should stay set.' );

		// A subsequent payment-time reduction must not reduce stock a second time.
		wc_maybe_reduce_stock_levels( $resumed_id );
		$this->assertEquals( 9, wc_get_product( $product->get_id() )->get_stock_quantity(), 'Stock must not be reduced twice for a resumed order.' );
	}

	/**
	 * @testdox Resuming an order does not re-run the create_order_line_item hook and does not duplicate its meta.
	 */
	public function test_resuming_order_does_not_refire_create_order_line_item_hook() {
		$fire_count = 0;
		$callback   = function ( $item ) use ( &$fire_count ) {
			++$fire_count;
			$item->add_meta_data( '_test_addon', 'engraving' );
		};
		add_action( 'woocommerce_checkout_create_order_line_item', $callback, 10, 1 );

		list( $order_id, , $data ) = $this->create_pending_reduced_stock_order();

		$this->assertSame( 1, $fire_count, 'The line-item hook should fire once when the order is first created.' );

		$resumed_id = $this->sut->create_order( $data );

		remove_action( 'woocommerce_checkout_create_order_line_item', $callback, 10 );

		$this->assertSame( 1, $fire_count, 'The line-item hook must not fire again on resume, since line items are preserved.' );

		$resumed = wc_get_order( $resumed_id );
		$items   = $resumed->get_items();
		$item    = reset( $items );

		$this->assertSame( 'engraving', $item->get_meta( '_test_addon' ), 'Meta added through the hook should survive the resume.' );
		$this->assertCount( 1, $item->get_meta( '_test_addon', false ), 'The hook meta should not be duplicated on the preserved line item.' );
	}

	/**
	 * @testdox Resuming an order does not re-run the create_order_line_item_object filter.
	 */
	public function test_resuming_order_does_not_refire_create_order_line_item_object_filter() {
		$fire_count = 0;
		$callback   = function ( $item ) use ( &$fire_count ) {
			++$fire_count;
			return $item;
		};
		add_filter( 'woocommerce_checkout_create_order_line_item_object', $callback, 10, 1 );

		list( $order_id, , $data ) = $this->create_pending_reduced_stock_order();

		$this->assertSame( 1, $fire_count, 'The line-item object filter should fire once when the order is first created.' );

		$this->sut->create_order( $data );

		remove_filter( 'woocommerce_checkout_create_order_line_item_object', $callback, 10 );

		$this->assertSame( 1, $fire_count, 'The line-item object filter must not fire again on resume, since line items are preserved.' );
	}

	/**
	 * @testdox Resuming an order still refreshes the non-line-item types such as fees.
	 */
	public function test_resuming_order_still_refreshes_fee_lines() {
		$add_fee = function ( $cart ) {
			$cart->add_fee( 'Handling', 5 );
		};
		add_action( 'woocommerce_cart_calculate_fees', $add_fee );

		list( $order_id, , $data ) = $this->create_pending_reduced_stock_order();

		$this->assertCount( 1, wc_get_order( $order_id )->get_items( 'fee' ), 'First pass should add the fee line.' );

		$resumed_id = $this->sut->create_order( $data );

		remove_action( 'woocommerce_cart_calculate_fees', $add_fee );

		$fees = wc_get_order( $resumed_id )->get_items( 'fee' );
		$this->assertCount( 1, $fees, 'The resumed order should still have exactly one fee line (refreshed, not dropped or duplicated).' );

		$fee = reset( $fees );
		$this->assertEquals( 5, $fee->get_total(), 'The refreshed fee amount should match the cart.' );
	}

	/**
	 * @testdox create_order does not gate on stock; availability is enforced by cart stock validation.
	 */
	public function test_create_order_does_not_bypass_cart_stock_validation() {
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'no' );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 5 );
		WC()->cart->calculate_totals();

		// Cart-level validation is the availability gate, independent of how order line items are built.
		$stock_check = WC()->cart->check_cart_item_stock();
		$this->assertInstanceOf( WP_Error::class, $stock_check, 'Cart stock validation should flag the shortage.' );

		// create_order() itself performs no stock check, so keeping line items on resume cannot bypass validation.
		$order_id = $this->sut->create_order(
			array(
				'payment_method' => WC_Gateway_BACS::ID,
				'billing_email'  => 'customer@example.com',
			)
		);
		$this->assertIsInt( $order_id, 'create_order() builds the order regardless of stock; the check lives in the validation phase.' );
	}
}
