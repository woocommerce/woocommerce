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
	 * @testdox Sequential percentage coupons should be discounted in application order.
	 */
	public function test_sequential_percentage_coupons_are_discounted_in_application_order(): void {
		$original_sequential_setting = get_option( 'woocommerce_calc_discounts_sequentially', null );
		$original_tax_setting        = get_option( 'woocommerce_calc_taxes', null );
		$product                     = null;
		$high_coupon                 = null;
		$low_coupon                  = null;

		try {
			update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );
			update_option( 'woocommerce_calc_taxes', 'no' );
			WC()->cart->empty_cart();

			$product     = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '1.06' ) );
			$high_coupon = WC_Helper_Coupon::create_coupon(
				'sequential-high-' . wp_generate_uuid4(),
				array(
					'discount_type' => 'percent',
					'coupon_amount' => '20',
				)
			);
			$low_coupon  = WC_Helper_Coupon::create_coupon(
				'sequential-low-' . wp_generate_uuid4(),
				array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				)
			);

			WC()->cart->add_to_cart( $product->get_id(), 1 );
			WC()->cart->apply_coupon( $high_coupon->get_code() );
			WC()->cart->apply_coupon( $low_coupon->get_code() );
			WC()->cart->calculate_totals();
			$high_discount = wc_format_decimal( WC()->cart->get_coupon_discount_amount( $high_coupon->get_code() ), 2 );
			$low_discount  = wc_format_decimal( WC()->cart->get_coupon_discount_amount( $low_coupon->get_code() ), 2 );
			$cart_total    = wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 );

			$this->assertSame(
				'0.21',
				$high_discount,
				sprintf(
					'The first-applied 20%% coupon should discount the original product price. Discounts: high %s, low %s; total %s.',
					$high_discount,
					$low_discount,
					$cart_total
				)
			);
			$this->assertSame(
				'0.08',
				$low_discount,
				'The second-applied 10% coupon should discount the remaining product price.'
			);
			$this->assertSame(
				'0.77',
				$cart_total,
				'The cart total should reflect sequential discounts in application order.'
			);
		} finally {
			WC()->cart->empty_cart();
			if ( $product ) {
				$product->delete( true );
			}
			if ( $high_coupon ) {
				$high_coupon->delete( true );
			}
			if ( $low_coupon ) {
				$low_coupon->delete( true );
			}
			if ( null === $original_sequential_setting ) {
				delete_option( 'woocommerce_calc_discounts_sequentially' );
			} else {
				update_option( 'woocommerce_calc_discounts_sequentially', $original_sequential_setting );
			}
			if ( null === $original_tax_setting ) {
				delete_option( 'woocommerce_calc_taxes' );
			} else {
				update_option( 'woocommerce_calc_taxes', $original_tax_setting );
			}
		}
	}

	/**
	 * @testdox Tax-enabled sequential percentage coupon totals should follow application order and reconcile with cart totals.
	 */
	public function test_tax_enabled_sequential_percentage_coupon_totals_follow_application_order(): void {
		$option_not_set              = 'option-not-set-' . wp_generate_uuid4();
		$original_sequential_setting = get_option( 'woocommerce_calc_discounts_sequentially', $option_not_set );
		$original_tax_setting        = get_option( 'woocommerce_calc_taxes', $option_not_set );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', $option_not_set );
		$product                     = null;
		$high_coupon                 = null;
		$low_coupon                  = null;
		$tax_rate_id                 = null;
		$tax_location_filter         = null;
		$matched_tax_rates_filter    = null;

		try {
			update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );
			update_option( 'woocommerce_calc_taxes', 'yes' );
			update_option( 'woocommerce_prices_include_tax', 'no' );
			WC()->cart->empty_cart();

			$tax_rate_id = WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'AQ',
					'tax_rate_state'    => '',
					'tax_rate'          => '10.0000',
					'tax_rate_name'     => 'Tax ' . wp_generate_uuid4(),
					'tax_rate_priority' => '1',
					'tax_rate_compound' => '0',
					'tax_rate_shipping' => '0',
					'tax_rate_order'    => '1',
					'tax_rate_class'    => '',
				)
			);
			$this->assertIsInt( $tax_rate_id, 'The temporary standard tax rate should be created.' );

			$tax_location_filter      = static function () {
				return array( 'AQ', '', '', '' );
			};
			$matched_tax_rates_filter = static function ( $matched_tax_rates ) use ( $tax_rate_id ) {
				return isset( $matched_tax_rates[ $tax_rate_id ] ) ? array( $tax_rate_id => $matched_tax_rates[ $tax_rate_id ] ) : array();
			};
			add_filter( 'woocommerce_get_tax_location', $tax_location_filter, 10, 3 );
			add_filter( 'woocommerce_matched_rates', $matched_tax_rates_filter, 10, 3 );

			$product     = WC_Helper_Product::create_simple_product(
				true,
				array(
					'regular_price' => '100.00',
					'tax_status'    => 'taxable',
				)
			);
			$high_coupon = WC_Helper_Coupon::create_coupon(
				'sequential-high-' . wp_generate_uuid4(),
				array(
					'discount_type' => 'percent',
					'coupon_amount' => '20',
				)
			);
			$low_coupon  = WC_Helper_Coupon::create_coupon(
				'sequential-low-' . wp_generate_uuid4(),
				array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				)
			);
			$high_code   = $high_coupon->get_code();
			$low_code    = $low_coupon->get_code();

			$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ), 'The taxable product fixture should be added to the cart.' );
			$this->assertTrue( WC()->cart->apply_coupon( $high_code ), 'The 20% coupon fixture should be applied first.' );
			$this->assertTrue( WC()->cart->apply_coupon( $low_code ), 'The 10% coupon fixture should be applied second.' );
			WC()->cart->calculate_totals();

			$discount_totals     = array_map(
				static function ( $amount ) {
					return wc_format_decimal( $amount, 2 );
				},
				WC()->cart->get_coupon_discount_totals()
			);
			$discount_tax_totals = array_map(
				static function ( $amount ) {
					return wc_format_decimal( $amount, 2 );
				},
				WC()->cart->get_coupon_discount_tax_totals()
			);

			$this->assertSame(
				array( $high_code, $low_code ),
				WC()->cart->get_applied_coupons(),
				'Applied coupon codes should remain in high-then-low application order.'
			);
			$this->assertSame(
				array(
					$high_code => '20.00',
					$low_code  => '8.00',
				),
				$discount_totals,
				'Per-code discount totals should preserve high-then-low order and sequential amounts.'
			);
			$this->assertSame(
				array(
					$high_code => '2.00',
					$low_code  => '0.80',
				),
				$discount_tax_totals,
				'Every applied coupon should have a discount-tax entry in high-then-low order.'
			);
			$this->assertSame(
				wc_format_decimal( array_sum( $discount_totals ), 2 ),
				wc_format_decimal( WC()->cart->get_discount_total(), 2 ),
				'The sum of normalized per-code discounts should equal the normalized cart discount total.'
			);
			$this->assertSame(
				wc_format_decimal( array_sum( $discount_tax_totals ), 2 ),
				wc_format_decimal( WC()->cart->get_discount_tax(), 2 ),
				'The sum of normalized per-code discount taxes should equal the normalized cart discount tax.'
			);
		} finally {
			if ( null !== $tax_location_filter ) {
				remove_filter( 'woocommerce_get_tax_location', $tax_location_filter, 10 );
			}
			if ( null !== $matched_tax_rates_filter ) {
				remove_filter( 'woocommerce_matched_rates', $matched_tax_rates_filter, 10 );
			}
			WC()->cart->empty_cart();
			if ( $product ) {
				$product->delete( true );
			}
			if ( $high_coupon ) {
				$high_coupon->delete( true );
			}
			if ( $low_coupon ) {
				$low_coupon->delete( true );
			}
			if ( $tax_rate_id ) {
				WC_Tax::_delete_tax_rate( $tax_rate_id );
			}
			if ( $option_not_set === $original_sequential_setting ) {
				delete_option( 'woocommerce_calc_discounts_sequentially' );
			} else {
				update_option( 'woocommerce_calc_discounts_sequentially', $original_sequential_setting );
			}
			if ( $option_not_set === $original_tax_setting ) {
				delete_option( 'woocommerce_calc_taxes' );
			} else {
				update_option( 'woocommerce_calc_taxes', $original_tax_setting );
			}
			if ( $option_not_set === $original_prices_include_tax ) {
				delete_option( 'woocommerce_prices_include_tax' );
			} else {
				update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
			}
		}
	}

	/**
	 * @testdox Sequential percentage coupon discounts should follow application order.
	 * @dataProvider sequential_percentage_coupon_order_provider
	 *
	 * @param array $application_order  Coupon keys in application order.
	 * @param array $expected_discounts Expected per-coupon discounts.
	 */
	public function test_sequential_percentage_coupon_discounts_follow_application_order( array $application_order, array $expected_discounts ): void {
		$result = $this->calculate_cart_coupon_scenario(
			'yes',
			array(
				'coupon_a' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '20',
				),
				'coupon_b' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				),
			),
			$application_order
		);

		$this->assertSame(
			$application_order,
			$result['application_order'],
			'Applied coupon codes should remain in the order supplied to the cart.'
		);
		$this->assertSame(
			$expected_discounts,
			$result['discounts'],
			'Sequential percentage coupon discounts should be calculated in application order.'
		);
		$this->assertSame( '28.00', $result['discount_total'], 'Sequential percentage coupons should discount 28.00 in aggregate.' );
		$this->assertSame( '72.00', $result['cart_total'], 'Sequential percentage coupons should leave a cart total of 72.00.' );
	}

	/**
	 * Data provider for sequential percentage coupon application orders.
	 *
	 * @return array
	 */
	public function sequential_percentage_coupon_order_provider(): array {
		return array(
			'20 percent then 10 percent' => array(
				array( 'coupon_a', 'coupon_b' ),
				array(
					'coupon_a' => '20.00',
					'coupon_b' => '8.00',
				),
			),
			'10 percent then 20 percent' => array(
				array( 'coupon_b', 'coupon_a' ),
				array(
					'coupon_a' => '18.00',
					'coupon_b' => '10.00',
				),
			),
		);
	}

	/**
	 * @testdox Non-sequential percentage coupons should each discount the original cart amount.
	 */
	public function test_non_sequential_percentage_coupons_use_original_cart_amount(): void {
		$result = $this->calculate_cart_coupon_scenario(
			'no',
			array(
				'coupon_a' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '20',
				),
				'coupon_b' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				),
			),
			array( 'coupon_a', 'coupon_b' )
		);

		$this->assertSame(
			array(
				'coupon_a' => '20.00',
				'coupon_b' => '10.00',
			),
			$result['discounts'],
			'Non-sequential percentage coupons should each discount the original 100.00 amount.'
		);
		$this->assertSame( '30.00', $result['discount_total'], 'Non-sequential percentage coupons should discount 30.00 in aggregate.' );
		$this->assertSame( '70.00', $result['cart_total'], 'Non-sequential percentage coupons should leave a cart total of 70.00.' );
	}

	/**
	 * @testdox Non-sequential fixed-cart coupons should preserve the amount-ascending fallback priority.
	 */
	public function test_non_sequential_fixed_cart_coupons_preserve_amount_ascending_fallback_priority(): void {
		$result = $this->calculate_cart_coupon_scenario(
			'no',
			array(
				'coupon_a' => array(
					'discount_type' => 'fixed_cart',
					'coupon_amount' => '80',
				),
				'coupon_b' => array(
					'discount_type' => 'fixed_cart',
					'coupon_amount' => '30',
				),
			),
			array( 'coupon_a', 'coupon_b' )
		);

		$this->assertSame(
			array(
				'coupon_a' => '70.00',
				'coupon_b' => '30.00',
			),
			$result['discounts'],
			'The 30.00 coupon should calculate first, then the 80.00 coupon should be capped at the 70.00 remaining.'
		);
		$this->assertSame( '100.00', $result['discount_total'], 'Non-sequential fixed-cart coupons should discount the full 100.00 cart amount.' );
		$this->assertSame( '0.00', $result['cart_total'], 'Non-sequential fixed-cart coupons should leave a cart total of 0.00.' );
	}

	/**
	 * @testdox Sequential mixed coupon types should preserve the standard type priority.
	 */
	public function test_sequential_mixed_coupon_types_preserve_standard_type_priority(): void {
		$result = $this->calculate_cart_coupon_scenario(
			'yes',
			array(
				'coupon_a' => array(
					'discount_type' => 'fixed_cart',
					'coupon_amount' => '30',
				),
				'coupon_b' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				),
			),
			array( 'coupon_a', 'coupon_b' )
		);

		$this->assertSame(
			array(
				'coupon_a' => '30.00',
				'coupon_b' => '10.00',
			),
			$result['discounts'],
			'The percentage coupon should discount the original amount before the fixed-cart coupon despite application order.'
		);
		$this->assertSame( '40.00', $result['discount_total'], 'Mixed coupon types should discount 40.00 in aggregate.' );
		$this->assertSame( '60.00', $result['cart_total'], 'Mixed coupon types should leave a cart total of 60.00.' );
	}

	/**
	 * @testdox Sequential percentage coupons should preserve unequal positive item-limit priority.
	 */
	public function test_sequential_percentage_coupons_preserve_unequal_item_limit_priority(): void {
		$result = $this->calculate_cart_coupon_scenario(
			'yes',
			array(
				'coupon_a' => array(
					'discount_type'          => 'percent',
					'coupon_amount'          => '20',
					'limit_usage_to_x_items' => 2,
				),
				'coupon_b' => array(
					'discount_type'          => 'percent',
					'coupon_amount'          => '10',
					'limit_usage_to_x_items' => 1,
				),
			),
			array( 'coupon_a', 'coupon_b' )
		);

		$this->assertSame(
			array(
				'coupon_a' => '18.00',
				'coupon_b' => '10.00',
			),
			$result['discounts'],
			'The one-item 10% coupon should calculate before the two-item 20% coupon.'
		);
		$this->assertSame( '28.00', $result['discount_total'], 'Item-limited percentage coupons should discount 28.00 in aggregate.' );
		$this->assertSame( '72.00', $result['cart_total'], 'Item-limited percentage coupons should leave a cart total of 72.00.' );
	}

	/**
	 * @testdox Distinct coupon sort priorities should override sequential application order.
	 */
	public function test_distinct_coupon_sort_priorities_override_sequential_application_order(): void {
		$coupon_sort_filter = static function ( $sort, $coupon ) {
			unset( $sort );
			return 'fixed_cart' === $coupon->get_discount_type() ? 1 : 2;
		};
		$result             = $this->calculate_cart_coupon_scenario(
			'yes',
			array(
				'coupon_a' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				),
				'coupon_b' => array(
					'discount_type' => 'fixed_cart',
					'coupon_amount' => '30',
				),
			),
			array( 'coupon_a', 'coupon_b' ),
			$coupon_sort_filter
		);

		$this->assertSame(
			array(
				'coupon_a' => '7.00',
				'coupon_b' => '30.00',
			),
			$result['discounts'],
			'The fixed-cart coupon priority should make it discount 30.00 before the percentage coupon discounts 7.00.'
		);
		$this->assertSame( '37.00', $result['discount_total'], 'Distinct filter priorities should discount 37.00 in aggregate.' );
		$this->assertSame( '63.00', $result['cart_total'], 'Distinct filter priorities should leave a cart total of 63.00.' );
	}

	/**
	 * @testdox Equal coupon sort priorities should form a sequential application-order group.
	 */
	public function test_equal_coupon_sort_priorities_use_sequential_application_order(): void {
		$coupon_sort_filter = static function () {
			return 5;
		};
		$result             = $this->calculate_cart_coupon_scenario(
			'yes',
			array(
				'coupon_a' => array(
					'discount_type' => 'fixed_cart',
					'coupon_amount' => '30',
				),
				'coupon_b' => array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				),
			),
			array( 'coupon_a', 'coupon_b' ),
			$coupon_sort_filter
		);

		$this->assertSame(
			array(
				'coupon_a' => '30.00',
				'coupon_b' => '7.00',
			),
			$result['discounts'],
			'Equal filter priorities should make the fixed-cart coupon discount 30.00 before the percentage coupon discounts 7.00.'
		);
		$this->assertSame( '37.00', $result['discount_total'], 'Equal filter priorities should discount 37.00 in aggregate.' );
		$this->assertSame( '63.00', $result['cart_total'], 'Equal filter priorities should leave a cart total of 63.00.' );
	}

	/**
	 * Calculate normalized totals for a cart coupon scenario.
	 *
	 * @param string       $sequential_setting Sequential discount setting value.
	 * @param array        $coupon_data         Coupon data keyed by fixture name.
	 * @param array        $application_order   Coupon fixture names in application order.
	 * @param Closure|null $sort_filter         Optional coupon sort filter.
	 * @return array
	 */
	private function calculate_cart_coupon_scenario( string $sequential_setting, array $coupon_data, array $application_order, ?Closure $sort_filter = null ): array {
		$option_not_set              = 'option-not-set-' . wp_generate_uuid4();
		$original_sequential_setting = get_option( 'woocommerce_calc_discounts_sequentially', $option_not_set );
		$original_tax_setting        = get_option( 'woocommerce_calc_taxes', $option_not_set );
		$product                     = null;
		$coupons                     = array();

		try {
			update_option( 'woocommerce_calc_discounts_sequentially', $sequential_setting );
			update_option( 'woocommerce_calc_taxes', 'no' );
			WC()->cart->empty_cart();

			$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '100.00' ) );
			foreach ( $coupon_data as $coupon_key => $data ) {
				$coupons[ $coupon_key ] = WC_Helper_Coupon::create_coupon( 'coupon-' . wp_generate_uuid4(), $data );
			}

			if ( null !== $sort_filter ) {
				add_filter( 'woocommerce_coupon_sort', $sort_filter, 10, 2 );
			}

			$cart_item_key = WC()->cart->add_to_cart( $product->get_id(), 1 );
			$this->assertNotFalse( $cart_item_key, 'The product fixture should be added to the cart.' );
			foreach ( $application_order as $coupon_key ) {
				$this->assertTrue(
					WC()->cart->apply_coupon( $coupons[ $coupon_key ]->get_code() ),
					sprintf( 'Coupon fixture %s should be applied to the cart.', $coupon_key )
				);
			}

			WC()->cart->calculate_totals();
			$discounts           = array();
			$coupon_keys_by_code = array();
			foreach ( $coupons as $coupon_key => $coupon ) {
				$discounts[ $coupon_key ]                   = wc_format_decimal( WC()->cart->get_coupon_discount_amount( $coupon->get_code() ), 2 );
				$coupon_keys_by_code[ $coupon->get_code() ] = $coupon_key;
			}
			$applied_coupon_order = array_map(
				static function ( $coupon_code ) use ( $coupon_keys_by_code ) {
					return $coupon_keys_by_code[ $coupon_code ];
				},
				WC()->cart->get_applied_coupons()
			);

			return array(
				'application_order' => $applied_coupon_order,
				'discounts'         => $discounts,
				'discount_total'    => wc_format_decimal( WC()->cart->get_discount_total(), 2 ),
				'cart_total'        => wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ),
			);
		} finally {
			if ( null !== $sort_filter ) {
				remove_filter( 'woocommerce_coupon_sort', $sort_filter, 10 );
			}
			WC()->cart->empty_cart();
			if ( $product ) {
				$product->delete( true );
			}
			foreach ( $coupons as $coupon ) {
				$coupon->delete( true );
			}
			if ( $option_not_set === $original_sequential_setting ) {
				delete_option( 'woocommerce_calc_discounts_sequentially' );
			} else {
				update_option( 'woocommerce_calc_discounts_sequentially', $original_sequential_setting );
			}
			if ( $option_not_set === $original_tax_setting ) {
				delete_option( 'woocommerce_calc_taxes' );
			} else {
				update_option( 'woocommerce_calc_taxes', $original_tax_setting );
			}
		}
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
