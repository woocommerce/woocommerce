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
}
