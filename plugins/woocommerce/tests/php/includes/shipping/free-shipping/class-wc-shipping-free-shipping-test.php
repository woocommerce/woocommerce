<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Tests when WC_Shipping_Free_Shipping offers itself to the shopper.
 *
 * Most expected values here are taken from what the settings screen promises the merchant, in
 * init_form_fields() of the method itself, rather than from reading is_available(). The five
 * "Free shipping requires" options, the minimum order amount ("Customers will need to spend this
 * amount to get free shipping") and the coupon discount rule ("Apply minimum order rule before
 * coupon discount") are the contract; the mechanism behind them is not.
 */
class WC_Shipping_Free_Shipping_Test extends WC_Unit_Test_Case {

	/**
	 * Shipping zone holding the method under test.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Instance id of the free shipping method in that zone.
	 *
	 * @var int
	 */
	private $instance_id;

	/**
	 * The System Under Test.
	 *
	 * @var WC_Shipping_Free_Shipping
	 */
	private $sut;

	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Free shipping' );
		$this->zone->add_location( 'US', 'country' );
		$this->zone->save();

		$this->instance_id = $this->zone->add_shipping_method( 'free_shipping' );
	}

	/**
	 * Save instance settings and build the method that reads them.
	 *
	 * @param array $settings Instance settings to store.
	 */
	private function method_with( array $settings ): void {
		update_option(
			'woocommerce_free_shipping_' . $this->instance_id . '_settings',
			array_merge(
				array(
					'title'            => 'Free shipping',
					'requires'         => '',
					'min_amount'       => '0',
					'ignore_discounts' => 'no',
				),
				$settings
			)
		);

		$this->sut = new WC_Shipping_Free_Shipping( $this->instance_id );
	}

	/**
	 * Put a product worth the given amount in the cart.
	 *
	 * @param float $price Product price.
	 */
	private function cart_holding( float $price ): void {
		$product = new WC_Product_Simple();
		$product->set_regular_price( (string) $price );
		$product->save();

		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ), 'The fixture product should reach the cart.' );

		WC()->cart->calculate_totals();

		// Asserted as a count rather than a subtotal, because the subtotal is tax-mode dependent
		// and one test here runs with prices including tax.
		$this->assertCount( 1, WC()->cart->get_cart(), 'The cart should hold the fixture product.' );
	}

	/**
	 * Apply a coupon to the cart.
	 *
	 * @param string $code Coupon code.
	 * @param array  $meta Coupon meta, for example free_shipping or coupon_amount.
	 */
	private function apply_coupon( string $code, array $meta ): void {
		WC_Helper_Coupon::create_coupon( $code, $meta );

		$this->assertTrue( WC()->cart->apply_coupon( $code ), 'The fixture coupon should apply, or a test expecting no free shipping proves nothing.' );

		WC()->cart->calculate_totals();
	}

	/**
	 * Switch taxes on at ten percent and choose how prices are stored and shown.
	 *
	 * @param string $prices_include_tax Whether catalog prices already include tax.
	 * @param string $display            Whether the cart shows prices including tax.
	 */
	private function taxes_at_ten_percent( string $prices_include_tax, string $display ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', $prices_include_tax );
		update_option( 'woocommerce_tax_display_cart', $display );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => '1',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '1',
			)
		);
	}

	/**
	 * Add a fee to the cart, the way an extension does.
	 *
	 * @param float $amount Fee amount.
	 */
	private function fee_of( float $amount ): void {
		add_action(
			'woocommerce_cart_calculate_fees',
			static function ( $cart ) use ( $amount ) {
				$cart->add_fee( 'Handling', $amount );
			}
		);

		WC()->cart->calculate_totals();

		$this->assertEquals( $amount, WC()->cart->get_fee_total(), 'The fixture fee should reach the cart.' );
	}

	/**
	 * Whether the method currently offers itself.
	 *
	 * @return bool
	 */
	private function is_offered(): bool {
		return $this->sut->is_available( array( 'destination' => array( 'country' => 'US' ) ) );
	}

	/**
	 * The threshold reads get_displayed_subtotal(), which is built from the line items, so a fee
	 * added through woocommerce_cart_calculate_fees sits outside it.
	 *
	 * @testdox A cart fee does not count towards the minimum.
	 */
	public function test_a_cart_fee_does_not_count_towards_the_minimum(): void {
		$this->cart_holding( 90.0 );
		$this->fee_of( 15.0 );

		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '100',
			)
		);

		$this->assertFalse(
			$this->is_offered(),
			'The shopper pays 105.00 but spends 90.00 on goods, and 90.00 does not reach a 100.00 minimum.'
		);
	}

	/**
	 * is_available() decides from the cart and never reads the package it is given, so a package
	 * holding part of a qualifying cart is still offered the rate.
	 *
	 * @testdox The minimum is judged on the cart, not on the package being priced.
	 */
	public function test_the_minimum_is_judged_on_the_cart_not_the_package(): void {
		$this->cart_holding( 100.0 );

		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '100',
			)
		);

		$half = array(
			'destination'   => array( 'country' => 'US' ),
			'contents_cost' => 50.0,
		);

		$this->assertTrue(
			$this->sut->is_available( $half ),
			'A shopper who spent 100.00 has met a 100.00 minimum, whichever part of their order is being priced.'
		);
	}

	/**
	 * The option is worded "No requirement", so neither half of the rule is consulted. The minimum
	 * here is set out of reach and the coupon in the cart grants no free shipping, so a method that
	 * started reading either of them would refuse.
	 *
	 * @testdox With no requirement neither the minimum nor a coupon is consulted.
	 */
	public function test_no_requirement_consults_neither_the_minimum_nor_a_coupon(): void {
		$this->method_with(
			array(
				'requires'   => '',
				'min_amount' => '500',
			)
		);

		$this->cart_holding( 1.0 );
		$this->apply_coupon( 'no-requirement-plain-discount', array( 'free_shipping' => 'no' ) );

		$this->assertTrue(
			$this->is_offered(),
			'A cart far under the stored minimum, holding a coupon that grants nothing, should still be offered free shipping.'
		);
	}

	/**
	 * "Customers will need to spend this amount to get free shipping" reads as the amount being
	 * enough, so spending exactly it qualifies and a cent less does not.
	 *
	 * @testdox A minimum order amount is met exactly, exceeded, or missed by a cent.
	 *
	 * @testWith [50.00, true]
	 *           [50.01, true]
	 *           [49.99, false]
	 *
	 * @param float $cart_total Value of the cart.
	 * @param bool  $expected   Whether free shipping should be offered.
	 */
	public function test_minimum_order_amount_boundary( float $cart_total, bool $expected ): void {
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '50',
			)
		);
		$this->cart_holding( $cart_total );

		$this->assertSame( $expected, $this->is_offered(), 'A cart of ' . $cart_total . ' against a 50.00 minimum.' );
	}

	/**
	 * The option reads "A valid free shipping coupon", so both halves matter.
	 *
	 * @testdox A coupon requirement is met only by a valid coupon that grants free shipping.
	 */
	public function test_coupon_requirement_needs_a_free_shipping_coupon(): void {
		$this->method_with( array( 'requires' => 'coupon' ) );
		$this->cart_holding( 100.0 );

		$this->apply_coupon( 'plain-discount', array( 'free_shipping' => 'no' ) );
		$this->assertFalse( $this->is_offered(), 'A coupon that does not grant free shipping should not qualify.' );

		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );
		$this->assertTrue( $this->is_offered(), 'A coupon that grants free shipping should qualify.' );
	}

	/**
	 * The option says "A valid free shipping coupon", so a coupon that has stopped being valid
	 * since it was applied, an expired one for instance, must stop qualifying too.
	 *
	 * @testdox A free shipping coupon that is no longer valid stops qualifying.
	 */
	public function test_an_invalid_free_shipping_coupon_does_not_qualify(): void {
		$this->method_with( array( 'requires' => 'coupon' ) );
		$this->cart_holding( 100.0 );
		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );

		$this->assertTrue( $this->is_offered(), 'The coupon should qualify while it is valid.' );

		$coupon = new WC_Coupon( 'ships-free' );
		$coupon->set_date_expires( time() - DAY_IN_SECONDS );
		$coupon->save();

		WC()->cart->calculate_totals();

		$this->assertFalse( $this->is_offered(), 'An expired coupon should no longer qualify.' );
	}

	/**
	 * @testdox Removing the free shipping coupon withdraws free shipping again.
	 */
	public function test_removing_the_coupon_withdraws_free_shipping(): void {
		$this->method_with( array( 'requires' => 'coupon' ) );
		$this->cart_holding( 100.0 );
		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );

		$this->assertTrue( $this->is_offered(), 'The coupon should qualify while it is applied.' );

		WC()->cart->remove_coupon( 'ships-free' );
		WC()->cart->calculate_totals();

		$this->assertFalse( $this->is_offered(), 'Removing the coupon should withdraw free shipping.' );
	}

	/**
	 * "A minimum order amount OR coupon" against "A minimum order amount AND coupon".
	 *
	 * @testdox Either is satisfied by one condition, both needs the two together.
	 *
	 * @testWith ["either", false, false, false]
	 *           ["either", true, false, true]
	 *           ["either", false, true, true]
	 *           ["either", true, true, true]
	 *           ["both", false, false, false]
	 *           ["both", true, false, false]
	 *           ["both", false, true, false]
	 *           ["both", true, true, true]
	 *
	 * @param string $requires     The requirement setting.
	 * @param bool   $meets_amount Whether the cart reaches the minimum.
	 * @param bool   $has_coupon   Whether a free shipping coupon is applied.
	 * @param bool   $expected     Whether free shipping should be offered.
	 */
	public function test_either_and_both_combine_the_two_conditions( string $requires, bool $meets_amount, bool $has_coupon, bool $expected ): void {
		$this->method_with(
			array(
				'requires'   => $requires,
				'min_amount' => '50',
			)
		);
		$this->cart_holding( $meets_amount ? 60.0 : 10.0 );

		if ( $has_coupon ) {
			$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );
		}

		$this->assertSame(
			$expected,
			$this->is_offered(),
			sprintf(
				'%s with the minimum %s and a free shipping coupon %s.',
				$requires,
				$meets_amount ? 'met' : 'missed',
				$has_coupon ? 'applied' : 'absent'
			)
		);
	}

	/**
	 * The threshold is compared against get_displayed_subtotal(), which carries tax when the store
	 * displays prices that way, and the discount taken off it drops its tax portion to match.
	 *
	 * @testdox On a tax-inclusive store the coupon's tax also comes off the minimum.
	 */
	public function test_the_minimum_follows_the_tax_inclusive_amount_the_shopper_sees(): void {
		$this->taxes_at_ten_percent( 'yes', 'incl' );

		$this->cart_holding( 110.0 );
		$this->apply_coupon( 'eleven-off', array( 'coupon_amount' => '11' ) );

		// Without this the test would read the same whether tax applied or not, and would go on
		// passing if the tax setup ever stopped working.
		$this->assertEquals( 1.0, WC()->cart->get_discount_tax(), 'The coupon should carry 1.00 of tax for the minimum to have to account for.' );

		// The shopper sees 110.00 and hands over 11.00 of it, so 99.00 is what they spent.
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '99',
			)
		);

		$this->assertTrue(
			$this->is_offered(),
			'The 99.00 the shopper actually spent should meet a 99.00 minimum, not the lower amount left after tax is stripped out.'
		);

		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '100',
			)
		);

		$this->assertFalse(
			$this->is_offered(),
			'A 110.00 tax-inclusive cart less an 11.00 coupon leaves 99.00 for the shopper, short of the 100.00 minimum.'
		);
	}

	/**
	 * A shopper who lands exactly on the minimum has met it. Floating point subtraction can put
	 * the result a hair below, and the rounding to the store's price decimals is what stops that
	 * from quietly denying free shipping.
	 *
	 * @testdox A discounted cart landing exactly on the minimum still qualifies.
	 */
	public function test_a_discounted_cart_landing_exactly_on_the_minimum_qualifies(): void {
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '35.77',
			)
		);
		$this->cart_holding( 40.0 );
		$this->apply_coupon( 'four-twenty-three-off', array( 'coupon_amount' => '4.23' ) );

		$this->assertTrue(
			$this->is_offered(),
			'40.00 less 4.23 is exactly the 35.77 minimum, whatever the binary subtraction leaves behind.'
		);
	}

	/**
	 * `woocommerce_shipping_free_shipping_is_available` is a public extension point. Pin that it
	 * is honoured rather than pinning an outcome an extension is meant to be able to change.
	 *
	 * @testdox An extension can override availability through the filter.
	 */
	public function test_the_availability_filter_is_honoured(): void {
		$this->method_with( array( 'requires' => '' ) );
		$this->cart_holding( 10.0 );

		$this->assertTrue( $this->is_offered(), 'No requirement should offer free shipping before the filter runs.' );

		$seen = array();
		add_filter(
			'woocommerce_shipping_free_shipping_is_available',
			function ( $is_available, $package, $method ) use ( &$seen ) {
				$seen = array( $is_available, $package, $method );
				return false;
			},
			10,
			3
		);

		$this->assertFalse( $this->is_offered(), 'The filter should be able to withdraw the method.' );
		$this->assertTrue( $seen[0], 'The filter should be handed the decision the method reached.' );
		$this->assertSame( 'US', $seen[1]['destination']['country'], 'The filter should be handed the package.' );
		$this->assertSame( $this->sut, $seen[2], 'The filter should be handed the method itself.' );
	}

	/**
	 * The checkbox reads "Apply minimum order rule before coupon discount", described as making
	 * free shipping "available based on pre-discount order amount". Unchecked, the discount
	 * counts against the minimum; checked, it does not.
	 *
	 * @testdox The coupon discount counts against the minimum unless the pre-discount rule is on.
	 *
	 * @testWith ["no", false]
	 *           ["yes", true]
	 *
	 * @param string $ignore_discounts The setting value.
	 * @param bool   $expected         Whether free shipping should be offered.
	 */
	public function test_discount_counts_against_the_minimum_unless_ignored( string $ignore_discounts, bool $expected ): void {
		$this->method_with(
			array(
				'requires'         => 'min_amount',
				'min_amount'       => '50',
				'ignore_discounts' => $ignore_discounts,
			)
		);
		$this->cart_holding( 55.0 );
		$this->apply_coupon( 'ten-off', array( 'coupon_amount' => '10' ) );

		$this->assertSame(
			$expected,
			$this->is_offered(),
			'A 55.00 cart discounted by 10.00 against a 50.00 minimum, with the pre-discount rule ' . $ignore_discounts . '.'
		);
	}

	/**
	 * The Minimum order amount field defaults to 0, so a merchant who picks the minimum rule and
	 * leaves the amount alone is asking for no floor at all, not an unreachable one.
	 *
	 * @testdox A minimum of zero is met by the smallest cart.
	 */
	public function test_a_minimum_of_zero_is_met_by_any_cart(): void {
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '0',
			)
		);
		$this->cart_holding( 1.0 );

		$this->assertTrue( $this->is_offered(), 'A minimum of zero should let any cart through.' );
	}

	/**
	 * The pre-discount checkbox is offered for every rule that involves the minimum, not just the
	 * minimum on its own, so it has to behave the same way under those too.
	 *
	 * @testdox The pre-discount rule applies under either and both, not only under the minimum alone.
	 *
	 * @testWith ["either", "no", false]
	 *           ["either", "yes", true]
	 *           ["both", "no", false]
	 *           ["both", "yes", true]
	 *
	 * @param string $requires         The requirement setting.
	 * @param string $ignore_discounts Whether the minimum is applied before the discount.
	 * @param bool   $expected         Whether free shipping should be offered.
	 */
	public function test_the_pre_discount_rule_applies_under_either_and_both( string $requires, string $ignore_discounts, bool $expected ): void {
		$this->method_with(
			array(
				'requires'         => $requires,
				'min_amount'       => '50',
				'ignore_discounts' => $ignore_discounts,
			)
		);
		$this->cart_holding( 55.0 );

		// The coupon has to discount either way, so the pre-discount rule has something to act on.
		// Under "either" it must not grant free shipping, or it would satisfy the rule on its own
		// and the minimum would never be consulted. Under "both" it must grant it, or the coupon
		// half of the rule fails and the minimum never gets to decide anything.
		$this->apply_coupon(
			'ten-off',
			array(
				'coupon_amount' => '10',
				'free_shipping' => 'both' === $requires ? 'yes' : 'no',
			)
		);

		$this->assertSame(
			$expected,
			$this->is_offered(),
			sprintf( 'A 55.00 cart less 10.00 against a 50.00 minimum under %s, pre-discount rule %s.', $requires, $ignore_discounts )
		);
	}

	/**
	 * A shopper can hold several coupons at once. Only one of them has to grant free shipping,
	 * and one that does not must not mask one that does.
	 *
	 * @testdox A free shipping coupon still qualifies alongside coupons that do not grant it.
	 */
	public function test_one_qualifying_coupon_among_several_is_enough(): void {
		$this->method_with( array( 'requires' => 'coupon' ) );
		$this->cart_holding( 100.0 );

		$this->apply_coupon( 'plain-one', array( 'free_shipping' => 'no' ) );
		$this->apply_coupon( 'plain-two', array( 'free_shipping' => 'no' ) );

		$this->assertFalse( $this->is_offered(), 'Two coupons that grant nothing should still grant nothing.' );

		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );

		$this->assertTrue( $this->is_offered(), 'The qualifying coupon should count even when it is not the only one.' );
	}

	/**
	 * The settings screen hides the minimum order amount field when the rule is the coupon alone,
	 * so a minimum left behind from an earlier rule must not quietly keep applying.
	 *
	 * @testdox A coupon rule ignores the minimum order amount entirely.
	 */
	public function test_a_coupon_rule_ignores_the_minimum(): void {
		$this->method_with(
			array(
				'requires'   => 'coupon',
				'min_amount' => '500',
			)
		);
		$this->cart_holding( 100.0 );
		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );

		$this->assertTrue( $this->is_offered(), 'The coupon alone should decide, whatever the minimum says.' );
	}

	/**
	 * The mirror of the tax-inclusive case. On a store that shows prices without tax, the amount
	 * the shopper is shown spending never included the tax, so the coupon's tax must not come off
	 * the minimum a second time.
	 *
	 * @testdox On a tax-exclusive store only the discount itself comes off the minimum.
	 */
	public function test_on_a_tax_exclusive_store_only_the_discount_comes_off(): void {
		$this->taxes_at_ten_percent( 'no', 'excl' );

		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '90',
			)
		);
		$this->cart_holding( 100.0 );
		$this->apply_coupon( 'ten-off', array( 'coupon_amount' => '10' ) );

		$this->assertEquals( 1.0, WC()->cart->get_discount_tax(), 'The coupon should carry 1.00 of tax, which must not come off a figure shown without tax.' );
		$this->assertTrue(
			$this->is_offered(),
			'A 100.00 cart shown without tax, less a 10.00 coupon, leaves exactly the 90.00 minimum.'
		);
	}

	/**
	 * The shopper holds the qualifying coupon first and a plain one after it. Whether free
	 * shipping is granted must not depend on which coupon happens to be looked at last.
	 *
	 * @testdox A qualifying coupon still counts when a plain coupon is applied after it.
	 */
	public function test_a_qualifying_coupon_counts_whatever_order_it_was_applied_in(): void {
		$this->method_with( array( 'requires' => 'coupon' ) );
		$this->cart_holding( 100.0 );

		$this->apply_coupon( 'ships-free', array( 'free_shipping' => 'yes' ) );
		$this->apply_coupon( 'plain-after', array( 'free_shipping' => 'no' ) );

		$this->assertTrue( $this->is_offered(), 'A later coupon that grants nothing should not undo the one that does.' );
	}

	/**
	 * Storing prices with tax and showing them without is a normal configuration, and the two
	 * options are independent. What the shopper is shown is the display setting, so that is what
	 * the minimum follows.
	 *
	 * @testdox Prices stored with tax but shown without are measured without tax.
	 */
	public function test_prices_stored_with_tax_but_shown_without_are_measured_without_tax(): void {
		$this->taxes_at_ten_percent( 'yes', 'excl' );

		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '90',
			)
		);
		$this->cart_holding( 110.0 );
		$this->apply_coupon( 'eleven-off', array( 'coupon_amount' => '11' ) );

		$this->assertEquals( 1.0, WC()->cart->get_discount_tax(), 'The coupon should carry 1.00 of tax.' );
		$this->assertTrue(
			$this->is_offered(),
			'A 110.00 price stored with tax shows as 100.00, and the shopper hands over 10.00 of that, so 90.00 meets the 90.00 minimum. Taking the coupon tax off as well would leave 89.00 and wrongly deny it.'
		);
	}

	/**
	 * The minimum is a price, so its pennies count. A cart a penny short is a penny short.
	 *
	 * @testdox A cart one penny under a fractional minimum does not qualify.
	 */
	public function test_a_penny_under_a_fractional_minimum_does_not_qualify(): void {
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '35.77',
			)
		);
		$this->cart_holding( 35.76 );

		$this->assertFalse( $this->is_offered(), 'A minimum of 35.77 should not be satisfied by 35.76.' );
	}

	/**
	 * @testdox A minimum amount stored in the store's number format still refuses carts below it.
	 *
	 * @testWith [50, false]
	 *           [150, true]
	 *
	 * @param int  $price    Price of the only product in the cart.
	 * @param bool $expected Whether free shipping should be available.
	 */
	public function test_is_available_with_locale_formatted_min_amount( int $price, bool $expected ): void {
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
		$this->method_with(
			array(
				'requires'   => 'min_amount',
				'min_amount' => '100,00',
			)
		);
		$this->cart_holding( (float) $price );

		$this->assertSame( $expected, $this->is_offered() );
	}
}
