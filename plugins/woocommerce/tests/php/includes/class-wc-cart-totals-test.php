<?php

/**
 * Class WC_Cart_Totals_Tests. Tests for WC_Cart_Total class.
 */
class WC_Cart_Totals_Tests extends WC_Unit_Test_Case {

	/**
	 * Shipping enabled state before the test.
	 *
	 * @var bool
	 */
	private $shipping_was_enabled;

	/**
	 * Set up non-shipping cart total tests.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->shipping_was_enabled = WC()->shipping()->enabled;
		WC()->shipping()->enabled   = false;
	}

	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
		WC()->shipping()->enabled = $this->shipping_was_enabled;
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
	 * A fixed_cart $5 coupon on a $20 product yields a $5 discount and $15 total.
	 */
	public function test_fixed_cart_coupon_discounts_cart_total() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = WC_Helper_Coupon::create_coupon(
			'fixed-cart-off',
			array(
				'discount_type' => 'fixed_cart',
				'coupon_amount' => '5',
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( 5.0, WC()->cart->get_discount_total(), 0.001, 'fixed_cart $5 should discount $5' );
		$this->assertEquals( '15.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'fixed_cart $5 on $20 should total $15' );

		WC()->cart->empty_cart();
		$product->delete( true );
		$coupon->delete( true );
	}

	/**
	 * A percent 50% coupon on a $20 product yields a $10 discount and $10 total.
	 */
	public function test_percent_coupon_discounts_cart_total() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = WC_Helper_Coupon::create_coupon(
			'percent-off',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '50',
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( 10.0, WC()->cart->get_discount_total(), 0.001, 'percent 50% should discount $10' );
		$this->assertEquals( '10.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'percent 50% on $20 should total $10' );

		WC()->cart->empty_cart();
		$product->delete( true );
		$coupon->delete( true );
	}

	/**
	 * A fixed_product $7 coupon on a $20 product yields a $7 discount and $13 total.
	 */
	public function test_fixed_product_coupon_discounts_cart_total() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = WC_Helper_Coupon::create_coupon(
			'fixed-product-off',
			array(
				'discount_type' => 'fixed_product',
				'coupon_amount' => '7',
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( 7.0, WC()->cart->get_discount_total(), 0.001, 'fixed_product $7 should discount $7' );
		$this->assertEquals( '13.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'fixed_product $7 on $20 should total $13' );

		WC()->cart->empty_cart();
		$product->delete( true );
		$coupon->delete( true );
	}

	/**
	 * Removing an applied coupon restores the cart to its undiscounted total.
	 */
	public function test_cart_total_restored_after_coupon_removed() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = WC_Helper_Coupon::create_coupon(
			'fixed-cart-restore',
			array(
				'discount_type' => 'fixed_cart',
				'coupon_amount' => '5',
			)
		);

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		// Sanity: coupon is applied.
		$this->assertEquals( '15.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'coupon should reduce total to $15' );

		// Act: remove the coupon.
		WC()->cart->remove_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		// Assert: total restored to base, discount cleared.
		$this->assertEqualsWithDelta( 0.0, WC()->cart->get_discount_total(), 0.001, 'discount total should be cleared after removal' );
		$this->assertEquals( '20.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'total should return to $20 after coupon removed' );

		WC()->cart->empty_cart();
		$product->delete( true );
		$coupon->delete( true );
	}
}
