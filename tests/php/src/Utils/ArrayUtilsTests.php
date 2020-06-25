<?php
/**
 * Tests for ArrayUtils
 *
 * @package Automattic\WooCommerce\Tests\Utils
 */

use Automattic\WooCommerce\Utils\ArrayUtils;

/**
 * Tests for ArrayUtils
 */
class ArrayUtilsTests extends WC_Unit_Test_Case {

	/**
	 * @testdox get_nested_value should return null if the requested key doesn't exist and no default value is supplied.
	 *
	 * @testWith ["foo"]
	 *           ["foo::bar"]
	 *
	 * @param string $key The key to test.
	 */
	public function test_get_nested_value_returns_null_if_non_existing_key_and_default_not_supplied( $key ) {
		$array  = array( 'fizz' => 'buzz' );
		$actual = ArrayUtils::get_nested_value( $array, $key );

		$this->assertNull( $actual );
	}

	/**
	 * @testdox get_nested_value should return the supplied default value if the requested key doesn't exist.
	 *
	 * @testWith ["foo"]
	 *           ["foo::bar"]
	 *
	 * @param string $key The key to test.
	 */
	public function test_get_nested_value_returns_supplied_default_if_non_existing_key( $key ) {
		$array  = array( 'fizz' => 'buzz' );
		$actual = ArrayUtils::get_nested_value( $array, $key, 'DEFAULT' );

		$this->assertEquals( 'DEFAULT', $actual );
	}

	/**
	 * @testdox get_nested_value should return the proper value when a simple key is passed.
	 */
	public function test_get_nested_value_works_for_simple_keys() {
		$array  = array( 'foo' => 'bar' );
		$actual = ArrayUtils::get_nested_value( $array, 'foo' );

		$this->assertEquals( 'bar', $actual );
	}

	/**
	 * @testdox get_nested_value should return the proper value when a nested key is passed.
	 */
	public function test_get_nested_value_works_for_nested_keys() {
		$array  = array(
			'foo' => array(
				'bar' => array(
					'fizz' => 'buzz',
				),
			),
		);
		$actual = ArrayUtils::get_nested_value( $array, 'foo::bar::fizz' );

		$this->assertEquals( 'buzz', $actual );
	}
}
