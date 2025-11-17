<?php

/**
 * Class WC_Cart_Totals_Tests. Tests for WC_Cart_Total class.
 */
class WC_Cart_Totals_Tests extends WC_Unit_Test_Case {

	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Tests whether discount tax is rounded properly in cart.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/23916.
	 */
	public function test_discount_tax_rounding() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		WC()->cart->empty_cart();

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '27.0000',
			'tax_rate_name'     => 'TAX27',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );
		$product_240  = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 240 ) );
		$product_1250 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 1250 ) );
		$product_1990 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 1990 ) );
		$product_3390 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 3390 ) );
		$product_6200 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 6200 ) );
		$coupon       = WC_Helper_Coupon::create_coupon( 'flat2000', array( 'coupon_amount' => 2000 ) );

		WC()->cart->add_to_cart( $product_240->get_id(), 1 );
		WC()->cart->add_to_cart( $product_1250->get_id(), 1 );
		WC()->cart->add_to_cart( $product_1990->get_id(), 1 );
		WC()->cart->add_to_cart( $product_3390->get_id(), 1 );
		WC()->cart->add_to_cart( $product_6200->get_id(), 1 );
		WC()->cart->apply_coupon( $coupon->get_code() );

		$this->assert_discount_tax_rounding_when_rounding_at_subtotal();
		$this->assert_discount_tax_rounding_when_rounding_at_line();
	}

	/**
	 * Helper method for assertions when prices are rounded at line.
	 */
	private function assert_discount_tax_rounding_when_rounding_at_line() {
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		$decimal_precision = wc_get_price_decimals();
		update_option( 'woocommerce_price_num_decimals', 0 );

		WC()->cart->calculate_totals();
		update_option( 'woocommerce_price_num_decimals', $decimal_precision );

		$this->assertEquals( '1575', wc_format_decimal( WC()->cart->get_discount_total(), 0 ) );
		$this->assertEquals( '425', wc_format_decimal( WC()->cart->get_discount_tax(), 0 ) );
		$this->assertEquals( '11070', wc_format_decimal( WC()->cart->get_total( 'edit' ), 0 ) );
	}

	/**
	 * Helper method for assertions when prices are rounded at line.
	 */
	private function assert_discount_tax_rounding_when_rounding_at_subtotal() {
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		$decimal_precision = wc_get_price_decimals();
		update_option( 'woocommerce_price_num_decimals', 0 );

		WC()->cart->calculate_totals();
		update_option( 'woocommerce_price_num_decimals', $decimal_precision );

		$this->assertEquals( '1575', wc_format_decimal( WC()->cart->get_discount_total(), 0 ) );
		$this->assertEquals( '425', wc_format_decimal( WC()->cart->get_discount_tax(), 0 ) );
		$this->assertEquals( '11070', wc_format_decimal( WC()->cart->get_total( 'edit' ), 0 ) );
	}

	/**
	 * Tests whether subtotal is properly rounded, when prices entered have higher precision than displayed.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/28292.
	 */
	public function test_subtotal_rounding_with_changing_precision() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		$decimal_precision = wc_get_price_decimals();
		update_option( 'woocommerce_price_num_decimals', 0 );

		WC()->cart->empty_cart();

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '23.0000',
			'tax_rate_name'     => 'TAX23',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );
		$product_301_90909 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 301.90909 ) );

		WC()->cart->add_to_cart( $product_301_90909->get_id() );
		WC()->cart->calculate_totals();
		update_option( 'woocommerce_price_num_decimals', $decimal_precision );

		// Notice how subtotal + tax does not equate to total here.
		// This is feature of round at subtotal property, where since we are not rounding, displayed components of price may not add up to displayed total price.
		$this->assertEquals( '245', wc_format_decimal( WC()->cart->get_subtotal(), 0 ) );
		$this->assertEquals( '302', wc_format_decimal( WC()->cart->get_total( 'edit' ), 0 ) );
		$this->assertEquals( '56', wc_format_decimal( WC()->cart->get_total_tax(), 0 ) );
	}

	/**
	 * Test subtotal and total are rounded correctly when values are entered with more precision.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/24184#issue-469311323.
	 */
	public function test_total_rounding_with_price_entered_has_high_precision() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		WC()->cart->empty_cart();

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
		WC_Tax::_insert_tax_rate( $tax_rate );
		$product_30_82500 = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 30.82500 ) );

		WC()->cart->add_to_cart( $product_30_82500->get_id() );

		WC()->cart->calculate_totals();
		// Since prices entered have higher precision, subtotal + tax will not equal to total.
		$this->assertEquals( '30.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '36.99', WC()->cart->get_total( 'edit' ) );
		$this->assertEquals( '6.17', WC()->cart->get_total_tax() );
	}

	/**
	 * Test cart totals when prices are entered inclusive of tax but no base tax rate exists.
	 *
	 * This test verifies the fix for WOOPLUG-5511 where cart totals were incorrectly inflated
	 * when:
	 * - Prices are entered inclusive of tax
	 * - No base location tax rate exists (empty base rate array)
	 * - Customer has a tax rate configured
	 *
	 * Before the fix, WC_Product::get_price_including_tax() would incorrectly apply the customer's
	 * tax rate on top of the already tax-inclusive price, causing price inflation in the cart.
	 *
	 * Expected behavior:
	 * - Cart subtotal should equal the product price (NOT inflated)
	 * - No additional tax should be added on top of the inclusive price
	 * - Calculations should work correctly with multiple quantities and different tax rates
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/WOOPLUG-5511
	 */
	public function test_cart_tax_inclusive_prices_with_no_base_rate_wooplug_5511() {
		// Capture original settings to restore later.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes' );
		$original_default_country    = get_option( 'woocommerce_default_country' );
		$original_billing_country    = WC()->customer->get_billing_country();
		$original_shipping_country   = WC()->customer->get_shipping_country();

		// Configure WooCommerce for tax-inclusive pricing.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Set store base location to a country with NO configured tax rate.
		// This simulates the bug scenario where the store has a base location
		// but hasn't configured a tax rate for it (results in empty base rate array).
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Create customer tax rate for Austria at 20%.
		$customer_tax_rate_data = array(
			'tax_rate_country'  => 'AT',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'Austria VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$customer_tax_rate_id   = WC_Tax::_insert_tax_rate( $customer_tax_rate_data );

		// Set customer location to Austria (where we have a tax rate).
		WC()->customer->set_billing_country( 'AT' );
		WC()->customer->set_shipping_country( 'AT' );
		WC()->customer->set_is_vat_exempt( false );

		// Create a product with tax-inclusive price of 100.
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 100 ) );

		// Empty cart and add product.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Calculate cart totals.
		WC()->cart->calculate_totals();

		// ASSERTION 1: Cart subtotal (excl. tax) should be the base price.
		// With 100 price inclusive of 20% tax: base = 100 / 1.20 = 83.33.
		$this->assertEquals(
			83.33,
			round( WC()->cart->get_subtotal(), 2 ),
			'Cart subtotal should be 83.33 (price excluding the 20% customer tax)'
		);

		// ASSERTION 2: Cart total should equal the tax-inclusive product price.
		$this->assertEquals(
			100,
			WC()->cart->get_total( 'edit' ),
			'Cart total should equal product price of 100'
		);

		// ASSERTION 3: Verify cart item line total (excl. tax) matches subtotal.
		$cart_contents = WC()->cart->get_cart();
		$cart_item     = reset( $cart_contents );
		$this->assertEquals(
			83.33,
			round( $cart_item['line_total'], 2 ),
			'Cart item line total should be 83.33 (excluding tax)'
		);

		// Test with multiple quantities.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 3 );
		WC()->cart->calculate_totals();

		$this->assertEquals(
			250,
			round( WC()->cart->get_subtotal(), 2 ),
			'Cart subtotal with qty=3 should be 250 (3 * 83.33, excluding tax)'
		);

		$this->assertEquals(
			300,
			WC()->cart->get_total( 'edit' ),
			'Cart total with qty=3 should be 300 (including tax)'
		);

		// Test with a different tax rate (10%).
		WC_Tax::_delete_tax_rate( $customer_tax_rate_id );
		$customer_tax_rate_10_data = array(
			'tax_rate_country'  => 'AT',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Austria VAT 10%',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$customer_tax_rate_10_id   = WC_Tax::_insert_tax_rate( $customer_tax_rate_10_data );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		$this->assertEquals(
			90.91,
			round( WC()->cart->get_subtotal(), 2 ),
			'Cart subtotal should be 90.91 (100 / 1.10, excluding 10% tax)'
		);

		// Test with a higher tax rate (25%).
		WC_Tax::_delete_tax_rate( $customer_tax_rate_10_id );
		$customer_tax_rate_25_data = array(
			'tax_rate_country'  => 'AT',
			'tax_rate_state'    => '',
			'tax_rate'          => '25.0000',
			'tax_rate_name'     => 'Austria VAT 25%',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$customer_tax_rate_25_id   = WC_Tax::_insert_tax_rate( $customer_tax_rate_25_data );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		$this->assertEquals(
			80,
			round( WC()->cart->get_subtotal(), 2 ),
			'Cart subtotal should be 80 (100 / 1.25, excluding 25% tax)'
		);

		// Clean up: Delete all tax rates and product.
		WC_Tax::_delete_tax_rate( $customer_tax_rate_25_id );
		WC_Helper_Product::delete_product( $product->get_id() );

		// Restore original settings and customer location.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
		update_option( 'woocommerce_default_country', $original_default_country );
		WC()->customer->set_billing_country( $original_billing_country );
		WC()->customer->set_shipping_country( $original_shipping_country );

		// Ensure cart is empty.
		WC()->cart->empty_cart();
	}

	/**
	 * REGRESSION TEST: Verify price adjustment still works when base rate exists.
	 * This ensures the WOOPLUG-5511 fix doesn't break existing price adjustment logic.
	 *
	 * Uses a filter hook to ensure base tax rates are properly recognized in the test environment.
	 */
	public function test_cart_tax_adjustment_with_base_rate_wooplug_5511_regression() {
		// Capture original settings.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes' );
		$original_default_country    = get_option( 'woocommerce_default_country' );
		$original_billing_country    = WC()->customer->get_billing_country();
		$original_shipping_country   = WC()->customer->get_shipping_country();

		// Configure WooCommerce for tax-inclusive pricing.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Set store base location WITH a tax rate (US:CA at 10%).
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Force WC to recognize the updated base location.
		WC()->countries = new WC_Countries();

		// Create BASE tax rate for store location (10%).
		$base_tax_rate_data = array(
			'tax_rate_country'  => 'US',
			'tax_rate_state'    => 'CA',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Base Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$base_tax_rate_id   = WC_Tax::_insert_tax_rate( $base_tax_rate_data );

		// Create CUSTOMER tax rate for Austria (25%).
		$customer_tax_rate_data = array(
			'tax_rate_country'  => 'AT',
			'tax_rate_state'    => '',
			'tax_rate'          => '25.0000',
			'tax_rate_name'     => 'Austria VAT 25%',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$customer_tax_rate_id   = WC_Tax::_insert_tax_rate( $customer_tax_rate_data );

		// Set customer location to Austria.
		WC()->customer->set_billing_country( 'AT' );
		WC()->customer->set_shipping_country( 'AT' );
		WC()->customer->set_is_vat_exempt( false );

		// Create product with tax-inclusive price of 100.
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 100 ) );

		// Add to cart and calculate.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		// When base rate is 10% and customer rate is 25%:
		// - Base price (excl. tax): 100 / 1.10 = 90.909090... = 90.91.
		// - Customer tax: 90.91 * 25% = 22.7275 = 22.73.
		// - Customer total: 90.91 + 22.73 = 113.64.

		// ASSERTION 1: Subtotal should be the tax-exclusive base price.
		$this->assertEquals(
			90.91,
			round( WC()->cart->get_subtotal(), 2 ),
			'Cart subtotal should be 90.91 (base price excl. tax)'
		);

		// ASSERTION 2: Tax should be the customer's tax amount.
		$this->assertEquals(
			22.73,
			round( WC()->cart->get_total_tax(), 2 ),
			'Cart tax should be 22.73 (25% of 90.91)'
		);

		// ASSERTION 3: Total should include the customer's tax.
		$this->assertEquals(
			113.64,
			round( WC()->cart->get_total( 'edit' ), 2 ),
			'Cart total should be 113.64 (90.91 + 22.73) when both base (10%) and customer (25%) rates exist'
		);

		// Clean up.
		WC_Tax::_delete_tax_rate( $base_tax_rate_id );
		WC_Tax::_delete_tax_rate( $customer_tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );

		// Restore original settings.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
		update_option( 'woocommerce_default_country', $original_default_country );
		WC()->customer->set_billing_country( $original_billing_country );
		WC()->customer->set_shipping_country( $original_shipping_country );

		WC()->cart->empty_cart();
	}
}
