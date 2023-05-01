<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * A collection of tests for the string utility class.
 */
class StringUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `starts_with` should check whether one string starts with another.
	 */
	public function test_starts_with() {
		$this->assertTrue( StringUtil::starts_with( 'test', 'te' ) );
		$this->assertTrue( StringUtil::starts_with( ' foo bar', ' foo' ) );
		$this->assertFalse( StringUtil::starts_with( 'test', 'st' ) );
		$this->assertFalse( StringUtil::starts_with( ' foo bar', ' bar' ) );

		$this->assertTrue( StringUtil::starts_with( 'TEST', 'te', false ) );
		$this->assertTrue( StringUtil::starts_with( ' FOO BAR', ' foo', false ) );
		$this->assertFalse( StringUtil::starts_with( 'TEST', 'st', false ) );
		$this->assertFalse( StringUtil::starts_with( ' FOO BAR', ' bar', false ) );

		$this->assertTrue( StringUtil::starts_with( 'test', 'TE', false ) );
		$this->assertTrue( StringUtil::starts_with( ' foo bar', ' FOO', false ) );
		$this->assertFalse( StringUtil::starts_with( 'test', 'ST', false ) );
		$this->assertFalse( StringUtil::starts_with( ' foo bar', ' BAR', false ) );
	}

	/**
	 * @testdox `ends_with` should check whether one string ends with another.
	 */
	public function test_ends_with() {
		$this->assertFalse( StringUtil::ends_with( 'test', 'te' ) );
		$this->assertFalse( StringUtil::ends_with( ' foo bar', ' foo' ) );
		$this->assertTrue( StringUtil::ends_with( 'test', 'st' ) );
		$this->assertTrue( StringUtil::ends_with( ' foo bar', ' bar' ) );

		$this->assertFalse( StringUtil::ends_with( 'TEST', 'te', false ) );
		$this->assertFalse( StringUtil::ends_with( ' FOO BAR', ' foo', false ) );
		$this->assertTrue( StringUtil::ends_with( 'TEST', 'st', false ) );
		$this->assertTrue( StringUtil::ends_with( ' FOO BAR', ' bar', false ) );

		$this->assertFalse( StringUtil::ends_with( 'test', 'TE', false ) );
		$this->assertFalse( StringUtil::ends_with( ' foo bar', ' FOO', false ) );
		$this->assertTrue( StringUtil::ends_with( 'test', 'ST', false ) );
		$this->assertTrue( StringUtil::ends_with( ' foo bar', ' BAR', false ) );
	}

	/**
	 * @@testdox 'contains' should check whether one string contains another.
	 */
	public function test_contains() {
		$this->assertFalse( StringUtil::contains( 'foobar', 'fizzbuzz' ) );
		$this->assertFalse( StringUtil::contains( 'foobar', 'fizzbuzz', true ) );
		$this->assertFalse( StringUtil::contains( 'foobar', 'fizzbuzz', false ) );

		$this->assertFalse( StringUtil::contains( 'foobar', 'BA' ) );
		$this->assertFalse( StringUtil::contains( 'foobar', 'BA', true ) );
		$this->assertTrue( StringUtil::contains( 'foobar', 'ba' ) );
		$this->assertTrue( StringUtil::contains( 'foobar', 'ba', true ) );

		$this->assertTrue( StringUtil::contains( 'foobar', 'BA', false ) );
	}

	/**
	 * @testdox 'plugin_name_from_plugin_file' returns the plugin name in the form 'directory/file.php' from the plugin file.
	 */
	public function test_plugin_name_from_plugin_file() {
		$file_path = '/home/someone/wordpress/wp-content/plugins/foobar/fizzbuzz.php';
		$result    = StringUtil::plugin_name_from_plugin_file( $file_path );
		$expected  = 'foobar/fizzbuzz.php';
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testDox 'is_null_or_empty' should return true only if the value is null or an empty string.
	 *
	 * @testWith [null, true]
	 *           ["", true]
	 *           ["  ", false]
	 *           ["0", false]
	 *           ["foo", false]
	 *           ["  foo  ", false]
	 *
	 * @param string $value Value to test.
	 * @param bool   $expected Expected result from the method.
	 */
	public function test_is_null_or_empty( $value, $expected ) {
		$result = StringUtil::is_null_or_empty( $value );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testDox 'is_null_or_empty' should return true only if the value is null, an empty string, or consists of only whitespace characters.
	 *
	 * @testWith [null, true]
	 *           ["", true]
	 *           [" \n\r\t\f ", true]
	 *           ["0", false]
	 *           ["foo", false]
	 *           ["  foo  ", false]
	 *
	 * @param string $value Value to test.
	 * @param bool   $expected Expected result from the method.
	 */
	public function test_is_null_or_whitespace( $value, $expected ) {
		$result = StringUtil::is_null_or_whitespace( $value );
		$this->assertEquals( $expected, $result );
	}
}
