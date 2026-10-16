<?php
declare( strict_types = 1 );

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
		WC()->shipping()->enabled = $this->shipping_was_enabled;
	}

	/**
	 * @testdox Inclusive checkout preserves gross discounts with split tax rates.
	 * @testWith ["no", [50], "430.00", false, 481]
	 *           ["yes", [50], "430.00", false]
	 *           ["no", [], "480.00", false]
	 *           ["no", [30, 20], "430.00", false]
	 *           ["no", [10, 10, 30], "430.00", false]
	 *           ["no", [49.89, 0.01], "430.10", false]
	 *           ["no", [50], "430.08", true]
	 *           ["no", [50.03], "430.00", true, 0, true]
	 *           ["no", [50], "429.49", false, 0, false, 429.48]
	 *
	 * @param string $round_at_subtotal Whether to round tax at subtotal.
	 * @param array  $discounts         Fixed-cart coupon amounts.
	 * @param string $expected_total    Gross cart and order total.
	 * @param bool   $include_fallback  Include a line whose net cannot be adjusted safely.
	 * @param int    $new_price         Updated cart price for coupon allocation refresh.
	 * @param bool   $shared_coupon     Apply one coupon across corrected and unchanged lines.
	 * @param float  $filtered_price    Additional legacy price adjustment.
	 */
	public function test_inclusive_split_tax_preserves_discounted_total( string $round_at_subtotal, array $discounts, string $expected_total, bool $include_fallback, int $new_price = 0, bool $shared_coupon = false, float $filtered_price = 0 ): void {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		update_option( 'woocommerce_price_num_decimals', 2 );
		update_option( 'woocommerce_default_country', 'IN:HR' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );
		WC()->customer->set_billing_country( 'IN' );
		WC()->customer->set_shipping_country( 'IN' );
		WC()->customer->set_billing_state( 'HR' );
		WC()->customer->set_shipping_state( 'HR' );

		foreach ( array( 1, 2 ) as $priority ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'IN',
					'tax_rate_state'    => 'HR',
					'tax_rate'          => '9',
					'tax_rate_name'     => 'GST ' . $priority,
					'tax_rate_priority' => $priority,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 0,
				)
			);
		}
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '480' ) );
		WC()->cart->add_to_cart( $product->get_id() );
		$expected_discounts = array();
		foreach ( $discounts as $index => $discount ) {
			$coupon                                    = WC_Helper_Coupon::create_coupon(
				'split-tax-discount-' . $index,
				array(
					'discount_type' => $include_fallback && ! $shared_coupon ? 'fixed_product' : 'fixed_cart',
					'coupon_amount' => $discount,
					'product_ids'   => (string) $product->get_id(),
				)
			);
			$expected_discounts[ $coupon->get_code() ] = $discount;
			WC()->cart->apply_coupon( $coupon->get_code() );
		}

		if ( $include_fallback ) {
			WC_Tax::create_tax_class( 'Fallback' );
			foreach ( $shared_coupon ? array( 1 ) : array( 1, 2 ) as $priority ) {
				WC_Tax::_insert_tax_rate(
					array(
						'tax_rate_country'  => 'IN',
						'tax_rate_state'    => 'HR',
						'tax_rate'          => $shared_coupon ? '20' : '25',
						'tax_rate_name'     => 'Fallback ' . $priority,
						'tax_rate_priority' => $priority,
						'tax_rate_class'    => 'fallback',
					)
				);
			}
			$fallback = WC_Helper_Product::create_simple_product(
				true,
				array(
					'regular_price' => $shared_coupon ? '0.03' : '0.59',
					'tax_class'     => 'fallback',
				)
			);
			WC()->cart->add_to_cart( $fallback->get_id() );
			if ( ! $shared_coupon ) {
				$coupon                                    = WC_Helper_Coupon::create_coupon(
					'fallback-discount',
					array(
						'discount_type' => 'fixed_product',
						'coupon_amount' => '0.50',
						'product_ids'   => (string) $fallback->get_id(),
					)
				);
				$expected_discounts[ $coupon->get_code() ] = 0.50;
				WC()->cart->apply_coupon( $coupon->get_code() );
			}
		}
		if ( $filtered_price ) {
			add_filter(
				'woocommerce_get_discounted_price',
				static function () use ( $filtered_price ) {
					return $filtered_price;
				}
			);
		}
		WC()->cart->calculate_totals();
		$this->assertSame( $expected_total, WC()->cart->get_total( 'edit' ) );
		if ( $filtered_price ) {
			$this->assertNotContains( true, array_column( WC()->cart->get_cart(), 'line_discount_tax_adjusted' ) );
			$this->assertEqualsWithDelta( 7.62711864, WC()->cart->get_discount_tax(), 0.000001 );
		}
		$net_discounts = WC()->cart->get_coupon_discount_totals();
		$tax_discounts = WC()->cart->get_coupon_discount_tax_totals();
		foreach ( $expected_discounts as $code => $gross ) {
			$this->assertEqualsWithDelta( $gross, $net_discounts[ $code ] + $tax_discounts[ $code ], 0.000001 );
			$this->assertGreaterThanOrEqual( 0, $net_discounts[ $code ], 'Coupon net discounts cannot be negative.' );
			$this->assertGreaterThanOrEqual( 0, $tax_discounts[ $code ], 'Coupon tax discounts cannot be negative.' );
		}

		$classic_order_id = WC()->checkout()->create_order( array( 'payment_method' => 'bacs' ) );
		$this->assertIsInt( $classic_order_id );
		$orders = array(
			wc_get_order( $classic_order_id ),
			( new \Automattic\WooCommerce\StoreApi\Utilities\OrderController() )->create_order_from_cart(),
		);
		foreach ( $orders as $order ) {
			$this->assertSame( $expected_total, $order->get_total() );
			$expected_discount = $filtered_price && $order === $orders[1] ? 50.52 : array_sum( $expected_discounts );
			$this->assertEqualsWithDelta( $expected_discount, (float) $order->get_discount_total() + (float) $order->get_discount_tax(), 0.000001 );
			if ( ! $filtered_price || $order !== $orders[1] ) {
				$this->assertSame( wc_format_decimal( WC()->cart->get_discount_tax(), 2 ), wc_format_decimal( $order->get_discount_tax(), 2 ) );
			}
			foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
				$this->assertEqualsWithDelta( $expected_discounts[ $coupon_item->get_code() ], (float) $coupon_item->get_discount() + (float) $coupon_item->get_discount_tax(), 0.000001 );
			}
		}
		if ( array( 10, 10, 30 ) === $discounts ) {
			$this->assertSame( 1.53, $tax_discounts['split-tax-discount-0'] );
			$this->assertSame( 1.52, $tax_discounts['split-tax-discount-1'] );
		}
		if ( $new_price ) {
			$cart_items = WC()->cart->get_cart();
			reset( $cart_items )['data']->set_price( $new_price );
			WC()->cart->calculate_totals();
			$this->assertNotContains( true, array_column( WC()->cart->get_cart(), 'line_discount_tax_adjusted' ) );
			( new \Automattic\WooCommerce\StoreApi\Utilities\OrderController() )->update_order_from_cart( $orders[1] );
			$updated_taxes = WC()->cart->get_coupon_discount_tax_totals();
			foreach ( $orders[1]->get_items( 'coupon' ) as $coupon_item ) {
				$this->assertSame( wc_format_decimal( $updated_taxes[ $coupon_item->get_code() ], 2 ), wc_format_decimal( $coupon_item->get_discount_tax(), 2 ) );
			}
			$discount_total = $orders[1]->get_discount_total();
			$discount_tax   = $orders[1]->get_discount_tax();
			$orders[1]->calculate_totals();
			$this->assertSame( $discount_total, $orders[1]->get_discount_total() );
			$this->assertSame( $discount_tax, $orders[1]->get_discount_tax() );

			WC()->cart->set_quantity( key( $cart_items ), 2 );
			( new \Automattic\WooCommerce\StoreApi\Utilities\OrderController() )->update_order_from_cart( $orders[1] );
			$this->assertSame( WC()->cart->get_total( 'edit' ), $orders[1]->get_total() );
			$this->assertSame( 2, $orders[1]->get_item_count() );
			$this->assertSame( array_keys( $expected_discounts ), $orders[1]->get_coupon_codes() );
		}
	}

	/**
	 * @testdox Legacy price-filter accounting is preserved when another line could be corrected.
	 */
	public function test_inclusive_split_tax_preserves_mixed_legacy_price_adjustments(): void {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 2 );
		update_option( 'woocommerce_tax_based_on', 'base' );
		foreach ( array( 1, 2 ) as $priority ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate'          => '9',
					'tax_rate_priority' => $priority,
				)
			);
		}
		$first  = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '480' ) );
		$second = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '480' ) );
		WC()->cart->add_to_cart( $first->get_id() );
		WC()->cart->add_to_cart( $second->get_id() );
		$coupon = WC_Helper_Coupon::create_coupon(
			'mixed-filter-discount',
			array(
				'discount_type' => 'fixed_product',
				'coupon_amount' => 50,
			)
		);
		WC()->cart->apply_coupon( $coupon->get_code() );
		add_filter(
			'woocommerce_get_discounted_price',
			static function ( $price, $item ) use ( $second ) {
				return $second->get_id() === $item['product_id'] ? 429.48 : $price;
			},
			10,
			2
		);
		WC()->cart->calculate_totals();
		$this->assertNotContains( true, array_column( WC()->cart->get_cart(), 'line_discount_tax_adjusted' ) );
		$this->assertSame( '859.50', WC()->cart->get_total( 'edit' ) );
		$order = ( new \Automattic\WooCommerce\StoreApi\Utilities\OrderController() )->create_order_from_cart();
		$this->assertSame( '859.50', $order->get_total() );
		$this->assertEqualsWithDelta( 100.51, (float) $order->get_discount_total() + (float) $order->get_discount_tax(), 0.000001 );
		$discount_total = $order->get_discount_total();
		$discount_tax   = $order->get_discount_tax();
		$order->calculate_totals();
		$this->assertSame( $discount_total, $order->get_discount_total() );
		$this->assertSame( $discount_tax, $order->get_discount_tax() );
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
	 * @testdox Coupon sorting should preserve priority and sequential application-order behavior.
	 * @dataProvider data_provider_test_coupon_sorting_preserves_priority_contract
	 *
	 * @param string     $product_price      Product price.
	 * @param string     $sequential_setting Sequential discount setting value.
	 * @param array      $coupon_data         Coupon data keyed by fixture name.
	 * @param array      $application_order   Coupon fixture names in application order.
	 * @param array      $expected_discounts  Expected per-coupon discounts.
	 * @param string     $expected_cart_total Expected cart total.
	 * @param array|null $sort_priorities     Optional filtered priorities keyed by discount type.
	 */
	public function test_coupon_sorting_preserves_priority_contract(
		string $product_price,
		string $sequential_setting,
		array $coupon_data,
		array $application_order,
		array $expected_discounts,
		string $expected_cart_total,
		?array $sort_priorities = null
	): void {
		$result = $this->calculate_cart_coupon_scenario( $product_price, $sequential_setting, $coupon_data, $application_order, $sort_priorities );

		$this->assertSame( $expected_discounts, $result['discounts'], 'Each coupon should receive the discount implied by its effective calculation order.' );
		$this->assertSame( $expected_cart_total, $result['cart_total'], 'The cart total should reflect the expected coupon calculation order.' );
	}

	/**
	 * Data provider for coupon sorting priorities.
	 *
	 * @return array
	 */
	public function data_provider_test_coupon_sorting_preserves_priority_contract(): array {
		return array(
			'sequential 20% then 10%'          => array(
				'100.00',
				'yes',
				array(
					'high' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '20',
					),
					'low'  => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'high', 'low' ),
				array(
					'high' => '20.00',
					'low'  => '8.00',
				),
				'72.00',
			),
			'sequential 10% then 20%'          => array(
				'100.00',
				'yes',
				array(
					'high' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '20',
					),
					'low'  => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'low', 'high' ),
				array(
					'high' => '18.00',
					'low'  => '10.00',
				),
				'72.00',
			),
			// The 100.00 fixtures above hold the cart total steady in both orders, so these two prove
			// the calculation order also moves the total once intermediate rounding is in play.
			'sequential rounding 20% then 10%' => array(
				'12.34',
				'yes',
				array(
					'high' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '20',
					),
					'low'  => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'high', 'low' ),
				array(
					'high' => '2.47',
					'low'  => '0.99',
				),
				'8.88',
			),
			'sequential rounding 10% then 20%' => array(
				'12.34',
				'yes',
				array(
					'high' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '20',
					),
					'low'  => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'low', 'high' ),
				array(
					'high' => '2.22',
					'low'  => '1.23',
				),
				'8.89',
			),
			'non-sequential amount fallback'   => array(
				'100.00',
				'no',
				array(
					'large' => array(
						'discount_type' => 'fixed_cart',
						'coupon_amount' => '80',
					),
					'small' => array(
						'discount_type' => 'fixed_cart',
						'coupon_amount' => '30',
					),
				),
				array( 'large', 'small' ),
				array(
					'large' => '70.00',
					'small' => '30.00',
				),
				'0.00',
			),
			'standard coupon type priority'    => array(
				'100.00',
				'yes',
				array(
					'fixed'   => array(
						'discount_type' => 'fixed_cart',
						'coupon_amount' => '30',
					),
					'percent' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'fixed', 'percent' ),
				array(
					'fixed'   => '30.00',
					'percent' => '10.00',
				),
				'60.00',
			),
			'usage-limit priority'             => array(
				'100.00',
				'yes',
				array(
					'broad'  => array(
						'discount_type'          => 'percent',
						'coupon_amount'          => '20',
						'limit_usage_to_x_items' => 2,
					),
					'narrow' => array(
						'discount_type'          => 'percent',
						'coupon_amount'          => '10',
						'limit_usage_to_x_items' => 1,
					),
				),
				array( 'broad', 'narrow' ),
				array(
					'broad'  => '18.00',
					'narrow' => '10.00',
				),
				'72.00',
			),
			'distinct filtered priorities'     => array(
				'100.00',
				'yes',
				array(
					'percent' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
					'fixed'   => array(
						'discount_type' => 'fixed_cart',
						'coupon_amount' => '30',
					),
				),
				array( 'percent', 'fixed' ),
				array(
					'percent' => '7.00',
					'fixed'   => '30.00',
				),
				'63.00',
				array(
					'fixed_cart' => 1,
					'percent'    => 2,
				),
			),
			'equal filtered priorities'        => array(
				'100.00',
				'yes',
				array(
					'fixed'   => array(
						'discount_type' => 'fixed_cart',
						'coupon_amount' => '30',
					),
					'percent' => array(
						'discount_type' => 'percent',
						'coupon_amount' => '10',
					),
				),
				array( 'fixed', 'percent' ),
				array(
					'fixed'   => '30.00',
					'percent' => '7.00',
				),
				'63.00',
				array(
					'fixed_cart' => 5,
					'percent'    => 5,
				),
			),
		);
	}

	/**
	 * Calculate normalized totals for a cart coupon scenario.
	 *
	 * @param string     $product_price      Product price.
	 * @param string     $sequential_setting Sequential discount setting value.
	 * @param array      $coupon_data         Coupon data keyed by fixture name.
	 * @param array      $application_order   Coupon fixture names in application order.
	 * @param array|null $sort_priorities     Optional filtered priorities keyed by discount type.
	 * @return array
	 */
	private function calculate_cart_coupon_scenario( string $product_price, string $sequential_setting, array $coupon_data, array $application_order, ?array $sort_priorities ): array {
		update_option( 'woocommerce_calc_discounts_sequentially', $sequential_setting );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $product_price ) );
		$coupons = array();
		foreach ( $coupon_data as $coupon_key => $data ) {
			$coupons[ $coupon_key ] = WC_Helper_Coupon::create_coupon( 'test-' . $coupon_key, $data );
		}

		$sort_filter = null;
		if ( null !== $sort_priorities ) {
			$sort_filter = static function ( $sort, $coupon ) use ( $sort_priorities ) {
				return $sort_priorities[ $coupon->get_discount_type() ] ?? $sort;
			};
			add_filter( 'woocommerce_coupon_sort', $sort_filter, 10, 2 );
		}

		WC()->cart->add_to_cart( $product->get_id() );
		foreach ( $application_order as $coupon_key ) {
			WC()->cart->apply_coupon( $coupons[ $coupon_key ]->get_code() );
		}
		WC()->cart->calculate_totals();

		if ( null !== $sort_filter ) {
			remove_filter( 'woocommerce_coupon_sort', $sort_filter, 10 );
		}

		$discounts = array();
		foreach ( $coupons as $coupon_key => $coupon ) {
			$discounts[ $coupon_key ] = wc_format_decimal( WC()->cart->get_coupon_discount_amount( $coupon->get_code() ), 2 );
		}

		return array(
			'discounts'  => $discounts,
			'cart_total' => wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ),
		);
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
