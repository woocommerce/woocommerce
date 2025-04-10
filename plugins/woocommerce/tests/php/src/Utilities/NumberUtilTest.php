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
	 * @testdox `round` should return 1 when passing the boolean 'true'.
	 */
	public function test_round_when_passing_the_boolean_true() {
		$actual   = NumberUtil::round( true );
		$expected = 1;
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
