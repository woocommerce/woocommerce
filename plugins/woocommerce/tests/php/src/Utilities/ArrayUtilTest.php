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

	/**
	 * @testdox `select` can be used to select a value from an array of arrays based on array key.
	 */
	public function test_select_for_arrays() {
		$items = array(
			array(
				'foo' => 1,
				'bar' => 2,
			),
			array(
				'foo' => 3,
				'bar' => 4,
			),
		);

		$actual = ArrayUtil::select( $items, 'foo' );
		$this->assertEquals( array( 1, 3 ), $actual, ArrayUtil::SELECT_BY_ARRAY_KEY );
	}

	/**
	 * @testdox `select` can be used to select a value from an array of objects based on a method of the objects.
	 */
	public function test_select_for_object_methods() {
		// phpcs:disable Squiz.Commenting
		$items = array(
			new class() {
				public function get_id() {
					return 1;
				}
			},
			new class() {
				public function get_id() {
					return 2;
				}
			},
		);
		// phpcs:enable Squiz.Commenting

		$actual = ArrayUtil::select( $items, 'get_id', ArrayUtil::SELECT_BY_OBJECT_METHOD );
		$this->assertEquals( array( 1, 2 ), $actual );
	}

	/**
	 * @testdox `select` can be used to select a value from an array of objects based on a property of the objects.
	 */
	public function test_select_for_object_properties() {
		// phpcs:disable Squiz.Commenting
		$items = array(
			new class() {
				public $id = 1;
			},
			new class() {
				public $id = 2;
			},
		);
		// phpcs:enable Squiz.Commenting

		$actual = ArrayUtil::select( $items, 'id', ArrayUtil::SELECT_BY_OBJECT_PROPERTY );
		$this->assertEquals( array( 1, 2 ), $actual );
	}

	/**
	 * @testdox `select` can be used to select a value from an array of objects with automatic selection of array key or object method/property.
	 */
	public function test_select_for_mixed() {
		// phpcs:disable Squiz.Commenting
		$items = array(
			array( 'the_id' => 1 ),
			new class() {
				public $the_id = 2;
			},
			new class() {
				public function the_id() {
					return 3;
				}
			},
		);
		// phpcs:enable Squiz.Commenting

		$actual = ArrayUtil::select( $items, 'the_id', ArrayUtil::SELECT_BY_AUTO );
		$this->assertEquals( array( 1, 2, 3 ), $actual );
	}

	/**
	 * @testdox push_once doesn't alter the array and returns false if the item is already in the array.
	 */
	public function test_push_once_existing_value() {
		$array = array( 1, 2, 3 );

		$result = ArrayUtil::push_once( $array, 2 );

		$this->assertFalse( $result );
		$this->assertEquals( array( 1, 2, 3 ), $array );
	}

	/**
	 * @testdox push_once pushes the value in the array and returns true if the value isn't yet in the array.
	 */
	public function test_push_once_new_value() {
		$array = array( 1, 2, 3 );

		$result = ArrayUtil::push_once( $array, 4 );

		$this->assertTrue( $result );
		$this->assertEquals( array( 1, 2, 3, 4 ), $array );
	}


	/**
	 * @testdox `ensure_key_is_array` adds an empty array under the given key if they key doesn't exist already in the array.
	 */
	public function test_ensure_key_is_array_when_key_does_not_exist() {
		$array  = array( 'foo' => 1 );
		$result = ArrayUtil::ensure_key_is_array( $array, 'bar' );
		$this->assertTrue( $result );
		$this->assertEquals(
			array(
				'foo' => 1,
				'bar' => array(),
			),
			$array
		);
	}

	/**
	 * @testdox `ensure_key_is_array` does nothing if the key already exists in the array and $throw_if_existing_is_not_array is false.
	 *
	 * @testWith [[]]
	 *           [1]
	 *           [true]
	 *           ["Foo"]
	 *
	 * @param mixed $value The already existing value.
	 */
	public function test_ensure_key_is_array_when_key_exist_and_not_throwing( $value ) {
		$array  = array(
			'foo' => 1,
			'bar' => $value,
		);
		$result = ArrayUtil::ensure_key_is_array( $array, 'bar' );
		$this->assertFalse( $result );
		$this->assertEquals(
			array(
				'foo' => 1,
				'bar' => $value,
			),
			$array
		);
	}

	/**
	 * @testdox `ensure_key_is_array` does nothing if the key already exists in the array, the value is itself an array, and $throw_if_existing_is_not_array is true.
	 */
	public function test_ensure_key_is_array_when_key_is_array_and_throwing() {
		$array  = array(
			'foo' => 1,
			'bar' => array(),
		);
		$result = ArrayUtil::ensure_key_is_array( $array, 'bar', true );
		$this->assertFalse( $result );
		$this->assertEquals(
			array(
				'foo' => 1,
				'bar' => array(),
			),
			$array
		);
	}

	/**
	 * @testdox `ensure_key_is_array` throws if the key already exists in the array, the value is not an array, and $throw_if_existing_is_not_array is true.
	 */
	public function test_ensure_key_is_array_when_key_is_not_array_and_throwing() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Array key exists but it's not an array, it's a integer" );

		$array = array(
			'foo' => 1,
			'bar' => 2,
		);
		ArrayUtil::ensure_key_is_array( $array, 'bar', true );
	}

	/**
	 * @testdox 'test_key_diff' returns the differences in keys between two associative arrays.
	 *
	 * @testWith [{}, {}, {"extra": [], "missing": []}]
	 *           [{"a": 1, "b": 2}, {"a": 3, "b": 4}, {"extra": [], "missing": []}]
	 *           [{"a": 1, "b": 2}, {"a": 3}, {"extra": [], "missing": ["b"]}]
	 *           [{"a": 1}, {"a": 2, "b": 3}, {"extra": ["b"], "missing": []}]
	 *           [{"a": 1, "b": 2}, {"a": 3, "c": 3}, {"extra": ["c"], "missing": ["b"]}]
	 *           [{"a": 1, "b": 2},  ["a", "b"], {"extra": [], "missing": []}]
	 *           [["a", "b"], {"a": 3, "b": 4}, {"extra": [], "missing": []}]
	 *           [["a", "b"], ["a", "b"], {"extra": [], "missing": []}]
	 *
	 * @param array $main The main array.
	 * @param array $secondary The secondary array.
	 * @param array $expected The expected result from the method.
	 */
	public function test_key_diff( $main, $secondary, $expected ) {
		$result = ArrayUtil::key_diff( $main, $secondary );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'test_key_diff' returns null if there are no differences between the array keys and $null_if_equal is passed as true.
	 */
	public function test_key_diff_with_null_on_equals() {
		$result = ArrayUtil::key_diff( array( 'a' => 1 ), array( 'a' => 2 ), true );
		$this->assertNull( $result );
	}
}
