<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * A collection of tests for the string utility class.
 */
class NumberUtilTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number.
	 */
	public function test_round_when_passing_a_number() {
		$actual   = NumberUtil::round( 1234.5 );
		$expected = 1235;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number and precision.
	 */
	public function test_round_when_passing_a_number_and_precision() {
		$actual   = NumberUtil::round( 1234.5678, 2 );
		$expected = 1234.57;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number and a mode flag.
	 */
	public function test_round_when_passing_a_number_and_mode_flag() {
		$actual   = NumberUtil::round( 1234.5, 0, PHP_ROUND_HALF_DOWN );
		$expected = 1234;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number-like string.
	 */
	public function test_round_when_passing_a_number_like_string() {
		$actual   = NumberUtil::round( '1234.5678' );
		$expected = 1235;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number-like string and precision.
	 */
	public function test_round_when_passing_a_number_like_string_and_precision() {
		$actual   = NumberUtil::round( '1234.5678', 2 );
		$expected = 1234.57;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number-like string and a mode flag.
	 */
	public function test_round_when_passing_a_number_like_string_and_mode_flag() {
		$actual   = NumberUtil::round( '1234.5', 0, PHP_ROUND_HALF_DOWN );
		$expected = 1234;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should work as the built-in function of the same name when passing a number-like string with spaces.
	 */
	public function test_round_when_passing_a_number_like_string_with_spaces() {
		$actual   = NumberUtil::round( '  1234.5678  ' );
		$expected = 1235;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for the `round` tests for non-numeric values.
	 *
	 * @return array Values to test.
	 */
	public function data_provider_for_test_round_when_passing_a_non_number_like_string() {
		return array(
			array( null ),
			array( '' ),
			array( 'foobar' ),
			array( array() ),
			array( false ),
		);
	}

	/**
	 * @testdox `round` should return 0 when passing a non-numeric value except 'true'.
	 *
	 * @dataProvider data_provider_for_test_round_when_passing_a_non_number_like_string
	 *
	 * @param mixed $value Value to test.
	 */
	public function test_round_when_passing_a_non_number_like_string( $value ) {
		$actual   = NumberUtil::round( $value );
		$expected = 0;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `round` should return 0 due to it not being numeric.
	 */
	public function test_round_when_passing_the_boolean_true() {
		$actual   = NumberUtil::round( true );
		$expected = 0;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for test_array_sum.
	 */
	public function data_provider_for_test_array_sum(): iterable {
		yield 'all numeric values' => array(
			array( 0, '0', 1, '1', 1.1, '1.1', ' 1.1 ' ),
			5.3,
		);
		yield 'all integers' => array(
			array( 1, '1', 2, '2' ),
			6,
		);
		yield 'numeric and non-numeric values' => array(
			// "4th wall" will convert to a valid float since it begins with a number.
			array( 1.1, '1.1', 'we 3 kings', '4th wall', null ),
			6.2,
		);
		yield 'all non-numeric values' => array(
			array( 'macaroni', '&', 'cheese' ),
			0,
		);
		yield 'empty array' => array(
			array(),
			0,
		);
	}

	/**
	 * @testdox array_sum should return a sum of the numeric values in an array, ignoring non-numeric
	 * values, and not triggering any warnings (especially with PHP 8.3+).
	 *
	 * @dataProvider data_provider_for_test_array_sum
	 *
	 * @param array $arr The input array for generating the actual value.
	 * @param float $expected The expected result.
	 */
	public function test_array_sum( $arr, $expected ) {
		$actual_sum = NumberUtil::array_sum( $arr );
		$this->assertFloatEquals( $expected, $actual_sum );
	}

	/**
	 * Data provider for test_normalize.
	 *
	 * @return array Test cases.
	 */
	public function data_provider_for_test_normalize(): array {
		return array(
			'integer value'                           => array( 123, 123, 0 ),
			'float value'                             => array( 123.45, 123.45, 0 ),
			'integer string'                          => array( '123', 123.0, 0 ),
			'float string'                            => array( '123.45', 123.45, 0 ),
			'negative integer string'                 => array( '-123', -123.0, 0 ),
			'negative float string'                   => array( '-123.45', -123.45, 0 ),
			'zero string'                             => array( '0', 0.0, 0 ),
			'zero float string'                       => array( '0.0', 0.0, 0 ),
			'non-numeric string with default'         => array( 'abc', 0, 0 ),
			'null with default'                       => array( null, 0, 0 ),
			'empty string with default'               => array( '', 0, 0 ),
			'array with default'                      => array( array(), 0, 0 ),
			'boolean false with default'              => array( false, 0, 0 ),
			'non-numeric string with custom fallback' => array( 'abc', 42, 42 ),
			'null with custom fallback'               => array( null, 42, 42 ),
			'empty string with custom fallback'       => array( '', 42, 42 ),
			'array with custom fallback'              => array( array(), 42, 42 ),
			'boolean false with custom fallback'      => array( false, 42, 42 ),
		);
	}

	/**
	 * @testdox normalize should convert numeric values to proper numeric types and return fallback for non-numeric values.
	 *
	 * @dataProvider data_provider_for_test_normalize
	 *
	 * @param mixed $input The input value to normalize.
	 * @param mixed $expected The expected result.
	 * @param mixed $fallback The fallback value to use.
	 */
	public function test_normalize( $input, $expected, $fallback ) {
		$actual = NumberUtil::normalize( $input, $fallback );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox normalize should return float for string inputs that are numeric.
	 */
	public function test_normalize_returns_float_for_numeric_strings() {
		$actual = NumberUtil::normalize( '123' );
		$this->assertIsFloat( $actual );
		$this->assertEquals( 123.0, $actual );
	}

	/**
	 * @testdox normalize should return original type for non-string numeric inputs.
	 */
	public function test_normalize_returns_original_type_for_numeric_inputs() {
		$int_input   = 123;
		$float_input = 123.45;

		$int_result   = NumberUtil::normalize( $int_input );
		$float_result = NumberUtil::normalize( $float_input );

		$this->assertIsInt( $int_result );
		$this->assertIsFloat( $float_result );
		$this->assertEquals( $int_input, $int_result );
		$this->assertEquals( $float_input, $float_result );
	}

	/**
	 * @testdox normalize should use default fallback of 0 when no fallback is provided.
	 */
	public function test_normalize_uses_default_fallback() {
		$actual = NumberUtil::normalize( 'abc' );
		$this->assertEquals( 0, $actual );
	}

	/**
	 * Data provider for test_normalize_integer.
	 *
	 * @return array Test cases.
	 */
	public function data_provider_for_test_normalize_integer(): array {
		return array(
			'integer'                                 => array( 7, 7 ),
			'negative integer'                        => array( -7, -7 ),
			'integer string'                          => array( '7', 7 ),
			'signed integer string'                   => array( '+7', 7 ),
			'integer string with spaces'              => array( ' 7 ', 7 ),
			'float truncates toward zero'             => array( 7.9, 7 ),
			'negative float truncates toward zero'    => array( -7.9, -7 ),
			'float string truncates toward zero'      => array( '7.9', 7 ),
			'exponent string'                         => array( '1e3', 1000 ),
			'digit string above float precision'      => array( '9007199254740993', 9007199254740993 ),
			'digit string beyond the integer range'   => array( '9223372036854775808', PHP_INT_MAX ),
			'float beyond the integer range'          => array( 9223372036854775808, PHP_INT_MAX ),
			'negative float beyond the integer range' => array( -9223372036854775809, PHP_INT_MIN ),
			'null'                                    => array( null, 10 ),
			'boolean true'                            => array( true, 10 ),
			'boolean false'                           => array( false, 10 ),
			'empty string'                            => array( '', 10 ),
			'non-numeric string'                      => array( 'high', 10 ),
			'hexadecimal string'                      => array( '0x1A', 10 ),
			'array'                                   => array( array( 7 ), 10 ),
			'infinite string'                         => array( '1e309', 10 ),
			'infinity'                                => array( INF, 10 ),
			'negative infinity'                       => array( -INF, 10 ),
			'not a number'                            => array( NAN, 10 ),
		);
	}

	/**
	 * @testdox normalize_integer should convert finite numbers to integers and return the fallback otherwise.
	 *
	 * @dataProvider data_provider_for_test_normalize_integer
	 *
	 * @param mixed $input    The value to normalize.
	 * @param int   $expected The expected result.
	 */
	public function test_normalize_integer( $input, int $expected ): void {
		$this->assertSame( $expected, NumberUtil::normalize_integer( $input, 10 ) );
	}

	/**
	 * Data provider for test_normalize_integer_clamps_to_the_bounds.
	 *
	 * @return array Test cases.
	 */
	public function data_provider_for_test_normalize_integer_clamps_to_the_bounds(): array {
		return array(
			'within both bounds'             => array( 10, 0, 255, 10 ),
			'below the lower bound'          => array( -5, 0, 255, 0 ),
			'above the upper bound'          => array( 300, 0, 255, 255 ),
			'float beyond the integer range' => array( 9223372036854775808, 0, 255, 255 ),
			'equal bounds'                   => array( 7, 3, 3, 3 ),
			'lower bound only'               => array( -5, 0, null, 0 ),
			'upper bound only'               => array( 300, null, 255, 255 ),
		);
	}

	/**
	 * @testdox normalize_integer should clamp the value to the given bounds.
	 *
	 * @dataProvider data_provider_for_test_normalize_integer_clamps_to_the_bounds
	 *
	 * @param mixed    $input    The value to normalize.
	 * @param int|null $min      The lower bound.
	 * @param int|null $max      The upper bound.
	 * @param int      $expected The expected result.
	 */
	public function test_normalize_integer_clamps_to_the_bounds( $input, ?int $min, ?int $max, int $expected ): void {
		$this->assertSame( $expected, NumberUtil::normalize_integer( $input, 10, $min, $max ) );
	}

	/**
	 * @testdox normalize_integer should return the fallback as given, without clamping it.
	 */
	public function test_normalize_integer_does_not_clamp_the_fallback(): void {
		$this->assertSame( 500, NumberUtil::normalize_integer( 'high', 500, 0, 255 ) );
	}

	/**
	 * @testdox normalize_integer should throw when the lower bound is greater than the upper bound.
	 */
	public function test_normalize_integer_throws_when_the_bounds_are_inverted(): void {
		$this->expectException( \InvalidArgumentException::class );
		NumberUtil::normalize_integer( 7, 10, 255, 0 );
	}

	/**
	 * Data provider for test_sanitize_cost_in_current_locale.
	 *
	 * @return array Test cases.
	 */
	public function data_provider_for_test_sanitize_cost_in_current_locale(): array {
		return array(
			'simple integer'                   => array(
				'1234',
				'1234',
				',',
				'.',
				'$',
			),
			'decimal number'                   => array(
				'12.34',
				'12.34',
				',',
				'.',
				'$',
			),
			'number with thousand separator'   => array(
				'1,234.56',
				'1234.56',
				',',
				'.',
				'$',
			),
			'number with currency symbol'      => array(
				'$1234.56',
				'1234.56',
				',',
				'.',
				'$',
			),
			'number with html currency symbol' => array(
				'&#36;1234.56',
				'1234.56',
				',',
				'.',
				'$',
			),
			'european format'                  => array(
				'1.234,56',
				'1234.56',
				'.',
				',',
				'€',
			),
			'zero value'                       => array(
				'0',
				'0',
				',',
				'.',
				'$',
			),
			'zero with decimal'                => array(
				'0.00',
				'0.00',
				',',
				'.',
				'$',
			),
			'empty string'                     => array(
				'',
				'',
				',',
				'.',
				'$',
			),
			'null value'                       => array(
				null,
				'',
				',',
				'.',
				'$',
			),
			'whitespace padded'                => array(
				'  123.45  ',
				'123.45',
				',',
				'.',
				'$',
			),
		);
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should properly format costs according to locale settings
	 *
	 * @dataProvider data_provider_for_test_sanitize_cost_in_current_locale
	 *
	 * @param mixed  $input Input value to test.
	 * @param string $expected Expected result.
	 * @param string $thousand_separator Thousand separator to use.
	 * @param string $decimal_separator Decimal separator to use.
	 * @param string $currency_symbol Currency symbol to use.
	 */
	public function test_sanitize_cost_in_current_locale( $input, string $expected, string $thousand_separator, string $decimal_separator, string $currency_symbol ) {
		// Set up locale settings via WordPress options.
		update_option( 'woocommerce_currency', '$' === $currency_symbol ? 'USD' : 'EUR' );
		update_option( 'woocommerce_price_thousand_sep', $thousand_separator );
		update_option( 'woocommerce_price_decimal_sep', $decimal_separator );

		$actual = NumberUtil::sanitize_cost_in_current_locale( $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should properly handle slashes in input
	 */
	public function test_sanitize_cost_in_current_locale_slashes() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency_pos', 'left' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		$actual = NumberUtil::sanitize_cost_in_current_locale( '1\\,234\\.56' );
		$this->assertEquals( '1234.56', $actual );
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should error with unrelated HTML entities in input
	 */
	public function test_sanitize_cost_in_current_locale_html_entities() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency_pos', 'left' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		$this->expectExceptionMessage( '1,234.56&nbsp; is not a valid numeric value. Allowed characters are numbers, the thousand (,), and decimal (.) separators.' );
		NumberUtil::sanitize_cost_in_current_locale( '&#36;1,234.56&nbsp;' );
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should error with unsafe HTML in input
	 */
	public function test_sanitize_cost_in_current_locale_unsafe_html() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency_pos', 'left' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );

		$this->expectExceptionMessage( '&lt;b&gt;1,234.56&lt;/b&gt;alert(&quot;bad&quot;) is not a valid numeric value. Allowed characters are numbers, the thousand (,), and decimal (.) separators.' );
		NumberUtil::sanitize_cost_in_current_locale( '<b>1,234.56</b><script>alert("bad")</script>' );
	}

	/**
	 * Data provider for test_sanitize_cost_in_current_locale_with_special_thousand_separators.
	 */
	public function data_provider_for_test_sanitize_cost_in_current_locale_with_special_thousand_separators(): array {
		return array(
			// 1. Special chars present but not used in current locale (should error).
			'space in USD locale'                          => array(
				'1 234.56',  // Input with space.
				'',          // Expected output (empty as it should error).
				',',         // Locale thousand sep.
				'.',         // Locale decimal sep.
				'$',         // Currency.
				true,        // Should throw error.
			),
			'apostrophe in USD locale'                     => array(
				"1'234.56",
				'',
				',',
				'.',
				'$',
				true,
			),

			// 2. Special chars present and correctly used in appropriate locales.
			'space correctly used in FR locale'            => array(
				'1 234,56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),
			'apostrophe correctly used in CH locale'       => array(
				"1'234.56",
				'1234.56',
				"'",
				'.',
				'CHF',
				false,
			),

			// 3. Special chars present but incorrectly used
			'incorrect space position in FR locale'        => array(
				'12 34,56',
				'1234.56',
				' ',
				',',
				'€',
				false, // This is a borderline edge case but I think it's recoverable and is more likely to result in false positives if we error here.
			),
			'mixed separators in FR locale'                => array(
				"1'234,56",
				'',
				' ',
				',',
				'€',
				true,
			),
			'incorrect apostrophe position in CH locale'   => array(
				"12'34.56",
				'1234.56',
				"'",
				'.',
				'CHF',
				false, // This is a borderline edge case but I think it's recoverable and is more likely to result in false positives if we error here.
			),
			'mixed separators in CH locale'                => array(
				'1 234.56',
				'',
				"'",
				'.',
				'CHF',
				true,
			),

			// 4. Decimal separator without thousands separator.
			'decimal only in USD locale'                   => array(
				'1234.56',
				'1234.56',
				',',
				'.',
				'$',
				false,
			),
			'decimal only in FR locale'                    => array(
				'1234,56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),

			// 5. Thousands separator with decimal
			'thousands and decimal in USD locale'          => array(
				'1,234.56',
				'1234.56',
				',',
				'.',
				'$',
				false,
			),
			'space thousands and decimal in FR locale'     => array(
				'1 234,56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),
			'apostrophe thousands and decimal in CH locale' => array(
				"1'234.56",
				'1234.56',
				"'",
				'.',
				'CHF',
				false,
			),

			// 6. Multiple thousands separators
			'multiple commas in USD locale'                => array(
				'1,234,567.89',
				'1234567.89',
				',',
				'.',
				'$',
				false,
			),
			'multiple spaces in FR locale'                 => array(
				'1 234 567,89',
				'1234567.89',
				' ',
				',',
				'€',
				false,
			),
			'multiple apostrophes in CH locale'            => array(
				"1'234'567.89",
				'1234567.89',
				"'",
				'.',
				'CHF',
				false,
			),

			// 7. Invalid multiple separators (Technically invalid but I think it is recoverable and is more likely to result in false positives if we error)
			'invalid multiple commas in USD locale'        => array(
				'1,23,4.56',
				'1234.56',
				',',
				'.',
				'$',
				false,
			),
			'invalid multiple spaces in FR locale'         => array(
				'1 23 4,56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),
			'invalid multiple apostrophes in CH locale'    => array(
				"1'23'4.56",
				'1234.56',
				"'",
				'.',
				'CHF',
				false,
			),

			// 8. Standard decimal format across different locales
			'standard decimal in USD locale'               => array(
				'1234.56',
				'1234.56',
				',',
				'.',
				'$',
				false,
			),
			'standard decimal in FR locale'                => array(
				'1234.56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),
			'standard decimal in CH locale'                => array(
				'1234.56',
				'1234.56',
				"'",
				'.',
				'CHF',
				false,
			),
			'standard decimal with thousands in USD locale' => array(
				'1234567.89',
				'1234567.89',
				',',
				'.',
				'$',
				false,
			),
			'standard decimal with thousands in FR locale' => array(
				'1234567.89',
				'1234567.89',
				' ',
				',',
				'€',
				false,
			),
			'standard decimal with thousands in CH locale' => array(
				'1234567.89',
				'1234567.89',
				"'",
				'.',
				'CHF',
				false,
			),
			'standard decimal with period thousand and comma decimal locale' => array(
				'1234.56',
				'1234.56',
				'.',
				',',
				'€',
				false,
			),
			'standard decimal with comma thousand and period decimal locale' => array(
				'1234.56',
				'1234.56',
				',',
				'.',
				'$',
				false,
			),
			'standard decimal with space thousand and comma decimal locale' => array(
				'1234.56',
				'1234.56',
				' ',
				',',
				'€',
				false,
			),
			'standard decimal with apostrophe thousand and period decimal locale' => array(
				'1234.56',
				'1234.56',
				"'",
				'.',
				'CHF',
				false,
			),
		);
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should properly handle special thousand separators (space and apostrophe)
	 *
	 * @dataProvider data_provider_for_test_sanitize_cost_in_current_locale_with_special_thousand_separators
	 *
	 * @param string  $input              Input value to test.
	 * @param string  $expected           Expected result.
	 * @param string  $thousand_separator Thousand separator to use.
	 * @param string  $decimal_separator  Decimal separator to use.
	 * @param string  $currency_symbol    Currency symbol to use.
	 * @param boolean $should_throw       Whether the test should throw an exception.
	 */
	public function test_sanitize_cost_in_current_locale_with_special_thousand_separators(
		$input,
		string $expected,
		string $thousand_separator,
		string $decimal_separator,
		string $currency_symbol,
		bool $should_throw
	) {
		// Set up locale settings via WordPress options.
		update_option( 'woocommerce_currency', '$' === $currency_symbol ? 'USD' : ( '€' === $currency_symbol ? 'EUR' : 'CHF' ) );
		update_option( 'woocommerce_price_thousand_sep', $thousand_separator );
		update_option( 'woocommerce_price_decimal_sep', $decimal_separator );

		if ( $should_throw ) {
			try {
				$actual = NumberUtil::sanitize_cost_in_current_locale( $input );
				$this->fail( 'Expected exception was not thrown' );
			} catch ( \Exception $e ) {
				$this->assertStringContainsString( 'is not a valid numeric value', $e->getMessage() );
				return;
			}
		}

		$actual = NumberUtil::sanitize_cost_in_current_locale( $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for test_sanitize_cost_in_current_locale_decimal_separator_validation
	 */
	public function data_provider_for_test_sanitize_cost_in_current_locale_decimal_separator_validation(): array {
		return array(
			'multiple decimal separators USD'       => array(
				'1,234.56.78',
				',',
				'.',
				'$',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
			'multiple decimal separators EUR'       => array(
				'1 234,56,78',
				' ',
				',',
				'€',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
			'multiple decimal separators'           => array(
				'1.234.56',
				',',
				'.',
				'$',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
			'decimal before thousand separator USD' => array(
				'1.234,567',
				',',
				'.',
				'$',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
			'decimal before thousand separator EUR' => array(
				'1,234.567',
				'.',
				',',
				'€',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
			'decimal before thousand separator CHF' => array(
				'1.234\'567',
				'\'',
				'.',
				'CHF',
				'is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator',
			),
		);
	}

	/**
	 * @testdox sanitize_cost_in_current_locale should properly validate decimal separator placement
	 *
	 * @dataProvider data_provider_for_test_sanitize_cost_in_current_locale_decimal_separator_validation
	 *
	 * @param string $input Input value to test.
	 * @param string $thousand_separator Thousand separator to use.
	 * @param string $decimal_separator Decimal separator to use.
	 * @param string $currency_symbol Currency symbol to use.
	 * @param string $expected_error Expected error message.
	 */
	public function test_sanitize_cost_in_current_locale_decimal_separator_validation(
		string $input,
		string $thousand_separator,
		string $decimal_separator,
		string $currency_symbol,
		string $expected_error
	) {
		// Set up locale settings.
		update_option( 'woocommerce_currency', '$' === $currency_symbol ? 'USD' : ( '€' === $currency_symbol ? 'EUR' : 'CHF' ) );
		update_option( 'woocommerce_price_thousand_sep', $thousand_separator );
		update_option( 'woocommerce_price_decimal_sep', $decimal_separator );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( $expected_error );

		NumberUtil::sanitize_cost_in_current_locale( $input );
	}
}
