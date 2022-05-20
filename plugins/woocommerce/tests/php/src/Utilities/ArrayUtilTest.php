<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * A collection of tests for the array utility class.
 */
class ArrayUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `get_nested_value` should return null if the requested key doesn't exist and no default value is supplied.
	 *
	 * @testWith ["foo"]
	 *           ["foo::bar"]
	 *
	 * @param string $key The key to test.
	 */
	public function test_get_nested_value_returns_null_if_non_existing_key_and_default_not_supplied( $key ) {
		$array  = array( 'fizz' => 'buzz' );
		$actual = ArrayUtil::get_nested_value( $array, $key );

		$this->assertNull( $actual );
	}

	/**
	 * @testdox `get_nested_value` should return the supplied default value if the requested key doesn't exist.
	 *
	 * @testWith ["foo"]
	 *           ["foo::bar"]
	 *
	 * @param string $key The key to test.
	 */
	public function test_get_nested_value_returns_supplied_default_if_non_existing_key( $key ) {
		$array  = array( 'fizz' => 'buzz' );
		$actual = ArrayUtil::get_nested_value( $array, $key, 'DEFAULT' );

		$this->assertEquals( 'DEFAULT', $actual );
	}

	/**
	 * @testdox `get_nested_value` should return the proper value when a simple key is passed.
	 */
	public function test_get_nested_value_works_for_simple_keys() {
		$array  = array( 'foo' => 'bar' );
		$actual = ArrayUtil::get_nested_value( $array, 'foo' );

		$this->assertEquals( 'bar', $actual );
	}

	/**
	 * @testdox `get_nested_value` should return the proper value when a nested key is passed.
	 */
	public function test_get_nested_value_works_for_nested_keys() {
		$array  = array(
			'foo' => array(
				'bar' => array(
					'fizz' => 'buzz',
				),
			),
		);
		$actual = ArrayUtil::get_nested_value( $array, 'foo::bar::fizz' );

		$this->assertEquals( 'buzz', $actual );
	}

	/**
	 * @testdox `is_truthy` returns false when the key does not exist in the array.
	 */
	public function test_is_truthy_returns_false_if_key_does_not_exist() {
		$array = array( 'foo' => 'bar' );

		$this->assertFalse( ArrayUtil::is_truthy( $array, 'fizz' ) );
	}

	/**
	 * @testdox `is_truthy` returns false for values that evaluate to false.
	 *
	 * @testWith [0]
	 *           ["0"]
	 *           [false]
	 *           [""]
	 *           [null]
	 *           [[]]
	 *
	 * @param mixed $value Value to test.
	 */
	public function test_is_truthy_returns_false_if_value_evaluates_to_false( $value ) {
		$array = array( 'foo' => $value );

		$this->assertFalse( ArrayUtil::is_truthy( $array, 'foo' ) );
	}

	/**
	 * @testdox `is_truthy` returns true for values that evaluate to true.
	 *
	 * @testWith [1]
	 *           ["foo"]
	 *           [true]
	 *           [[1]]
	 *
	 * @param mixed $value Value to test.
	 */
	public function test_is_truthy_returns_false_if_value_evaluates_to_true( $value ) {
		$array = array( 'foo' => $value );

		$this->assertTrue( ArrayUtil::is_truthy( $array, 'foo' ) );
	}

	/**
	 * @testdox `get_value_or_default` returns the correct value for an existing key.
	 */
	public function test_get_value_or_default_returns_value_if_key_exists() {
		$array = array( 'foo' => 'bar' );

		$this->assertEquals( 'bar', ArrayUtil::get_value_or_default( $array, 'foo' ) );
	}

	/**
	 * @testdox `get_value_or_default` returns null if the key does not exist and no default value is supplied.
	 */
	public function test_get_value_or_default_returns_null_if_key_not_exists_and_no_default_supplied() {
		$array = array( 'foo' => 'bar' );

		$this->assertNull( ArrayUtil::get_value_or_default( $array, 'fizz' ) );
	}

	/**
	 * @testdox `get_value_or_default` returns the supplied default value if the key does not exist.
	 */
	public function test_get_value_or_default_returns_supplied_default_value_if_key_not_exists() {
		$array = array( 'foo' => 'bar' );

		$this->assertEquals( 'buzz', ArrayUtil::get_value_or_default( $array, 'fizz', 'buzz' ) );
	}

	/**
	 * Data provider for test_to_ranges_string
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_to_ranges_string(): array {
		return array(
			array( '1', array( 1 ) ),
			array( '1, 3, 5, 7, 9, 11, 13-15', array( 1, 3, 5, 7, 9, 11, 13, 14, 15 ) ),
			array( '1-5', array( 1, 2, 3, 4, 5 ) ),
			array( '7-10', array( 7, 8, 9, 10 ) ),
			array( '1-3, 5, 7-8', array( 1, 2, 3, 5, 7, 8 ) ),
			array( '1-5, 10-12', array( 1, 2, 3, 4, 5, 10, 11, 12 ) ),
			array( '1-5, 7', array( 1, 2, 3, 4, 5, 7 ) ),
			array( '10, 12-15', array( 10, 12, 13, 14, 15 ) ),
			array( '10, 12-15, 101', array( 10, 12, 13, 14, 15, 101 ) ),
			array( '1-5, 7, 10-12', array( 1, 2, 3, 4, 5, 7, 10, 11, 12 ) ),
			array( '1-5, 7, 10-12, 101', array( 1, 2, 3, 4, 5, 7, 10, 11, 12, 101 ) ),
			array( '1-5, 7, 10, 12, 14', array( 14, 12, 10, 1, 2, 3, 4, 5, 7 ) ),
		);
	}

	/**
	 * @testdox `to_ranges_string` works as expected with the default arguments.
	 * @dataProvider data_provider_for_test_to_ranges_string
	 *
	 * @param string $expected_string The expected generated string.
	 * @param array  $input_array The input array of numbers.
	 */
	public function test_to_ranges_string( string $expected_string, array $input_array ) {
		$actual = ArrayUtil::to_ranges_string( $input_array );
		$this->assertEquals( $expected_string, $actual );
	}
}
