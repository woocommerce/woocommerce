<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use WC_Unit_Test_Case;

/**
 * A collection of tests for the Internal\Utilities\ArrayUtil class.
 */
class ArrayUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox `array_is_list` should return true for an empty array.
	 */
	public function test_array_is_list_empty_array() {
		$this->assertTrue( ArrayUtil::array_is_list( array() ) );
	}

	/**
	 * @testdox `array_is_list` should return true for a sequential array.
	 */
	public function test_array_is_list_sequential_array() {
		$this->assertTrue( ArrayUtil::array_is_list( array( 'a', 'b', 'c' ) ) );
	}

	/**
	 * @testdox `array_is_list` should return false for an associative array.
	 */
	public function test_array_is_list_associative_array() {
		$this->assertFalse( ArrayUtil::array_is_list( array( 'foo' => 'bar' ) ) );
	}

	/**
	 * @testdox `array_is_list` should return false for an array with non-consecutive keys.
	 */
	public function test_array_is_list_non_consecutive_keys() {
		$this->assertFalse(
			ArrayUtil::array_is_list(
				array(
					0 => 'a',
					2 => 'b',
				)
			)
		);
	}

	/**
	 * @testdox `merge_by_key` should merge two arrays by a common key.
	 */
	public function test_merge_by_key_basic() {
		$arr1 = array(
			array(
				'id'   => 1,
				'name' => 'John',
			),
			array(
				'id'   => 2,
				'name' => 'Jane',
			),
		);
		$arr2 = array(
			array(
				'id'  => 2,
				'age' => 25,
			),
			array(
				'id'  => 1,
				'age' => 30,
			),
		);

		$result = ArrayUtil::merge_by_key( $arr1, $arr2, 'id' );

		$expected = array(
			array(
				'id'   => 1,
				'name' => 'John',
				'age'  => 30,
			),
			array(
				'id'   => 2,
				'name' => 'Jane',
				'age'  => 25,
			),
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox `merge_by_key` should append items from arr2 that don't exist in arr1.
	 */
	public function test_merge_by_key_append_new_items() {
		$arr1 = array(
			array(
				'id'   => 1,
				'name' => 'John',
			),
		);
		$arr2 = array(
			array(
				'id'   => 2,
				'name' => 'Jane',
			),
			array(
				'id'   => 3,
				'name' => 'Bob',
			),
		);

		$result = ArrayUtil::merge_by_key( $arr1, $arr2, 'id' );

		$this->assertCount( 3, $result );
		$this->assertEquals( 1, $result[0]['id'] );
		$this->assertEquals( 2, $result[1]['id'] );
		$this->assertEquals( 3, $result[2]['id'] );
	}

	/**
	 * @testdox `filter_null_values_recursive` should return an empty array when given an empty array.
	 */
	public function test_filter_null_values_recursive_empty_array() {
		$result = ArrayUtil::filter_null_values_recursive( array() );
		$this->assertEquals( array(), $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should remove null values from a simple associative array.
	 */
	public function test_filter_null_values_recursive_simple_associative_array() {
		$input = array(
			'name' => 'John',
			'age'  => null,
			'city' => 'New York',
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'name' => 'John',
			'city' => 'New York',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should remove null values from a list and reindex.
	 */
	public function test_filter_null_values_recursive_list_reindex() {
		$input = array( 'apple', null, 'banana', null, 'cherry' );

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array( 'apple', 'banana', 'cherry' );

		$this->assertEquals( $expected, $result );
		$this->assertTrue( ArrayUtil::array_is_list( $result ) );
	}

	/**
	 * @testdox `filter_null_values_recursive` should preserve non-null values including zero, false, and empty strings.
	 */
	public function test_filter_null_values_recursive_preserves_falsy_values() {
		$input = array(
			'zero'         => 0,
			'false'        => false,
			'empty_string' => '',
			'null'         => null,
			'empty_array'  => array(),
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'zero'         => 0,
			'false'        => false,
			'empty_string' => '',
			'empty_array'  => array(),
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should remove null values from nested arrays.
	 */
	public function test_filter_null_values_recursive_nested_arrays() {
		$input = array(
			'name'    => 'John',
			'age'     => null,
			'address' => array(
				'street' => '123 Main St',
				'city'   => null,
				'state'  => 'NY',
				'coords' => array(
					'lat'  => 40.7128,
					'long' => null,
				),
			),
			'phone'   => null,
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'name'    => 'John',
			'address' => array(
				'street' => '123 Main St',
				'state'  => 'NY',
				'coords' => array(
					'lat' => 40.7128,
				),
			),
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle nested lists correctly.
	 */
	public function test_filter_null_values_recursive_nested_lists() {
		$input = array(
			'items' => array( 'apple', null, 'banana', null, 'cherry' ),
			'tags'  => array( 'tag1', 'tag2', null, 'tag3' ),
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'items' => array( 'apple', 'banana', 'cherry' ),
			'tags'  => array( 'tag1', 'tag2', 'tag3' ),
		);

		$this->assertEquals( $expected, $result );
		$this->assertTrue( ArrayUtil::array_is_list( $result['items'] ) );
		$this->assertTrue( ArrayUtil::array_is_list( $result['tags'] ) );
	}

	/**
	 * @testdox `filter_null_values_recursive` should remove null values from deeply nested arrays.
	 */
	public function test_filter_null_values_recursive_deeply_nested() {
		$input = array(
			'level1' => array(
				'level2' => array(
					'level3' => array(
						'value' => 'deep',
						'null'  => null,
					),
					'null'   => null,
				),
				'value'  => 'mid',
			),
			'null'   => null,
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'level1' => array(
				'level2' => array(
					'level3' => array(
						'value' => 'deep',
					),
				),
				'value'  => 'mid',
			),
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle list of associative arrays.
	 */
	public function test_filter_null_values_recursive_list_of_associative_arrays() {
		$input = array(
			array(
				'name' => 'John',
				'age'  => null,
			),
			null,
			array(
				'name' => 'Jane',
				'age'  => 25,
			),
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			array( 'name' => 'John' ),
			array(
				'name' => 'Jane',
				'age'  => 25,
			),
		);

		$this->assertEquals( $expected, $result );
		$this->assertTrue( ArrayUtil::array_is_list( $result ) );
	}

	/**
	 * @testdox `filter_null_values_recursive` should return the same array when no null values exist.
	 */
	public function test_filter_null_values_recursive_no_null_values() {
		$input = array(
			'name'    => 'John',
			'age'     => 30,
			'address' => array(
				'street' => '123 Main St',
				'city'   => 'New York',
			),
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$this->assertEquals( $input, $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle arrays with all null values.
	 */
	public function test_filter_null_values_recursive_all_null_values() {
		$input = array(
			'null1' => null,
			'null2' => null,
			'null3' => null,
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$this->assertEquals( array(), $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle list with all null values.
	 */
	public function test_filter_null_values_recursive_list_all_null_values() {
		$input = array( null, null, null );

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$this->assertEquals( array(), $result );
		$this->assertTrue( ArrayUtil::array_is_list( $result ) );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle mixed data types.
	 */
	public function test_filter_null_values_recursive_mixed_types() {
		$input = array(
			'string'  => 'test',
			'integer' => 42,
			'float'   => 3.14,
			'boolean' => true,
			'null'    => null,
			'array'   => array(
				'nested' => 'value',
				'null'   => null,
			),
			'object'  => (object) array( 'prop' => 'value' ),
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$this->assertEquals( 'test', $result['string'] );
		$this->assertEquals( 42, $result['integer'] );
		$this->assertEquals( 3.14, $result['float'] );
		$this->assertTrue( $result['boolean'] );
		$this->assertArrayNotHasKey( 'null', $result );
		$this->assertEquals( array( 'nested' => 'value' ), $result['array'] );
		$this->assertIsObject( $result['object'] );
	}

	/**
	 * @testdox `filter_null_values_recursive` should preserve associative array keys.
	 */
	public function test_filter_null_values_recursive_preserves_associative_keys() {
		$input = array(
			'key1' => 'value1',
			'key2' => null,
			'key3' => 'value3',
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$this->assertArrayHasKey( 'key1', $result );
		$this->assertArrayNotHasKey( 'key2', $result );
		$this->assertArrayHasKey( 'key3', $result );
	}

	/**
	 * @testdox `filter_null_values_recursive` should handle complex nested structure with mixed lists and associative arrays.
	 */
	public function test_filter_null_values_recursive_complex_structure() {
		$input = array(
			'users' => array(
				array(
					'id'      => 1,
					'name'    => 'John',
					'email'   => null,
					'hobbies' => array( 'reading', null, 'gaming' ),
				),
				null,
				array(
					'id'      => 2,
					'name'    => 'Jane',
					'email'   => 'jane@example.com',
					'hobbies' => array( 'cooking', 'traveling' ),
				),
			),
			'count' => 2,
			'meta'  => null,
		);

		$result = ArrayUtil::filter_null_values_recursive( $input );

		$expected = array(
			'users' => array(
				array(
					'id'      => 1,
					'name'    => 'John',
					'hobbies' => array( 'reading', 'gaming' ),
				),
				array(
					'id'      => 2,
					'name'    => 'Jane',
					'email'   => 'jane@example.com',
					'hobbies' => array( 'cooking', 'traveling' ),
				),
			),
			'count' => 2,
		);

		$this->assertEquals( $expected, $result );
		$this->assertTrue( ArrayUtil::array_is_list( $result['users'] ) );
		$this->assertTrue( ArrayUtil::array_is_list( $result['users'][0]['hobbies'] ) );
		$this->assertTrue( ArrayUtil::array_is_list( $result['users'][1]['hobbies'] ) );
	}
}
