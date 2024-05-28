<?php

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
}
