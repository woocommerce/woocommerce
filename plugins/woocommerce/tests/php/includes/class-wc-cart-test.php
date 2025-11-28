<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Cart.
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Class WC_Cart_Test
 */
class WC_Cart_Test extends \WC_Unit_Test_Case {
	/**
	 * Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();
	}

	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();

		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * @testdox Order Again should enforce sold individually for variable products (no duplicates, qty forced to 1)
	 */
	public function test_order_again_enforces_sold_individually_for_variations() {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set_customer_session_cookie( true );

		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$variable_product = WC_Helper_Product::create_variation_product();
		$variable_product->set_sold_individually( true );
		$variable_product->save();

		$variation_ids = $variable_product->get_children();
		$this->assertNotEmpty( $variation_ids, 'Expected at least one variation.' );
		$variation_id = (int) $variation_ids[0];
		$variation    = wc_get_product( $variation_id );

		$this->assertTrue( $variation->is_sold_individually(), 'Variation should be sold individually.' );

		$order = WC_Helper_Order::create_order( $user_id, $variation, array( 'status' => OrderStatus::COMPLETED ) );
		$this->assertGreaterThan( 0, $order->get_id(), 'Order should be created.' );

		$order_items = $order->get_items();
		$this->assertNotEmpty( $order_items, 'Order should have at least one item.' );
		$order_item = array_values( $order_items )[0];
		foreach ( $variation->get_attributes() as $att_key => $att_val ) {
			$order_item->add_meta_data( $att_key, $att_val, true );
		}
		$order_item->save();
		$order->save();

		$cart_session = new WC_Cart_Session( WC()->cart );
		$ref          = new ReflectionClass( WC_Cart_Session::class );
		$method       = $ref->getMethod( 'populate_cart_from_order' );
		$method->setAccessible( true );
		$current_cart = WC()->session->get( 'cart', null );
		$populated    = $method->invoke( $cart_session, $order->get_id(), $current_cart );
		WC()->session->set( 'cart', $populated );
		WC()->cart->set_cart_contents( $populated ? $populated : array() );

		$cart_contents = WC()->cart->get_cart();
		$this->assertCount( 1, $cart_contents, 'Cart should contain one item after Order Again for sold individually product.' );

		$only_item = array_values( $cart_contents )[0];
		$this->assertEquals( $variation_id, $only_item['variation_id'], 'Cart item should correspond to the ordered variation.' );
		$this->assertEquals( 1, $only_item['quantity'], 'Quantity should be forced to 1 for sold individually products.' );

		$available_variations     = $variable_product->get_available_variations();
		$attributes_for_variation = array();
		foreach ( $available_variations as $v ) {
			if ( (int) $v['variation_id'] === $variation_id ) {
				$attributes_for_variation = $v['attributes'];
				break;
			}
		}
		$this->assertNotEmpty( $attributes_for_variation, 'Expected to find attributes for variation.' );

		$added = WC()->cart->add_to_cart( $variable_product->get_id(), 1, $variation_id, $attributes_for_variation );
		$this->assertFalse( $added, 'Adding duplicate sold individually variation should be blocked.' );

		$notices = wc_get_notices();
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertNotEmpty( $notices['error'], 'Expected an error notice when adding duplicate sold individually item.' );

		$cart_contents_after = WC()->cart->get_cart();
		$this->assertCount( 1, $cart_contents_after, 'Cart should still contain one item.' );
		$only_item_after = array_values( $cart_contents_after )[0];
		$this->assertEquals( 1, $only_item_after['quantity'], 'Quantity should remain 1.' );

		WC_Helper_Order::delete_order( $order->get_id() );
		WC_Helper_Product::delete_product( $variable_product->get_id() );
		wp_delete_user( $user_id );
	}

	/**
	 * @testdox should throw a notice to the cart if an "any" attribute is empty.
	 */
	public function test_add_variation_to_the_cart_with_empty_attributes() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();

		// Get a variation with small pa_size and any pa_colour and pa_number.
		$variation = $variations[0];

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$variation['variation_id'],
			1,
			0,
			array(
				'attribute_pa_colour' => '',
				'attribute_pa_number' => '',
			)
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check for cart contents.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertEquals( 'colour and number are required fields', $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$product->delete( true );
	}

	/**
	 * @testdox should throw a notice to the cart if using variation_id
	 * that doesn't belong to specified variable product.
	 */
	public function test_add_variation_to_the_cart_invalid_variation_id() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$variable_product = WC_Helper_Product::create_variation_product();
		$single_product   = WC_Helper_Product::create_simple_product();

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$variable_product->get_id(),
			1,
			$single_product->get_id()
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check for cart contents.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$expected = sprintf( sprintf( 'The selected product isn\'t a variation of %2$s, please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', esc_url( $variable_product->get_permalink() ), esc_html( $variable_product->get_name() ) ) );
		$this->assertEquals( $expected, $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$variable_product->delete( true );
	}

	/**
	 * @testdox should throw a notice to the cart if using an invalid product_id.
	 */
	public function test_add_variation_to_the_cart_invalid_product() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$single_product = WC_Helper_Product::create_simple_product();

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			-1,
			1,
			$single_product->get_id()
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check for cart contents.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$expected = sprintf( 'The selected product is invalid.' );
		$this->assertEquals( $expected, $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
	}

	/**
	 * @testdox variable product should not be added to the cart if variation_id=0.
	 */
	public function test_add_variation_to_the_cart_zero_variation_id() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$variable_product = WC_Helper_Product::create_variation_product();

		// Add variable and variation_id=0.
		WC()->cart->add_to_cart(
			$variable_product->get_id(),
			1,
			0
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check for cart contents.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about the product option is not selected.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$expected = sprintf( sprintf( 'Please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', esc_url( $variable_product->get_permalink() ), esc_html( $variable_product->get_name() ) ) );
		$this->assertEquals( $expected, $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$variable_product->delete( true );
	}

	/**
	 * Test cloning cart holds no references in session
	 */
	public function test_cloning_cart_session() {
		$product = WC_Helper_Product::create_simple_product();

		// Initialize $cart1 and $cart2 as empty carts.
		$cart1 = WC()->cart;
		$cart1->empty_cart();
		$cart2 = clone $cart1;

		// Create a cart in session.
		$cart1->add_to_cart( $product->get_id(), 1 );
		$cart1->set_session();

		// Empty the cart without clearing the session.
		$cart1->set_cart_contents( array() );

		// Both carts are empty at that point.
		$this->assertTrue( $cart2->is_empty() );
		$this->assertTrue( $cart1->is_empty() );

		$cart2->get_cart_from_session();

		// We retrieved $cart2 from the previously set session so it should not be empty.
		$this->assertFalse( $cart2->is_empty() );

		// We didn't touch $cart1 so it should still be empty.
		$this->assertTrue( $cart1->is_empty() );
	}

	/**
	 * Test show shipping.
	 */
	public function test_show_shipping() {
		// Test with an empty cart.
		$this->assertFalse( WC()->cart->show_shipping() );

		// Add a product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test with "woocommerce_ship_to_countries" disabled.
		$default_ship_to_countries = get_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Test with default "woocommerce_ship_to_countries" and "woocommerce_shipping_cost_requires_address".
		update_option( 'woocommerce_ship_to_countries', $default_ship_to_countries );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Test with "woocommerce_shipping_cost_requires_address" enabled.
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Set address for shipping calculation required for "woocommerce_shipping_cost_requires_address".
		WC()->cart->get_customer()->set_shipping_country( 'US' );
		WC()->cart->get_customer()->set_shipping_state( 'NY' );
		WC()->cart->get_customer()->set_shipping_city( 'New York' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Remove postcode while it is still required, validate shipping is hidden again.
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertFalse( WC()->cart->show_shipping() );

		/**
		 * Make shipping fields postcode optional.
		 * @param array $fields Shipping fields.
		 *
		 * @return array
		 */
		function make_shipping_fields_postcode_optional( $fields ) {
			$fields['shipping_postcode']['required'] = 0;
			return $fields;
		}
		add_filter(
			'woocommerce_shipping_fields',
			'make_shipping_fields_postcode_optional'
		);
		$this->assertTrue( WC()->cart->show_shipping() );
		// Check shipping still shows when postcode is optional and set.
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		remove_all_filters( 'woocommerce_shipping_fields' );
		$this->assertTrue( WC()->cart->show_shipping() );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertFalse( WC()->cart->show_shipping() );

		/**
		 * Make locale postcode optional.
		 * @param array $locales Locales.
		 *
		 * @return array
		 */
		function make_locale_postcode_optional( $locales ) {
			foreach ( $locales as $country => $locale ) {
				$locales[ $country ]['postcode']['required'] = false;
				$locales[ $country ]['postcode']['hidden']   = true;
			}
			return $locales;
		}
		add_filter( 'woocommerce_get_country_locale', 'make_locale_postcode_optional' );

		// Reset locales so they are regenerated with the new postcode optional.
		WC()->countries->locale = null;
		$this->assertTrue( WC()->cart->show_shipping() );
		// Check shipping still shows when postcode is optional and set.
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Check that both fields and locale filter work when both are in use together.
		add_filter(
			'woocommerce_shipping_fields',
			'make_shipping_fields_postcode_optional'
		);
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Check shipping still shows when postcode is optional and set.
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		remove_all_filters( 'woocommerce_shipping_fields' );
		remove_all_filters( 'woocommerce_get_country_locale' );

		/**
		 * Remove unwanted fields from checkout page.
		 *
		 * @param array $fields of checkout fields.
		 *
		 * @return mixed
		 */
		function remove_unwanted_fields_from_checkout_page( $fields ) {
			unset( $fields['shipping']['shipping_company'] );
			unset( $fields['shipping']['shipping_city'] );
			unset( $fields['shipping']['shipping_postcode'] );
			unset( $fields['shipping']['shipping_address_2'] );
			return $fields;
		}
		add_filter( 'woocommerce_checkout_fields', 'remove_unwanted_fields_from_checkout_page' );

		WC()->cart->get_customer()->set_shipping_postcode( '' );
		WC()->cart->get_customer()->set_shipping_city( '' );
		$this->assertTrue( WC()->cart->show_shipping() );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		WC()->cart->get_customer()->set_shipping_city( 'San Francisco' );
		$this->assertTrue( WC()->cart->show_shipping() );

		remove_filter( 'woocommerce_checkout_fields', 'remove_unwanted_fields_from_checkout_page' );

		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_city( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}

	/**
	 * Test show_shipping for countries with various state/postcode requirement.
	 */
	public function test_show_shipping_for_countries_different_shipping_requirements() {
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->cart->empty_cart();
		$this->assertFalse( WC()->cart->show_shipping() );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Country that does not require state.
		WC()->cart->get_customer()->set_shipping_country( 'LB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_city( 'Test' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Country that does not require postcode.
		WC()->cart->get_customer()->set_shipping_country( 'NG' );
		WC()->cart->get_customer()->set_shipping_state( 'AB' );
		WC()->cart->get_customer()->set_shipping_city( 'Test' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_city( 'Test' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}

	/**
	 * Test adding a variable product without selecting variations.
	 *
	 * @see WC_Form_Handler::add_to_cart_action()
	 */
	public function test_form_handler_add_to_cart_action_with_parent_variable_product() {
		$this->tearDown();

		$product                 = WC_Helper_Product::create_variation_product();
		$product_id              = $product->get_id();
		$url                     = get_permalink( $product_id );
		$_REQUEST['add-to-cart'] = $product_id;

		WC_Form_Handler::add_to_cart_action();

		$notices = WC()->session->get( 'wc_notices', array() );

		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertMatchesRegularExpression( '/Please choose product options by visiting/', $notices['error'][0]['notice'] );
	}

	/**
	 * Test case sensitivity fix for coupon discount amounts.
	 *
	 * This test verifies that the fix for issue #58864 works correctly.
	 * It creates a coupon with uppercase code in the database, applies it with lowercase code,
	 * and ensures that get_coupon_discount_amount and get_coupon_discount_tax_amount
	 * return the correct values regardless of case.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/58864
	 */
	public function test_coupon_discount_amount_case_sensitivity() {
		$old_calc_taxes = get_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'TAX20',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a product to add to cart.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create a coupon with uppercase code.
		$coupon = WC_Helper_Coupon::create_coupon( 'TESTCOUPON123' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 10 );
		$coupon->save();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		// Apply the coupon using lowercase code.
		$applied = WC()->cart->apply_coupon( 'testcoupon123' );
		$this->assertTrue( $applied, 'Coupon should be applied successfully with lowercase code' );

		// Verify the coupon is in applied coupons (should be stored in original case).
		$applied_coupons = WC()->cart->get_applied_coupons();
		$this->assertContains( 'testcoupon123', $applied_coupons, 'Coupon should be stored in provided case' );

		// Test get_coupon_discount_amount with lowercase code.
		$discount_amount = WC()->cart->get_coupon_discount_amount( 'testcoupon123' );
		$this->assertEquals( 10.00, $discount_amount, 'get_coupon_discount_amount should return correct value with lowercase code' );

		// Test get_coupon_discount_amount with uppercase code.
		$discount_amount_upper = WC()->cart->get_coupon_discount_amount( 'TESTCOUPON123' );
		$this->assertEquals( 10.00, $discount_amount_upper, 'get_coupon_discount_amount should return correct value with uppercase code' );

		// Test get_coupon_discount_tax_amount with lowercase code.
		$tax_amount = WC()->cart->get_coupon_discount_tax_amount( 'testcoupon123' );
		$this->assertEquals( 2.00, $tax_amount, 'get_coupon_discount_tax_amount should return correct value with lowercase code' );

		// Test get_coupon_discount_tax_amount with uppercase code.
		$tax_amount_upper = WC()->cart->get_coupon_discount_tax_amount( 'TESTCOUPON123' );
		$this->assertEquals( 2.00, $tax_amount_upper, 'get_coupon_discount_tax_amount should return correct value with uppercase code' );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		$product->delete( true );
		$coupon->delete( true );

		// Restore global state.
		update_option( 'woocommerce_calc_taxes', $old_calc_taxes );
		if ( $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}
	}

		/**
		 * Test coupon codes with special characters (ampersands, quotes, etc.).
		 *
		 * This test verifies that coupon codes containing special characters work correctly
		 * when users input them in various formats (raw, HTML-encoded, etc.).
		 * It tests the html_entity_decode() functionality in wc_format_coupon_code().
		 */
	public function test_coupon_codes_with_special_characters() {
		// Create a product to add to cart.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Test cases with various special character scenarios.
		$test_cases = array(
			// Ampersand test cases.
			array(
				'coupon_code' => 'TEST&COUPON-SPECIAL',
				'user_inputs' => array( 'TEST&COUPON-SPECIAL', 'TEST&amp;COUPON-SPECIAL', 'test&coupon-special' ),
				'discount'    => 15,
				'description' => 'Coupon with ampersand',
			),
			// Quote test cases.
			array(
				'coupon_code' => 'TEST&COUPON\'S',
				'user_inputs' => array( 'TEST&COUPON\'S', 'TEST&amp;COUPON\'S', 'test&coupon\'s' ),
				'discount'    => 20,
				'description' => 'Coupon with ampersand and apostrophe',
			),
			// Quote characters test.
			array(
				'coupon_code' => 'TEST"2024"',
				'user_inputs' => array( 'TEST"2024"', 'TEST&quot;2024&quot;', 'test"2024"' ),
				'discount'    => 25,
				'description' => 'Coupon with quote characters',
			),
		);

		foreach ( $test_cases as $test_case ) {
			$coupon_code = $test_case['coupon_code'];
			$user_inputs = $test_case['user_inputs'];
			$discount    = $test_case['discount'];
			$description = $test_case['description'];

			// Create coupon with special characters.
			$coupon = WC_Helper_Coupon::create_coupon( $coupon_code );
			$coupon->set_discount_type( 'fixed_cart' );
			$coupon->set_amount( $discount );
			$coupon->save();

			foreach ( $user_inputs as $user_input ) {
				// Add product to cart and clear any previously applied coupons.
				WC()->cart->add_to_cart( $product->get_id(), 1 );
				WC()->cart->remove_coupons();
				WC()->cart->calculate_totals();

				// Apply coupon with user input variation.
				$applied = WC()->cart->apply_coupon( $user_input );
				$this->assertTrue(
					$applied,
					sprintf(
						'%s: Coupon should be applied successfully with user input "%s"',
						$description,
						$user_input
					)
				);

				// Verify discount amount is correct.
				$applied_coupons = WC()->cart->get_applied_coupons();
				$this->assertNotEmpty( $applied_coupons, sprintf( '%s: Should have applied coupons', $description ) );

				// Get the applied coupon code and verify discount amount.
				$applied_coupon_code = reset( $applied_coupons );
				$discount_amount     = WC()->cart->get_coupon_discount_amount( $applied_coupon_code );
				$this->assertEquals(
					(float) $discount,
					$discount_amount,
					sprintf(
						'%s: Discount amount should be %s for user input "%s"',
						$description,
						$discount,
						$user_input
					)
				);

				WC()->cart->empty_cart();
			}

			$coupon->delete( true );
		}

		// Test edge case: Double-encoded input (simulating form submission issues).
		$coupon = WC_Helper_Coupon::create_coupon( 'COMPANY&CO' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 30 );
		$coupon->save();

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		// Test with double-encoded input (what might come from a problematic form).
		$double_encoded_input = 'COMPANY&amp;amp;CO';
		$applied              = WC()->cart->apply_coupon( $double_encoded_input );
		$this->assertTrue(
			$applied,
			'Double-encoded coupon input should still be applied successfully'
		);

		$applied_coupons = WC()->cart->get_applied_coupons();
		$applied_coupon  = reset( $applied_coupons );
		$discount_amount = WC()->cart->get_coupon_discount_amount( $applied_coupon );
		$this->assertEquals( 30.0, $discount_amount, 'Double-encoded input should produce correct discount' );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		$product->delete( true );
		$coupon->delete( true );
	}


	/**
	 * @testdox should not clear store_api_draft_order from session when cart is not empty
	 */
	public function test_setting_session_should_not_clear_store_api_draft_order_when_cart_is_not_empty() {
		$cart    = WC()->cart;
		$product = WC_Helper_Product::create_simple_product();

		$cart->add_to_cart( $product->get_id() );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'checkout-draft' );
		$order_id = $order->save();
		WC()->session->set( 'store_api_draft_order', $order_id );

		$this->assertEquals( $order_id, WC()->session->get( 'store_api_draft_order' ) );

		$cart->set_session();

		$order = wc_get_order( $order_id );
		$this->assertEquals( $order_id, $order->get_id() );
		$this->assertEquals( $order_id, WC()->session->get( 'store_api_draft_order' ) );
	}


	/**
	 * @testdox should NOT delete non-draft orders when clearing store_api_draft_order from session
	 */
	public function test_emptying_cart_should_not_delete_non_draft_orders() {
		$cart = WC()->cart;

		// Create a processing order (simulating a completed checkout).
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order_id = $order->save();

		// Simulate stale session data where order ID still exists in session.
		WC()->session->set( 'store_api_draft_order', $order_id );

		$cart->empty_cart();

		// Session should be cleared.
		$this->assertNull( WC()->session->get( 'store_api_draft_order' ) );

		// BUT the order should NOT be deleted.
		$order = wc_get_order( $order_id );
		$this->assertNotFalse( $order, 'Non-draft order should not be deleted' );
		$this->assertEquals( 'processing', $order->get_status(), 'Order status should remain unchanged' );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * @testdox should clear shipping data from session when the cart is empty
	 */
	public function test_setting_session_should_clear_shipping_data_when_cart_is_empty() {
		$this->simulate_two_packages();

		WC()->session->set_customer_session_cookie( true );
		WC()->session->set( 'shipping_method_counts', array( 123 ) );
		WC()->session->set( 'previous_shipping_methods', array( 123 ) );
		WC()->session->set( 'shipping_for_package_0', array( 123 ) );
		WC()->session->set( 'shipping_for_package_1', array( 123 ) );
		WC()->session->set( 'chosen_shipping_methods', array( 123 ) );
		WC()->session->save_data();

		$shipping_method_counts    = WC()->session->get( 'shipping_method_counts' );
		$previous_shipping_methods = WC()->session->get( 'previous_shipping_methods' );
		$shipping_for_package_0    = WC()->session->get( 'shipping_for_package_0' );
		$shipping_for_package_1    = WC()->session->get( 'shipping_for_package_1' );
		$chosen_shipping_methods   = WC()->session->get( 'chosen_shipping_methods' );
		$this->assertNotEmpty( $shipping_method_counts );
		$this->assertNotEmpty( $previous_shipping_methods );
		$this->assertNotEmpty( $shipping_for_package_0 );
		$this->assertNotEmpty( $shipping_for_package_1 );
		$this->assertNotEmpty( $chosen_shipping_methods );

		$cart = WC()->cart;
		$cart->set_session();

		$this->assertNull( WC()->session->get( 'shipping_method_counts' ) );
		$this->assertNull( WC()->session->get( 'previous_shipping_methods' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_0' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_1' ) );
		$this->assertNull( WC()->session->get( 'chosen_shipping_methods' ) );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * @testdox should clear shipping data from session when cart products are not shippable
	 */
	public function test_setting_session_should_clear_shipping_data_when_cart_products_are_not_shippable() {
		$this->simulate_two_packages();

		WC()->session->set_customer_session_cookie( true );
		WC()->session->set( 'shipping_method_counts', array( 123 ) );
		WC()->session->set( 'previous_shipping_methods', array( 123 ) );
		WC()->session->set( 'shipping_for_package_0', array( 123 ) );
		WC()->session->set( 'shipping_for_package_1', array( 123 ) );
		WC()->session->set( 'chosen_shipping_methods', array( 123 ) );
		WC()->session->save_data();

		$shipping_method_counts    = WC()->session->get( 'shipping_method_counts' );
		$previous_shipping_methods = WC()->session->get( 'previous_shipping_methods' );
		$shipping_for_package_0    = WC()->session->get( 'shipping_for_package_0' );
		$shipping_for_package_1    = WC()->session->get( 'shipping_for_package_1' );
		$chosen_shipping_methods   = WC()->session->get( 'chosen_shipping_methods' );
		$this->assertNotEmpty( $shipping_method_counts );
		$this->assertNotEmpty( $previous_shipping_methods );
		$this->assertNotEmpty( $shipping_for_package_0 );
		$this->assertNotEmpty( $shipping_for_package_1 );
		$this->assertNotEmpty( $chosen_shipping_methods );

		$virtual_product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );

		$cart = WC()->cart;
		$cart->add_to_cart( $virtual_product->get_id() );

		$cart->set_session();

		$this->assertNull( WC()->session->get( 'shipping_method_counts' ) );
		$this->assertNull( WC()->session->get( 'previous_shipping_methods' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_0' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_1' ) );
		$this->assertNull( WC()->session->get( 'chosen_shipping_methods' ) );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * @testdox should clear shipping data from session when the cart is not empty
	 */
	public function test_setting_session_should_not_clear_shipping_data_when_cart_is_not_empty() {
		$this->simulate_two_packages();

		WC()->session->set( 'shipping_method_counts', array( 123 ) );
		WC()->session->set( 'previous_shipping_methods', array( 123 ) );
		WC()->session->set( 'shipping_for_package_0', array( 123 ) );
		WC()->session->set( 'shipping_for_package_1', array( 123 ) );
		WC()->session->set( 'chosen_shipping_methods', array( 123 ) );

		$product = WC_Helper_Product::create_simple_product();

		$cart = WC()->cart;
		$cart->add_to_cart( $product->get_id() );

		$shipping_method_counts    = WC()->session->get( 'shipping_method_counts' );
		$previous_shipping_methods = WC()->session->get( 'previous_shipping_methods' );
		$shipping_for_package_0    = WC()->session->get( 'shipping_for_package_0' );
		$shipping_for_package_1    = WC()->session->get( 'shipping_for_package_1' );
		$chosen_shipping_methods   = WC()->session->get( 'chosen_shipping_methods' );
		$this->assertNotEmpty( $previous_shipping_methods );
		$this->assertNotEmpty( $shipping_method_counts );
		$this->assertNotEmpty( $shipping_for_package_0 );
		$this->assertNotEmpty( $shipping_for_package_1 );
		$this->assertNotEmpty( $chosen_shipping_methods );

		$cart->set_session();

		$this->assertNotEmpty( WC()->session->get( 'shipping_method_counts' ) );
		$this->assertNotEmpty( WC()->session->get( 'previous_shipping_methods' ) );
		$this->assertNotEmpty( WC()->session->get( 'shipping_for_package_0' ) );
		$this->assertNotEmpty( WC()->session->get( 'shipping_for_package_1' ) );
		$this->assertNotEmpty( WC()->session->get( 'chosen_shipping_methods' ) );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * @testdox should clear shipping data from session when the cart is emptied
	 */
	public function test_emptying_the_cart_should_clear_shipping_data() {
		$this->simulate_two_packages();

		WC()->session->set_customer_session_cookie( true );
		WC()->session->set( 'shipping_method_counts', array( 123 ) );
		WC()->session->set( 'previous_shipping_methods', array( 123 ) );
		WC()->session->set( 'shipping_for_package_0', array( 123 ) );
		WC()->session->set( 'shipping_for_package_1', array( 123 ) );
		WC()->session->set( 'chosen_shipping_methods', array( 123 ) );
		WC()->session->save_data();

		$shipping_method_counts    = WC()->session->get( 'shipping_method_counts' );
		$previous_shipping_methods = WC()->session->get( 'previous_shipping_methods' );
		$shipping_for_package_0    = WC()->session->get( 'shipping_for_package_0' );
		$shipping_for_package_1    = WC()->session->get( 'shipping_for_package_1' );
		$chosen_shipping_methods   = WC()->session->get( 'chosen_shipping_methods' );
		$this->assertNotEmpty( $chosen_shipping_methods );
		$this->assertNotEmpty( $shipping_method_counts );
		$this->assertNotEmpty( $previous_shipping_methods );
		$this->assertNotEmpty( $shipping_for_package_0 );
		$this->assertNotEmpty( $shipping_for_package_1 );

		$cart = WC()->cart;
		$cart->empty_cart();

		$this->assertNull( WC()->session->get( 'shipping_method_counts' ) );
		$this->assertNull( WC()->session->get( 'previous_shipping_methods' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_0' ) );
		$this->assertNull( WC()->session->get( 'shipping_for_package_1' ) );
		$this->assertNull( WC()->session->get( 'chosen_shipping_methods' ) );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * Simulate two shipping packages.
	 */
	private function simulate_two_packages() {
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) {
				$packages[] = $packages[0];
				return $packages;
			}
		);
	}

	/**
	 * @testdox Test that modified products are removed from persistent cart to prevent repeated removal messages
	 */
	public function test_modified_product_removed_from_persistent_cart() {
		// Create a logged-in user to enable persistent cart functionality.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Initialize session for the user.
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set_customer_session_cookie( true );

		// Clear any existing cart and notices.
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		// Create a simple product.
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Add the product to the cart.
		$cart_item_key = WC()->cart->add_to_cart( $product_id, 1 );
		$this->assertNotFalse( $cart_item_key, 'Product should be added to cart successfully' );

		// Get the cart session handler.
		$cart_session = new WC_Cart_Session( WC()->cart );

		// Save the cart to the persistent cart (this happens on shutdown normally).
		$cart_session->persistent_cart_update();

		// Verify the product is in the persistent cart.
		$saved_cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		$this->assertIsArray( $saved_cart_meta );
		$this->assertArrayHasKey( 'cart', $saved_cart_meta );
		$this->assertArrayHasKey( $cart_item_key, $saved_cart_meta['cart'] );

		// Get the cart data with original data hash.
		$cart_data = WC()->session->get( 'cart' );

		// Simulate product modification by manually altering the data_hash in the saved cart
		// This simulates what happens when a product type changes (e.g., simple to variable)
		// We'll set an incorrect data hash to trigger the mismatch.
		$saved_cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		if ( isset( $saved_cart_meta['cart'][ $cart_item_key ] ) ) {
			$saved_cart_meta['cart'][ $cart_item_key ]['data_hash'] = 'modified_hash_' . time();
			update_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), $saved_cart_meta );
		}

		// Clear session cart but keep persistent cart.
		WC()->session->set( 'cart', null );
		WC()->cart->empty_cart( false );

		// Clear notices before reloading cart.
		wc_clear_notices();

		// Reload cart from session - this should trigger the modified product removal.
		$cart_session->get_cart_from_session();

		// Check that a notice was added about the product being removed.
		$all_notices = wc_get_notices();
		$this->assertNotEmpty( $all_notices, 'Expected notices to be added when product is modified' );

		$found_removal_notice = false;
		foreach ( $all_notices as $type => $type_notices ) {
			foreach ( $type_notices as $notice ) {
				// Handle both string and array notice formats.
				$notice_text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : (string) $notice;
				if ( strpos( $notice_text, 'has been removed from your cart because it has since been modified' ) !== false ) {
					$found_removal_notice = true;
					break 2;
				}
			}
		}
		$this->assertTrue( $found_removal_notice, 'Should find the product modification removal notice' );

		// Verify the cart is empty.
		$this->assertCount( 0, WC()->cart->get_cart_contents(), 'Cart should be empty after modified product removal' );

		// Verify the persistent cart is also updated (no longer contains the product).
		$saved_cart_meta_after = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		if ( is_array( $saved_cart_meta_after ) && isset( $saved_cart_meta_after['cart'] ) ) {
			$this->assertArrayNotHasKey( $cart_item_key, $saved_cart_meta_after['cart'], 'Persistent cart should not contain the removed product' );
			$this->assertEmpty( $saved_cart_meta_after['cart'], 'Persistent cart should be empty after product removal' );
		}

		// Clear notices again.
		WC()->session->set( 'wc_notices', null );

		// Reload cart from session again - this should NOT trigger another removal notice.
		$cart_session->get_cart_from_session();

		// Verify no new notices are added (the bug would cause repeated notices).
		$notices_after_reload = wc_get_notices();
		foreach ( $notices_after_reload as $type => $type_notices ) {
			foreach ( $type_notices as $notice ) {
				// Handle both string and array notice formats.
				$notice_text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : (string) $notice;
				$this->assertStringNotContainsString(
					'has been removed from your cart because it has since been modified',
					$notice_text,
					'Should not see removal notice on subsequent page loads'
				);
			}
		}

		// Clean up.
		$product->delete( true );
		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Test that unpurchasable products are removed from persistent cart
	 */
	public function test_unpurchasable_product_removed_from_persistent_cart() {
		// Create a logged-in user to enable persistent cart functionality.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Initialize session for the user.
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set_customer_session_cookie( true );

		// Clear any existing cart and notices.
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		// Create a simple product.
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Add the product to the cart.
		$cart_item_key = WC()->cart->add_to_cart( $product_id, 1 );
		$this->assertNotFalse( $cart_item_key, 'Product should be added to cart successfully' );

		// Get the cart session handler.
		$cart_session = new WC_Cart_Session( WC()->cart );

		// Save the cart to the persistent cart.
		$cart_session->persistent_cart_update();

		// Verify the product is in the persistent cart.
		$saved_cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		$this->assertIsArray( $saved_cart_meta );
		$this->assertArrayHasKey( 'cart', $saved_cart_meta );
		$this->assertArrayHasKey( $cart_item_key, $saved_cart_meta['cart'] );

		// Make the product unpurchasable by removing its price (is_purchasable() returns false when price is empty).
		$product->set_price( '' );
		$product->set_regular_price( '' );
		$product->save();

		// Clear session cart but keep persistent cart.
		WC()->session->set( 'cart', null );
		WC()->cart->empty_cart( false );

		// Clear notices before reloading cart.
		WC()->session->set( 'wc_notices', null );

		// Reload cart from session - this should trigger the unpurchasable product removal.
		$cart_session->get_cart_from_session();

		// Check that an error notice was added about the product being removed.
		$notices = wc_get_notices();
		$this->assertNotEmpty( $notices, 'Expected notices to be added when product becomes unpurchasable' );

		$found_removal_notice = false;
		foreach ( $notices as $type => $type_notices ) {
			foreach ( $type_notices as $notice ) {
				// Handle both string and array notice formats.
				$notice_text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : (string) $notice;
				if ( strpos( $notice_text, 'has been removed from your cart because it can no longer be purchased' ) !== false ) {
					$found_removal_notice = true;
					break 2;
				}
			}
		}
		$this->assertTrue( $found_removal_notice, 'Should find the unpurchasable product removal notice' );

		// Verify the cart is empty.
		$this->assertCount( 0, WC()->cart->get_cart_contents(), 'Cart should be empty after unpurchasable product removal' );

		// Verify the persistent cart is also updated.
		$saved_cart_meta_after = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		if ( is_array( $saved_cart_meta_after ) && isset( $saved_cart_meta_after['cart'] ) ) {
			$this->assertArrayNotHasKey( $cart_item_key, $saved_cart_meta_after['cart'], 'Persistent cart should not contain the removed product' );
			$this->assertEmpty( $saved_cart_meta_after['cart'], 'Persistent cart should be empty after product removal' );
		}

		// Clear notices again.
		WC()->session->set( 'wc_notices', null );

		// Reload cart from session again - this should NOT trigger another removal notice.
		$cart_session->get_cart_from_session();

		// Verify no new notices are added.
		$notices_after_reload = wc_get_notices();
		foreach ( $notices_after_reload as $type => $type_notices ) {
			foreach ( $type_notices as $notice ) {
				// Handle both string and array notice formats.
				$notice_text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : (string) $notice;
				$this->assertStringNotContainsString(
					'has been removed from your cart because it can no longer be purchased',
					$notice_text,
					'Should not see removal notice on subsequent page loads'
				);
			}
		}

		// Clean up.
		$product->delete( true );
		wp_delete_user( $user_id );
	}
}
