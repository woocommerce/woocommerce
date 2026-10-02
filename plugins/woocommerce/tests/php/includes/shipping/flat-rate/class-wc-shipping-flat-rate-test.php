<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Test for WC_Shipping_Flat_Rate class.
 */
class WC_Shipping_Flat_Rate_Test extends WC_Unit_Test_Case {

	/**
	 * @var WC_Shipping_Flat_Rate Shipping method instance.
	 */
	private $sut;

	/**
	 * @var Closure Function to call protected method evaluate_cost.
	 */
	private $call_evaluate_cost;

	/**
	 * @var Closure Function to call public method sanitize_cost.
	 */
	private $call_sanitize_cost;

	/**
	 * Set up test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                = new WC_Shipping_Flat_Rate();
		$this->call_evaluate_cost = function ( $sum, $args ) {
			return $this->evaluate_cost( $sum, $args );
		};
		$this->call_sanitize_cost = function ( $value ) {
			return $this->sanitize_cost( $value );
		};
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
	}

	/**
	 * Tear down test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		parent::tearDown();
	}


	/**
	 * @testDox Shipping cost with decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_dec() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with incorrect decimal separator works as expected.
	 */
	public function test_evaluate_cost_dec_separator_inverse() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12345.67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with a thousand and decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_thou_dec() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12.345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with two decimal separator works as expected.
	 */
	public function test_evaluate_cost_sep_dec_dec() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12,345,67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 12345.67, $val );
	}

	/**
	 * @testDox Shipping cost with two thousand separator works as expected.
	 */
	public function test_evaluate_cost_sep_thou_thou() {
		$this->markTestSkipped( 'This test currently fails because we dont support thousand separator in shipping price.' );
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'12.345.67',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals( 1234567, $val );
	}

	/**
	 * Percent fee calculation works as expected.
	 */
	public function test_evaluate_cost_percent_fee() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10.1"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 10.1, $val );
	}

	/**
	 * Percent fee calculation works as expected with comma as decimal separator. Value after the comma is ignored.
	 */
	public function test_evaluate_cost_percent_fee_comma() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10,1"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 10, $val );
	}

	/**
	 * @testDox sanitize_cost() accepts and preserves valid math expressions.
	 *
	 * @dataProvider provider_valid_math_expressions
	 *
	 * @param string $value           Value to test.
	 * @param string $decimal_sep     Decimal separator to use.
	 * @param string $thousand_sep    Thousand separator to use.
	 */
	public function test_sanitize_cost_accepts_math_expressions( string $value, string $decimal_sep, string $thousand_sep ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$result = $this->call_sanitize_cost->call( $this->sut, $value );
		$this->assertEquals( $value, trim( $result ) );
	}

	/**
	 * @testDox sanitize_cost() rejects invalid math expressions.
	 *
	 * @dataProvider provider_invalid_math_expressions
	 *
	 * @param string $value       Value to sanitize.
	 * @param string $decimal_sep Decimal separator to use.
	 * @param string $thousand_sep Thousand separator to use.
	 */
	public function test_sanitize_cost_rejects_invalid_expressions( string $value, string $decimal_sep, string $thousand_sep ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$this->expectException( Exception::class );
		$this->call_sanitize_cost->call( $this->sut, $value );
	}

	/**
	 * Valid math expression cases.
	 *
	 * Format: [ value, decimal_separator, thousand_separator ]
	 */
	public function provider_valid_math_expressions(): array {
		return array(
			'plain number'                  => array( '10.00', '.', ',' ),
			'empty string'                  => array( '', '.', ',' ),
			'shortcode qty'                 => array( '[qty]', '.', ',' ),
			'shortcode expression'          => array( '10.00 * [qty]', '.', ',' ),
			'shortcode weight'              => array( '[weight]', '.', ',' ),
			'shortcode weight expression'   => array( '2.50 * [weight] + 1', '.', ',' ),
			'shortcode weight with min'     => array( '[weight min="1"]', '.', ',' ),
			'shortcode weight min and max'  => array( '2 * [weight min="1" max="20"]', '.', ',' ),
			'weight min zero'               => array( '2 * [weight min="0"]', '.', ',' ),
			'weight max zero'               => array( '2 * [weight max="0"]', '.', ',' ),
			'weight equal limits'           => array( '2 * [weight min="1" max="1"]', '.', ',' ),
			'weight empty limits'           => array( '2 * [weight min="" max=""]', '.', ',' ),

			// Safe because the minimum keeps the divisor away from zero.
			'weight division with min'      => array( '10 / [weight min="1"]', '.', ',' ),

			// period decimal, comma thousand.
			'simple division'               => array( '3.50 / 1.21', '.', ',' ),
			'simple multiplication'         => array( '10.00 * 1.21', '.', ',' ),
			'simple addition'               => array( '10 + 5', '.', ',' ),
			'simple subtraction'            => array( '20 - 3.50', '.', ',' ),
			'chained operators'             => array( '10 * 2 + 5', '.', ',' ),

			// comma decimal, period thousand.
			'EU locale division'            => array( '3,50 / 1,21', ',', '.' ),
			'EU locale multiplication'      => array( '10,00 * 1,21', ',', '.' ),

			// No thousand separator locale.
			'no thousand separator simple'  => array( '3.50 / 1.21', '.', '' ),
			'no thousand separator chained' => array( '10 * 2 + 5', '.', '' ),
		);
	}

	/**
	 * Invalid math expression cases.
	 *
	 * Format: [ value, decimal_separator, thousand_separator ]
	 */
	public function provider_invalid_math_expressions(): array {
		return array(
			// Thousand-separated operands must not be used in math expressions
			// as evaluate_cost() normalises all separators to ".", causing
			// "10,000" to be evaluated as "10.0" instead of "10000".
			'thousand separated operand'    => array( '10,500 * 3000', '.', ',' ),
			'EU thousand separated operand' => array( '10.500 * 3000', ',', '.' ),

			// Trailing operator — incomplete expressions.
			'trailing plus'                 => array( '20 +', '.', ',' ),
			'trailing minus'                => array( '20 -', '.', ',' ),
			'trailing multiply'             => array( '20 *', '.', ',' ),
			'trailing divide'               => array( '3.50 /', '.', ',' ),

			// Invalid characters.
			'alphabetic string'             => array( 'abc', '.', ',' ),
			'alphanumeric'                  => array( '10abc', '.', ',' ),
			'hyphenated weight name'        => array( '[weight-foo]', '.', ',' ),
			'dotted weight name'            => array( '[weight.foo]', '.', ',' ),
			'hyphenated weight with limits' => array( '2 * [weight-foo min="1"]', ',', '.' ),
			'dotted weight with limits'     => array( '2 * [weight.foo max="2"]', ',', '.' ),

			// Divides by zero on any package whose items have no weight set.
			'weight division'               => array( '10 / [weight]', '.', ',' ),
			'weight division with min zero' => array( '10 / [weight min="0"]', '.', ',' ),
		);
	}

	/**
	 * @testdox Should take the tax out of the entered cost only when costs are entered with tax.
	 * @testWith [{"cost": "5.70", "prices_include_tax": "yes"}, 4.596774, 1.103226]
	 *           [{"cost": "5.70", "prices_include_tax": "no"}, 5.70, 1.368]
	 *           [{"cost": "5.70"}, 5.70, 1.368]
	 *           [{"cost": "5.70", "prices_include_tax": "yes", "tax_status": "none"}, 5.70, 0]
	 *
	 * @param array $settings      Flat rate settings; no prices_include_tax is an instance saved before the setting existed.
	 * @param float $expected_cost Expected net cost on the rate.
	 * @param float $expected_tax  Expected tax on the rate.
	 */
	public function test_calculate_shipping_honours_prices_include_tax( array $settings, float $expected_cost, float $expected_tax ): void {
		$this->enable_shipping_tax( array( '24.0000' ) );

		$rates = $this->calculate_flat_rates( $settings );
		$rate  = reset( $rates );

		$this->assertEqualsWithDelta( $expected_cost, (float) $rate->get_cost(), 0.000001, 'The stored cost should be the net' );
		$this->assertEqualsWithDelta( $expected_tax, array_sum( $rate->get_taxes() ), 0.000001, 'The tax should match the setting' );
	}

	/**
	 * @testdox Should charge the cost entered with tax in the cart and keep it when the order taxes are recalculated.
	 * @testWith ["10.00", ["24.0000"], "no"]
	 *           ["10.00", ["24.0000"], "yes"]
	 *           ["7.00", ["19.0000"], "yes"]
	 *
	 * @param string   $cost                       Cost entered with tax.
	 * @param string[] $tax_rates                  Tax rates that apply to shipping, each at its own priority.
	 * @param string   $product_prices_include_tax Value of the woocommerce_prices_include_tax option, which sets the tax rounding mode.
	 */
	public function test_cost_entered_with_tax_survives_order_recalculation( string $cost, array $tax_rates, string $product_prices_include_tax ): void {
		update_option( 'woocommerce_prices_include_tax', $product_prices_include_tax );
		$this->enable_shipping_tax( $tax_rates );

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'US' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance_id . '_settings',
			array(
				'cost'               => $cost,
				'prices_include_tax' => 'yes',
			)
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$shipping_was_enabled = WC()->shipping()->enabled;

		try {
			WC()->shipping()->enabled = true;
			WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );
			WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $instance_id ) );
			WC()->cart->calculate_totals();
			$cart_total = WC()->cart->get_total( 'edit' );

			$order = new WC_Order();
			WC()->checkout()->set_data_from_cart( $order );
			$order->save();
			$order->calculate_totals( true );
		} finally {
			WC()->shipping()->enabled = $shipping_was_enabled;
		}

		$this->assertSame( $cost, wc_format_decimal( (float) WC()->cart->get_shipping_total() + (float) WC()->cart->get_shipping_tax(), 2 ), 'The cart should charge the entered cost' );
		$this->assertSame( $cost, wc_format_decimal( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), 2 ), 'The recalculated order should charge the entered cost' );
		$this->assertSame( wc_format_decimal( $cart_total, 2 ), wc_format_decimal( $order->get_total(), 2 ), 'The recalculated order total should match the cart total' );
	}

	/**
	 * @testdox Should default new instances to costs entered with tax when the store enters prices with tax, and leave instances saved before the option existed alone.
	 * @testWith ["yes", null, "yes"]
	 *           ["yes", {"cost": "5.70"}, "no"]
	 *           ["no", null, "no"]
	 *
	 * @param string     $store_prices_include_tax Value of the woocommerce_prices_include_tax option.
	 * @param array|null $saved_settings           Settings saved for the instance, null for a new instance.
	 * @param string     $expected                 Expected costs entered with tax setting.
	 */
	public function test_prices_include_tax_default_follows_store_setting_for_new_instances( string $store_prices_include_tax, ?array $saved_settings, string $expected ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', $store_prices_include_tax );
		if ( null !== $saved_settings ) {
			update_option( 'woocommerce_flat_rate_4242_settings', $saved_settings );
		}

		$flat_rate = new WC_Shipping_Flat_Rate( 4242 );

		$this->assertSame( $expected, $flat_rate->get_option( 'prices_include_tax' ) );
	}

	/**
	 * @testdox Should only offer the costs entered with tax setting while taxes are enabled.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $calc_taxes Value of the woocommerce_calc_taxes option.
	 * @param bool   $expected   Whether the setting should be offered.
	 */
	public function test_prices_include_tax_field_needs_taxes_enabled( string $calc_taxes, bool $expected ): void {
		update_option( 'woocommerce_calc_taxes', $calc_taxes );

		$flat_rate = new WC_Shipping_Flat_Rate( 4242 );

		$this->assertSame( $expected, array_key_exists( 'prices_include_tax', $flat_rate->get_instance_form_fields() ) );
	}

	/**
	 * @testdox Should also take the tax out of rates that extensions add through the flat rate action.
	 */
	public function test_rate_added_through_action_is_entered_with_tax(): void {
		$this->enable_shipping_tax( array( '24.0000' ) );
		$add_express_rate = function ( $method ) {
			$method->add_rate(
				array(
					'id'    => 'express',
					'label' => 'Express',
					'cost'  => '5.70',
				)
			);
		};
		add_action( 'woocommerce_flat_rate_shipping_add_rate', $add_express_rate );

		try {
			$rates = $this->calculate_flat_rates(
				array(
					'cost'               => '10.00',
					'prices_include_tax' => 'yes',
				)
			);
		} finally {
			remove_action( 'woocommerce_flat_rate_shipping_add_rate', $add_express_rate );
		}

		$this->assertEqualsWithDelta( 4.596774, (float) $rates['express']->get_cost(), 0.000001 );
	}

	/**
	 * @testdox Should let a woocommerce_shipping_prices_include_tax callback switch off costs entered with tax.
	 */
	public function test_shipping_prices_include_tax_filter_overrides_the_setting(): void {
		$this->enable_shipping_tax( array( '24.0000' ) );
		add_filter( 'woocommerce_shipping_prices_include_tax', '__return_false' );

		try {
			$rates = $this->calculate_flat_rates(
				array(
					'cost'               => '5.70',
					'prices_include_tax' => 'yes',
				)
			);
		} finally {
			remove_filter( 'woocommerce_shipping_prices_include_tax', '__return_false' );
		}
		$rate = reset( $rates );

		$this->assertEqualsWithDelta( 5.70, (float) $rate->get_cost(), 0.000001, 'The cost should be treated as excluding tax' );
		$this->assertEqualsWithDelta( 1.368, array_sum( $rate->get_taxes() ), 0.000001, 'The tax should be added on top of the cost' );
	}

	/**
	 * @testdox Should leave costs alone for methods that have a prices_include_tax setting but do not support costs entered with tax.
	 */
	public function test_costs_entered_with_tax_need_method_support(): void {
		$this->enable_shipping_tax( array( '24.0000' ) );

		$sut = new class() extends WC_Shipping_Method {
			/**
			 * Set up a taxable method with its own prices_include_tax setting.
			 */
			public function __construct() {
				$this->id         = 'own_prices_include_tax';
				$this->tax_status = 'taxable';
				$this->settings   = array( 'prices_include_tax' => 'yes' );
			}

			/**
			 * Add a single rate.
			 *
			 * @param array $package Package.
			 */
			public function calculate_shipping( $package = array() ) {
				$this->add_rate(
					array(
						'id'    => 'own',
						'label' => 'Own',
						'cost'  => '5.70',
					)
				);
			}
		};
		$sut->calculate_shipping();
		$rate = reset( $sut->rates );

		$this->assertEqualsWithDelta( 5.70, (float) $rate->get_cost(), 0.000001, 'The cost should be left as entered' );
		$this->assertEqualsWithDelta( 1.368, array_sum( $rate->get_taxes() ), 0.000001, 'The tax should be added on top' );
	}

	/**
	 * Turn on taxes for shipping to the US customer, each rate at its own priority so they all apply.
	 *
	 * @param string[] $tax_rates Tax rate percentages.
	 */
	private function enable_shipping_tax( array $tax_rates ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_shipping_tax_class', '' );
		foreach ( $tax_rates as $index => $tax_rate ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate'          => $tax_rate,
					'tax_rate_priority' => $index + 1,
				)
			);
		}
		WC_Helper_Shipping::force_customer_us_address();
	}

	/**
	 * Calculate the rates of a flat rate instance with the given settings.
	 *
	 * @param array $settings Flat rate settings.
	 * @return WC_Shipping_Rate[]
	 */
	private function calculate_flat_rates( array $settings ): array {
		update_option( 'woocommerce_flat_rate_4242_settings', $settings );

		$flat_rate = new WC_Shipping_Flat_Rate( 4242 );
		$flat_rate->calculate_shipping(
			array(
				'contents'      => array(),
				'contents_cost' => 0,
			)
		);

		return $flat_rate->rates;
	}

	/**
	 * @testdox The [weight] placeholder is replaced with the package weight.
	 *
	 * @dataProvider provider_weight_substitution
	 *
	 * @param string           $sum      Cost expression to evaluate.
	 * @param int|float|string $weight   Weight passed in the args.
	 * @param float            $expected Expected result.
	 */
	public function test_evaluate_cost_substitutes_weight( string $sum, $weight, float $expected ): void {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			$sum,
			array(
				'qty'    => 2,
				'cost'   => 100,
				'weight' => $weight,
			)
		);

		$this->assertFloatEquals( $expected, (float) $val, null, "Expected '{$sum}' to evaluate to {$expected}." );
	}

	/**
	 * Weight substitution cases.
	 *
	 * Only enough to prove the placeholder is wired into evaluate_cost() and survives the expression
	 * evaluator. Substitution and clamping are covered exhaustively in WeightPlaceholderTest.
	 *
	 * Format: [ expression, weight, expected result ]. Quantity is always 2 and cost is always 100.
	 *
	 * @return array
	 */
	public function provider_weight_substitution(): array {
		return array(
			'weight on its own'            => array( '[weight]', 3, 3.0 ),
			'weight multiplied'            => array( '2 * [weight]', 1.5, 3.0 ),
			'weight combined with qty'     => array( '[weight] + [qty]', 2.25, 4.25 ),
			'zero weight keeps expression' => array( '10 * [weight]', 0, 0.0 ),
			'min raises a low weight'      => array( '[weight min="1"]', 0, 1.0 ),
			'min keeps divisor non-zero'   => array( '10 / [weight min="2"]', 0, 5.0 ),
		);
	}

	/**
	 * @testdox The [weight] placeholder falls back to zero when callers omit the weight argument.
	 */
	public function test_evaluate_cost_weight_defaults_to_zero_when_arg_missing(): void {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'10 + [weight]',
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);

		$this->assertFloatEquals( 10.0, (float) $val, null, 'Subclasses that pass only cost and qty should still evaluate [weight] as zero.' );
	}

	/**
	 * @testdox A non-numeric weight coming from the args filter is treated as zero.
	 */
	public function test_evaluate_cost_ignores_non_numeric_weight_from_filter(): void {
		$callback = function ( $args ) {
			$args['weight'] = 'not-a-number';
			return $args;
		};
		add_filter( 'woocommerce_evaluate_shipping_cost_args', $callback );

		try {
			$val = $this->call_evaluate_cost->call(
				$this->sut,
				'5 + [weight]',
				array(
					'qty'    => 1,
					'cost'   => 1,
					'weight' => 2,
				)
			);
		} finally {
			remove_filter( 'woocommerce_evaluate_shipping_cost_args', $callback );
		}

		$this->assertFloatEquals( 5.0, (float) $val, null, 'A non-numeric weight from a filter should fall back to zero.' );
	}

	/**
	 * @testdox The [weight] placeholder works alongside a comma decimal separator.
	 */
	public function test_evaluate_cost_weight_with_comma_decimal_separator(): void {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'1,5 * [weight]',
			array(
				'qty'    => 1,
				'cost'   => 1,
				'weight' => 2.5,
			)
		);

		$this->assertFloatEquals( 3.75, (float) $val, null, 'The substituted weight should survive decimal separator normalisation.' );
	}

	/**
	 * @testdox Weight limits accept dot decimals even when the store uses a comma decimal separator.
	 *
	 * @testWith ["10 * [weight min=\"0.5\"]", 0.0, 5.0]
	 *           ["10 * [weight max=\"1.5\"]", 3.0, 15.0]
	 *
	 * @param string $sum      Cost formula with a decimal weight limit.
	 * @param float  $weight   Package weight in the store's unit.
	 * @param float  $expected Expected shipping cost.
	 */
	public function test_evaluate_cost_weight_with_decimal_bounds( string $sum, float $weight, float $expected ): void {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			$this->sut->sanitize_cost( $sum ),
			array(
				'qty'    => 1,
				'cost'   => 1,
				'weight' => $weight,
			)
		);

		$this->assertFloatEquals( $expected, (float) $val, null, "Expected '{$sum}' to honor its decimal weight limit." );
	}

	/**
	 * @testdox Invalid weight limits produce a validation error when saving the cost.
	 *
	 * @testWith ["10 * [weight min=\"0,5\"]"]
	 *           ["10 * [weight max=\"1,5\"]"]
	 *           ["10 * [weight min=\"100,000.50\"]"]
	 *           ["10 * [weight max=\"100,00.5\"]"]
	 *           ["10 * [weight min=\"1.000,50\"]"]
	 *           ["10 * [weight max=\"1 000.50\"]"]
	 *           ["10 * [weight min=\"abc\"]"]
	 *           ["10 * [weight max=\"abc\"]"]
	 *           ["10 * [weight min=\"-1\"]"]
	 *           ["10 * [weight max=\"-1\"]"]
	 *           ["10 * [weight min=\"20\" max=\"10\"]"]
	 *           ["10 * [weight min=\"0.5\" max=\"0\"]"]
	 *
	 * @param string $sum Cost formula with an invalid weight limit.
	 */
	public function test_sanitize_cost_rejects_invalid_weight_limits( string $sum ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->sut->sanitize_cost( $sum );
	}

	/**
	 * @testdox The [fee] shortcode stays based on cost when a weight is supplied.
	 */
	public function test_evaluate_cost_weight_does_not_change_fee_base(): void {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10"] + [weight]',
			array(
				'qty'    => 1,
				'cost'   => 100,
				'weight' => 2,
			)
		);

		$this->assertFloatEquals( 12.0, (float) $val, null, 'The fee should be a percentage of cost, with the weight added on top.' );
	}
}
