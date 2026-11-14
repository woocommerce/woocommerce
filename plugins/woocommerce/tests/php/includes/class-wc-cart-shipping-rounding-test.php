<?php
declare( strict_types = 1 );

/**
 * Class WC_Cart_Shipping_Rounding_Test
 *
 * Regression test for Issue #62692:
 * Order total off by $0.01 when combining percentage-based shipping with tax.
 *
 * @package WooCommerce\Tests\Cart
 * @link https://github.com/woocommerce/woocommerce/issues/62692
 */
class WC_Cart_Shipping_Rounding_Test extends WC_Unit_Test_Case {

	/**
	 * Tax rate ID created during test.
	 *
	 * @var int
	 */
	private $tax_rate_id;

	/**
	 * Product created during test.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Shipping zone created during test.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Flat rate instance ID.
	 *
	 * @var int
	 */
	private $flat_rate_id;

	/**
	 * Shipping enabled state before the test.
	 *
	 * @var bool
	 */
	private $shipping_was_enabled;

	/**
	 * Set up shipping state for the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->shipping_was_enabled = WC()->shipping()->enabled;
		WC()->shipping()->enabled   = true;
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		try {
			if ( $this->zone ) {
				$this->zone->delete();
			}

			if ( $this->flat_rate_id ) {
				delete_option( 'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings' );
			}

			if ( $this->tax_rate_id ) {
				WC_Tax::_delete_tax_rate( $this->tax_rate_id );
			}

			if ( $this->product ) {
				WC_Helper_Product::delete_product( $this->product->get_id() );
			}

			// Clear shipping caches.
			WC_Cache_Helper::get_transient_version( 'shipping', true );
			WC()->shipping()->enabled = $this->shipping_was_enabled;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Tests that percentage-based shipping with tax does not cause $0.01 rounding error.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/62692
	 */
	public function test_percentage_shipping_with_tax_rounding_issue_62692() {
		// 1. Configure WooCommerce Settings.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' ); // Key trigger.
		update_option( 'woocommerce_price_num_decimals', 2 );

		WC()->cart->empty_cart();

		// 2. Create Tax Rate (8.25% on Items Only).
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '8.2500',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0', // Tax does NOT apply to shipping.
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Insert the tax rate into the database.
		$this->tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// 3. Create Product ($110.50).
		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '110.50',
			)
		);

		// 4. Create Shipping Zone with Flat Rate Method (15% fee).
		// Using WC_Shipping_Zone ensures the code path goes through:
		// calculate_totals() -> WC_Shipping_Zone -> WC_Shipping_Flat_Rate::evaluate_cost() -> fee().
		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->set_zone_order( 1 );
		$this->zone->save();

		// Add US location to match force_customer_us_address().
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		// Add flat rate method to zone.
		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );

		// Configure the flat rate instance with percentage cost.
		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Flat rate',
				'tax_status' => 'none', // Shipping is not taxable.
				'cost'       => '[fee percent="15"]',
			)
		);

		// Clear shipping cache and reload methods.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		// 5. Simulate Cart Interaction.
		WC_Helper_Shipping::force_customer_us_address();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		WC()->cart->calculate_totals();

		// 6. Assertions.
		// Expected calculation:
		// Subtotal: $110.50
		// Shipping (15%): $110.50 * 0.15 = $16.575 → rounded to $16.58.
		// Tax (8.25% on items only): $110.50 * 0.0825 = $9.11625 → rounded to $9.12.
		// Total: $110.50 + $16.58 + $9.12 = $136.20.

		$this->assertEquals(
			'110.50',
			wc_format_decimal( WC()->cart->get_subtotal(), 2 ),
			'Subtotal should be $110.50.'
		);

		$this->assertEquals(
			'16.58',
			wc_format_decimal( WC()->cart->get_shipping_total(), 2 ),
			'Shipping should be $16.58 (15% of $110.50 = $16.575, rounded).'
		);

		$this->assertEquals(
			'9.12',
			wc_format_decimal( WC()->cart->get_total_tax(), 2 ),
			'Tax should be $9.12.'
		);

		// THE KEY ASSERTION.
		// Bug behavior: Returns $136.19.
		// Fixed behavior: Returns $136.20.
		$this->assertEquals(
			'136.20',
			wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ),
			'Total should be $136.20. If $136.19, floating-point precision loss occurred in shipping fee calculation.'
		);
	}

	/**
	 * @testdox Shipping tax filters do not compound when cart taxes are merged.
	 * @testWith [0, "no", [{"1": 10.4}], 11.0, {"1": 11.0}, "81"]
	 *           [0, "no", [{"1": 10.4, "2": 1.6}, {"1": 10.4}], 25.0, {"1": 22.0, "2": 3.0}, "165"]
	 *           [2, "no", [{"1": 0.104}], 0.11, {"1": 0.11}, "70.11"]
	 *           [0, "yes", [{"1": 10.4}], 10.4, {"1": 10.4}, "80"]
	 *
	 * @param int    $decimals          Currency decimal places.
	 * @param string $round_at_subtotal Whether taxes round at subtotal.
	 * @param array  $package_taxes     Raw tax entries for each shipping package.
	 * @param float  $expected_tax      Total shipping tax.
	 * @param array  $expected_taxes    Shipping taxes grouped by rate.
	 * @param string $expected_total    Cart total.
	 */
	public function test_shipping_tax_filter_does_not_compound( int $decimals, string $round_at_subtotal, array $package_taxes, float $expected_tax, array $expected_taxes, string $expected_total ): void {
		update_option( 'woocommerce_price_num_decimals', $decimals );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$rates = array();
		foreach ( $package_taxes as $package => $taxes ) {
			$rates[] = new WC_Shipping_Rate( 'test:' . $package, 'Shipping', 70, $taxes );
		}
		$cart = $this->getMockBuilder( WC_Cart::class )->onlyMethods( array( 'calculate_shipping' ) )->getMock();
		$cart->method( 'calculate_shipping' )->willReturn( $rates );
		add_filter( 'wc_round_tax_total', static fn( $rounded ) => 0 < $rounded ? $rounded + 1 : $rounded );

		new WC_Cart_Totals( $cart );

		$this->assertEqualsWithDelta( $expected_tax, $cart->get_shipping_tax(), 0.00001 );
		$this->assertEquals( $expected_taxes, $cart->get_shipping_taxes() );
		$this->assertSame( $expected_total, $cart->get_total( 'edit' ), 'The grand total must not filter already-rounded shipping taxes.' );
		foreach ( $rates as $package => $rate ) {
			$this->assertSame( $package_taxes[ $package ], $rate->get_taxes(), 'Cached shipping rates must retain their raw taxes.' );
		}
		if ( 1 === count( $rates ) ) {
			$this->assertStringContainsString( wc_price( 70 + $expected_tax ), wc_cart_totals_shipping_method_label( $rates[0] ) );
		}
	}

	/**
	 * @testdox Cart shipping tax should match the recalculated order under each tax rounding setting.
	 * @testWith ["yes", 0, "no", "70", 10.0]
	 *           ["yes", 2, "no", "0.70", 0.10]
	 *           ["no", 2, "no", "0.70", 0.11]
	 *           ["yes", 0, "yes", "70", 10.5]
	 *           ["no", 2, "no", "0.70", 0.10, true]
	 *           ["yes", 0, "no", "70", 0.0, false, true]
	 *
	 * @param string $prices_include_tax Whether product prices include tax.
	 * @param int    $decimals           Currency decimal places.
	 * @param string $round_at_subtotal  Whether taxes round at subtotal.
	 * @param string $shipping_cost      Flat rate shipping cost.
	 * @param float  $expected_tax       Expected shipping tax.
	 * @param bool   $filter_rounding    Whether a tax filter overrides the store's rounding mode.
	 * @param bool   $exempt             Whether the customer is exempt from tax.
	 */
	public function test_shipping_tax_matches_order_rounding( string $prices_include_tax, int $decimals, string $round_at_subtotal, string $shipping_cost, float $expected_tax, bool $filter_rounding = false, bool $exempt = false ): void {
		update_option( 'woocommerce_prices_include_tax', $prices_include_tax );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		update_option( 'woocommerce_price_num_decimals', $decimals );
		update_option( 'woocommerce_default_country', 'US:NY' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		$this->tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '15',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
			)
		);
		$this->product     = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '675' ) );
		$coupon            = WC_Helper_Coupon::create_coupon(
			'shipping-tax-rounding',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '10',
			)
		);

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Shipping tax test' );
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();
		$this->flat_rate_id = $this->zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $this->flat_rate_id . '_settings',
			array(
				'enabled'    => 'yes',
				'tax_status' => \Automattic\WooCommerce\Enums\ProductTaxStatus::TAXABLE,
				'cost'       => $shipping_cost,
			)
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();
		WC_Helper_Shipping::force_customer_us_address();
		$customer_was_exempt = WC()->customer->get_is_vat_exempt();
		WC()->customer->set_is_vat_exempt( $exempt );
		WC()->cart->add_to_cart( $this->product->get_id() );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->flat_rate_id ) );
		$round_tax = static function ( $rounded_tax, $value, $precision ) {
			return round( (float) $value, $precision, PHP_ROUND_HALF_DOWN );
		};
		if ( $filter_rounding ) {
			add_filter( 'wc_round_tax_total', $round_tax, 10, 3 );
		}

		try {
			WC()->cart->calculate_totals();
			$rate = WC()->shipping()->get_packages()[0]['rates'][ 'flat_rate:' . $this->flat_rate_id ];
			$this->assertStringContainsString( wc_price( (float) $shipping_cost + $expected_tax ), wc_cart_totals_shipping_method_label( $rate ), 'The offered shipping price must match the calculated shipping charge.' );

			$order = wc_get_order(
				WC()->checkout()->create_order(
					array(
						'payment_method'   => 'bacs',
						'billing_country'  => 'US',
						'shipping_country' => 'US',
						'shipping_state'   => 'NY',
					)
				)
			);
			$order->calculate_totals();

			$this->assertEqualsWithDelta( $expected_tax, WC()->cart->get_shipping_tax(), 0.00001, 'Cart shipping tax should use the configured tax rounding mode.' );
			$this->assertEqualsWithDelta( $expected_tax, $order->get_shipping_tax(), 0.00001, 'Order shipping tax should match the cart.' );
			$this->assertSame( WC()->cart->get_total( 'edit' ), $order->get_total(), 'Recalculating an order must preserve the checkout total.' );
		} finally {
			remove_filter( 'wc_round_tax_total', $round_tax, 10 );
			WC()->customer->set_is_vat_exempt( $customer_was_exempt );
		}
	}
}
