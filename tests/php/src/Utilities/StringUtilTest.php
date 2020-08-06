<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * A collection of tests for the string utility class.
 */
class StringUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `starts` should check whether one string starts with another.
	 */
	public function test_starts() {
		$this->assertTrue( StringUtil::starts( 'test', 'te' ) );
		$this->assertTrue( StringUtil::starts( ' foo bar', ' foo' ) );
		$this->assertFalse( StringUtil::starts( 'test', 'st' ) );
		$this->assertFalse( StringUtil::starts( ' foo bar', ' bar' ) );

		$this->assertTrue( StringUtil::starts( 'TEST', 'te', false ) );
		$this->assertTrue( StringUtil::starts( ' FOO BAR', ' foo', false ) );
		$this->assertFalse( StringUtil::starts( 'TEST', 'st', false ) );
		$this->assertFalse( StringUtil::starts( ' FOO BAR', ' bar', false ) );

		$this->assertTrue( StringUtil::starts( 'test', 'TE', false ) );
		$this->assertTrue( StringUtil::starts( ' foo bar', ' FOO', false ) );
		$this->assertFalse( StringUtil::starts( 'test', 'ST', false ) );
		$this->assertFalse( StringUtil::starts( ' foo bar', ' BAR', false ) );
	}

	/**
	 * @testdox `ends` should check whether one string ends with another.
	 */
	public function test_ends() {
		$this->assertFalse( StringUtil::ends( 'test', 'te' ) );
		$this->assertFalse( StringUtil::ends( ' foo bar', ' foo' ) );
		$this->assertTrue( StringUtil::ends( 'test', 'st' ) );
		$this->assertTrue( StringUtil::ends( ' foo bar', ' bar' ) );

		$this->assertFalse( StringUtil::ends( 'TEST', 'te', false ) );
		$this->assertFalse( StringUtil::ends( ' FOO BAR', ' foo', false ) );
		$this->assertTrue( StringUtil::ends( 'TEST', 'st', false ) );
		$this->assertTrue( StringUtil::ends( ' FOO BAR', ' bar', false ) );

		$this->assertFalse( StringUtil::ends( 'test', 'TE', false ) );
		$this->assertFalse( StringUtil::ends( ' foo bar', ' FOO', false ) );
		$this->assertTrue( StringUtil::ends( 'test', 'ST', false ) );
		$this->assertTrue( StringUtil::ends( ' foo bar', ' BAR', false ) );
	}
}
