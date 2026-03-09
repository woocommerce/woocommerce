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
	 * @var Closure Function to call protected method is_math_expression.
	 */
	private $call_is_math_expression;

	/**
	 * Set up test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                     = new WC_Shipping_Flat_Rate();
		$this->call_evaluate_cost      = function ( $sum, $args ) {
			return $this->evaluate_cost( $sum, $args );
		};
		$this->call_is_math_expression = function ( $value ) {
			return $this->is_math_expression( $value );
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
	 * @testDox is_math_expression() returns true for valid math expressions.
	 * @dataProvider provider_valid_math_expressions
	 *
	 * @param string $value           Value to test.
	 * @param string $decimal_sep     Decimal separator to use.
	 * @param string $thousand_sep    Thousand separator to use.
	 * @param float  $expected_result Expected result of evaluating the expression.
	 */
	public function test_is_math_expression_returns_true( string $value, string $decimal_sep, string $thousand_sep, float $expected_result ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$this->assertTrue(
			$this->call_is_math_expression->call( $this->sut, $value ),
			"Expected '{$value}' to be recognised as a math expression."
		);

		$result = $this->call_evaluate_cost->call(
			$this->sut,
			$value,
			array(
				'qty'  => 1,
				'cost' => 1,
			)
		);
		$this->assertEquals(
			$expected_result,
			$result,
			"Expected '{$value}' to evaluate to {$expected_result}."
		);
	}

	/**
	 * @testDox is_math_expression() returns false for non-math-expression values.
	 *
	 * @dataProvider provider_invalid_math_expressions
	 *
	 * @param string $value       Value to test.
	 * @param string $decimal_sep  Decimal separator to use.
	 * @param string $thousand_sep Thousand separator to use.
	 */
	public function test_is_math_expression_returns_false( string $value, string $decimal_sep, string $thousand_sep ): void {
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );

		$this->assertFalse(
			$this->call_is_math_expression->call( $this->sut, $value ),
			"Expected '{$value}' to NOT be recognised as a math expression."
		);
	}

	/**
	 * Valid math expression cases.
	 *
	 * Format: [ value, decimal_separator, thousand_separator, expected_result ]
	 */
	public function provider_valid_math_expressions(): array {
		return array(
			// period decimal, comma thousand.
			'simple division'                   => array( '3.50 / 1.21', '.', ',', 3.50 / 1.21 ),
			'simple multiplication'             => array( '10.00 * 1.21', '.', ',', 10.00 * 1.21 ),
			'simple addition'                   => array( '10 + 5', '.', ',', 15.0 ),
			'simple subtraction'                => array( '20 - 3.50', '.', ',', 16.50 ),
			'chained operators'                 => array( '10 * 2 + 5', '.', ',', 25.0 ),

			// comma decimal, period thousand.
			'EU locale division'                => array( '3,50 / 1,21', ',', '.', 3.50 / 1.21 ),
			'EU locale multiplication'          => array( '10,00 * 1,21', ',', '.', 10.00 * 1.21 ),

			// No thousand separator locale.
			'no thousand separator simple'      => array( '3.50 / 1.21', '.', '', 3.50 / 1.21 ),
			'no thousand separator chained'     => array( '10 * 2 + 5', '.', '', 25.0 ),

			// Whitespace variations.
			'extra whitespace between operands' => array( '3.50  /  1.21', '.', ',', 3.50 / 1.21 ),
			'leading and trailing whitespace'   => array( '  3.50 / 1.21  ', '.', ',', 3.50 / 1.21 ),
		);
	}

	/**
	 * Invalid math expression cases.
	 *
	 * Format: [ value, decimal_separator, thousand_separator ]
	 */
	public function provider_invalid_math_expressions(): array {
		return array(
			// Plain numbers.
			'plain integer'                 => array( '10', '.', ',' ),
			'plain decimal'                 => array( '10.00', '.', ',' ),
			'plain EU decimal'              => array( '10,00', ',', '.' ),

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
			'empty string'                  => array( '', '.', ',' ),

			// Shortcodes — handled separately by $contains_shortcodes check.
			'shortcode qty'                 => array( '[qty]', '.', ',' ),
			'expression with shortcode'     => array( '10 * [qty]', '.', ',' ),
		);
	}
}
