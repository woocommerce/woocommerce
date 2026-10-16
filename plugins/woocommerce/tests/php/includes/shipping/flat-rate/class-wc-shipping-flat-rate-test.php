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
	 * Percent fee calculation keeps the decimals when the store uses a comma as the decimal separator.
	 */
	public function test_evaluate_cost_percent_fee_comma() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10,1" min_fee="12,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 12.5, $val, 'The minimum fee should apply, read with a comma decimal.' );

		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="10,1"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 10.1, $val, 'The percentage should keep its decimals.' );
	}

	/**
	 * A fee below a comma-decimal minimum is raised to that minimum.
	 */
	public function test_evaluate_cost_percent_fee_below_comma_min_fee() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="5" min_fee="12,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 12.5, $val, 'A fee below the minimum should be raised to it.' );

		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="12,25" min_fee="12,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 12.5, $val, 'A fee just below the minimum should be raised to it.' );
	}

	/**
	 * A fee below a comma-decimal maximum is not capped.
	 */
	public function test_evaluate_cost_percent_fee_below_comma_max_fee() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="3" max_fee="20,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 3, $val, 'A fee below the maximum should not be capped.' );

		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="20,25" max_fee="20,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 20.25, $val, 'A fee just below the maximum should not be capped.' );

		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'[fee percent="30" max_fee="20,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 20.5, $val, 'A fee above the maximum should be capped to it.' );
	}

	/**
	 * A comma-decimal percent fee keeps its decimals inside a cost formula.
	 */
	public function test_evaluate_cost_comma_percent_fee_in_formula() {
		$val = $this->call_evaluate_cost->call(
			$this->sut,
			'10,5 + [fee percent="2,5"]',
			array(
				'qty'  => 1,
				'cost' => 100,
			)
		);
		$this->assertEquals( 13.0, $val, 'The fee should keep its decimals when added to a base cost.' );
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

	/**
	 * A plain amount is stored dot-decimal whichever separator the merchant typed, so the value
	 * that reaches evaluate_cost() is the one PHP can read. setUp() puts the store on a comma
	 * decimal separator and a dot thousand separator, which is where the forms differ.
	 *
	 * The three rows are three different routes through the sanitiser: a value that is already
	 * dot-decimal is taken as it stands, one written in the store's own separators is converted,
	 * and one carrying a thousand separator has that removed rather than read as a decimal point.
	 *
	 * @testdox sanitize_cost() stores a plain amount dot-decimal whichever separator was typed.
	 *
	 * @testWith ["10.5", "10.5"]
	 *           ["10,5", "10.5"]
	 *           ["1.000,50", "1000.50"]
	 *
	 * @param string $typed    What the merchant entered.
	 * @param string $expected What should be stored.
	 */
	public function test_sanitize_cost_stores_a_plain_amount_dot_decimal( string $typed, string $expected ): void {
		$result = $this->call_sanitize_cost->call( $this->sut, $typed );

		$this->assertSame( $expected, trim( $result ), 'Entered as "' . $typed . '" on a comma-decimal store.' );
	}

	/**
	 * @testdox sanitize_cost() delocalises a plain thousand-separated cost so it is never evaluated as free shipping.
	 *
	 * @dataProvider provider_thousand_separated_costs
	 *
	 * @param string $value        Raw cost as a merchant would enter it.
	 * @param string $decimal_sep  Decimal separator for the locale.
	 * @param string $thousand_sep Thousand separator for the locale.
	 * @param string $expected     Delocalised cost sanitize_cost() should return.
	 * @param float  $evaluated    Amount evaluate_cost() should yield for the sanitized cost.
	 */
	public function test_sanitize_cost_delocalises_thousand_separated_value( string $value, string $decimal_sep, string $thousand_sep, string $expected, float $evaluated ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$sanitized = $this->call_sanitize_cost->call( $this->sut, $value );
		$this->assertSame( $expected, $sanitized, 'sanitize_cost() should delocalise the thousand-separated value.' );

		$result = $this->call_evaluate_cost->call(
			$this->sut,
			$sanitized,
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEqualsWithDelta( $evaluated, $result, 0.0001, 'evaluate_cost() should evaluate the sanitized cost to the expected amount.' );
	}

	/**
	 * Plain thousand-separated costs across locales.
	 *
	 * Format: [ raw value, decimal separator, thousand separator, sanitized value, evaluated amount ].
	 *
	 * @return array
	 */
	public function provider_thousand_separated_costs(): array {
		return array(
			// period decimal, comma thousand (US).
			'US thousand separator' => array( '1,234.56', '.', ',', '1234.56', 1234.56 ),

			// comma decimal, period thousand (EU).
			'EU thousand separator' => array( '1.234,56', ',', '.', '1234.56', 1234.56 ),
		);
	}

	/**
	 * @testdox sanitize_cost() rejects a cost with more than one decimal separator rather than saving a wrong amount.
	 *
	 * @testWith ["12.34.56", ".", ","]
	 *           ["12,34,56", ",", "."]
	 *
	 * @param string $value        Cost written with two decimal separators.
	 * @param string $decimal_sep  Decimal separator for the locale.
	 * @param string $thousand_sep Thousand separator for the locale.
	 */
	public function test_sanitize_cost_rejects_two_decimal_separators( string $value, string $decimal_sep, string $thousand_sep ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$this->expectException( InvalidArgumentException::class );
		$this->call_sanitize_cost->call( $this->sut, $value );
	}

	/**
	 * @testdox Explicit class restrictions suppress only the affected flat-rate package.
	 *
	 * @testWith ["class", ""]
	 *           ["class", "5"]
	 *           ["order", ""]
	 *           ["order", "5"]
	 * @param string $type Calculation type.
	 * @param string $base Base shipping cost.
	 */
	public function test_restricted_class_suppresses_mixed_package( string $type, string $base ): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, $base, $type, 'unavailable' );

		$this->sut->calculate_shipping( $package );
		$this->assertSame( array(), $this->sut->rates, 'A restricted class must suppress the whole method, even with another class cost.' );

		unset( $package['contents'][1] );
		$this->sut->calculate_shipping( $package );
		$this->assertCount( 1, $this->sut->rates, 'The same method must remain available for a package without the restricted class.' );
	}

	/**
	 * @testdox Missing class behavior retains the existing mixed-cart shipping cost.
	 */
	public function test_legacy_blank_class_keeps_mixed_package_rate(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '', 'class' );

		$blank_package             = $package;
		$blank_package['contents'] = array( $package['contents'][1] );
		$this->sut->calculate_shipping( $blank_package );
		$this->assertSame( array(), $this->sut->rates, 'A blank base and blank sole class do not offer a rate.' );

		$this->sut->calculate_shipping( $package );
		$this->assertSame( '10', current( $this->sut->rates )->get_cost(), 'A legacy blank class adds no surcharge to the other class.' );
	}

	/**
	 * @testdox Disabling a class surcharge keeps its saved formula for later use.
	 */
	public function test_no_class_cost_ignores_saved_formula(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '5', 'class', 'none', '3 * [qty]' );

		$this->sut->calculate_shipping( $package );
		$this->assertSame( '15', current( $this->sut->rates )->get_cost(), 'The disabled class formula must not contribute to the total.' );
		$this->assertSame( '3 * [qty]', $this->sut->get_option( 'class_cost_' . $classes[1]->term_id ) );
	}

	/**
	 * @testdox A virtual item in a restricted class does not suppress shipping.
	 */
	public function test_virtual_product_does_not_restrict_shipping(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$package['contents'][1]['data']->set_virtual( true );
		$this->configure_class_costs( $classes, '', 'class', 'unavailable' );

		$this->sut->calculate_shipping( $package );
		$this->assertSame( '10', current( $this->sut->rates )->get_cost() );
	}

	/**
	 * @testdox Products without a shipping class can explicitly exclude a flat rate.
	 */
	public function test_unclassified_product_can_restrict_shipping(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$package['contents'][1]['data']->set_shipping_class_id( 0 );
		$this->configure_class_costs( $classes, '5', 'class' );
		$this->sut->instance_settings['no_class_cost_behavior'] = 'unavailable';

		$this->sut->calculate_shipping( $package );
		$this->assertSame( array(), $this->sut->rates );
	}

	/**
	 * @testdox New settings preserve legacy blank and zero rates without a migration.
	 *
	 * @testWith ["", "none", 0]
	 *           ["0", "add", 1]
	 * @param string $cost Existing class cost.
	 * @param string $behavior Expected displayed behavior.
	 * @param int    $rate_count Expected number of rates.
	 */
	public function test_class_behavior_defaults_preserve_saved_costs( string $cost, string $behavior, int $rate_count ): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '', 'class', null, $cost );
		$package['contents'] = array( $package['contents'][1] );
		$settings            = get_option( $this->sut->get_instance_option_key() );

		$this->assertSame( $behavior, $this->sut->get_option( 'class_cost_behavior_' . $classes[1]->term_id ) );
		$this->sut->calculate_shipping( $package );
		$this->assertCount( $rate_count, $this->sut->rates );
		$this->assertSame( $settings, get_option( $this->sut->get_instance_option_key() ), 'Reading the new defaults must not migrate stored options.' );

		$this->save_current_method_settings();
		$this->sut = new WC_Shipping_Flat_Rate( 1 );
		$this->sut->calculate_shipping( $package );
		$this->assertCount( $rate_count, $this->sut->rates, 'Saving unchanged settings must preserve blank and zero availability.' );
		$this->assertSame( $cost, $this->sut->get_option( 'class_cost_' . $classes[1]->term_id ) );
	}

	/**
	 * @testdox Legacy slug costs remain active when the newer term-id cost is blank.
	 */
	public function test_class_behavior_preserves_legacy_slug_cost(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '', 'class' );
		update_option( 'woocommerce_flat_rate_settings', array( 'class_cost_' . $classes[1]->slug => '7' ) );
		$this->sut           = new WC_Shipping_Flat_Rate( 1 );
		$package['contents'] = array( $package['contents'][1] );

		$this->sut->calculate_shipping( $package );
		$this->assertCount( 1, $this->sut->rates );
		$this->assertSame( '7', current( $this->sut->rates )->get_cost() );
		$this->save_current_method_settings();
		$this->sut = new WC_Shipping_Flat_Rate( 1 );
		$this->sut->calculate_shipping( $package );
		$this->assertSame( '7', current( $this->sut->rates )->get_cost() );
	}

	/**
	 * @testdox Unset class behavior preserves filtered costs before and after saving settings.
	 *
	 * @testWith [false, false]
	 *           [true, false]
	 *           [false, true]
	 *           [true, true]
	 * @param bool $unclassified Whether the product has no shipping class.
	 * @param bool $disabled Whether the merchant explicitly disabled its class cost.
	 */
	public function test_class_behavior_preserves_filtered_cost( bool $unclassified, bool $disabled ): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '', 'class' );
		$cost_key     = $unclassified ? 'no_class_cost' : 'class_cost_' . $classes[1]->term_id;
		$behavior_key = $unclassified ? 'no_class_cost_behavior' : 'class_cost_behavior_' . $classes[1]->term_id;
		if ( $unclassified ) {
			$package['contents'][1]['data']->set_shipping_class_id( 0 );
		}
		$package['contents'] = array( $package['contents'][1] );
		if ( $disabled ) {
			$this->sut->instance_settings[ $behavior_key ] = 'none';
		}
		add_filter(
			'woocommerce_shipping_flat_rate_instance_option',
			static function ( $value, $key ) use ( $cost_key ) {
				return $cost_key === $key ? '7' : $value;
			},
			10,
			2
		);

		$this->assertSame( $disabled ? 'none' : 'add', $this->sut->get_option( $behavior_key ) );
		$this->sut->calculate_shipping( $package );
		$this->assertCount( $disabled ? 0 : 1, $this->sut->rates );
		if ( ! $disabled ) {
			$this->assertSame( '7', current( $this->sut->rates )->get_cost() );
		}
		$this->save_current_method_settings();
		$this->sut = new WC_Shipping_Flat_Rate( 1 );
		$this->sut->calculate_shipping( $package );
		$this->assertCount( $disabled ? 0 : 1, $this->sut->rates );
	}

	/**
	 * @testdox An explicit restriction also suppresses additional rates from the flat-rate action.
	 */
	public function test_restriction_skips_additional_rates_action(): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '', 'class', 'unavailable' );
		$called = false;
		add_action(
			'woocommerce_flat_rate_shipping_add_rate',
			static function () use ( &$called ) {
				$called = true;
			}
		);

		$this->sut->calculate_shipping( $package );
		$this->assertFalse( $called, 'An extension must not add another rate for an explicitly excluded method.' );

		$this->sut->instance_settings[ 'class_cost_behavior_' . $classes[1]->term_id ] = 'add';
		$this->sut->calculate_shipping( $package );
		$this->assertTrue( $called, 'The existing action must still run for an unrestricted method.' );
	}

	/**
	 * @testdox Invalid class behaviors fall back to the existing cost calculation.
	 *
	 * @testWith ["invalid"]
	 *           [null]
	 *           [["unavailable"]]
	 * @param mixed $behavior Submitted setting value.
	 */
	public function test_invalid_class_behavior_keeps_legacy_calculation( $behavior ): void {
		list( $package, $classes ) = $this->create_shipping_class_package();
		$this->configure_class_costs( $classes, '5', 'class', null, '3 * [qty]' );
		$key                                  = 'class_cost_behavior_' . $classes[1]->term_id;
		$this->sut->instance_settings[ $key ] = $this->sut->get_field_value(
			$key,
			$this->sut->get_instance_form_fields()[ $key ],
			array( $this->sut->get_field_key( $key ) => $behavior )
		);

		$this->sut->calculate_shipping( $package );
		$this->assertSame( '18', current( $this->sut->rates )->get_cost() );
	}

	/**
	 * Save the values currently shown by the Settings API.
	 */
	private function save_current_method_settings(): void {
		$post_data = array();
		foreach ( $this->sut->get_instance_form_fields() as $key => $field ) {
			if ( 'title' !== $field['type'] ) {
				$post_data[ $this->sut->get_field_key( $key ) ] = $this->sut->get_option( $key );
			}
		}
		$this->sut->set_post_data( $post_data );
		$request                 = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request around this settings test.
		$_REQUEST['instance_id'] = 1;
		try {
			$this->sut->process_admin_options();
		} finally {
			$_REQUEST = $request;
		}
	}

	/**
	 * Create two physical products in distinct shipping classes.
	 *
	 * @return array Package and class terms.
	 */
	private function create_shipping_class_package(): array {
		$classes = array();
		$items   = array();
		foreach ( array( 'local', 'regional' ) as $name ) {
			$term      = wp_insert_term( $name, 'product_shipping_class' );
			$classes[] = get_term( $term['term_id'], 'product_shipping_class' );
			$product   = new WC_Product_Simple();
			$product->set_shipping_class_id( $term['term_id'] );
			$items[] = array(
				'data'       => $product,
				'quantity'   => 1,
				'line_total' => 20,
			);
		}
		add_filter(
			'woocommerce_get_shipping_classes',
			static function () use ( $classes ) {
				return $classes;
			}
		);

		return array(
			array(
				'contents'      => $items,
				'contents_cost' => 40,
			),
			$classes,
		);
	}

	/**
	 * Configure an instance with a priced first class and a configurable second class.
	 *
	 * @param array       $classes Shipping class terms.
	 * @param string      $base Base cost.
	 * @param string      $type Calculation type.
	 * @param string|null $behavior Optional second-class behavior.
	 * @param string      $cost Second-class cost.
	 */
	private function configure_class_costs( array $classes, string $base, string $type, ?string $behavior = null, string $cost = '' ): void {
		$settings = array(
			'cost'                               => $base,
			'type'                               => $type,
			'class_cost_' . $classes[0]->term_id => '10',
			'class_cost_' . $classes[1]->term_id => $cost,
		);
		if ( null !== $behavior ) {
			$settings[ 'class_cost_behavior_' . $classes[1]->term_id ] = $behavior;
		}
		update_option( 'woocommerce_flat_rate_1_settings', $settings );
		$this->sut = new WC_Shipping_Flat_Rate( 1 );
	}
}
