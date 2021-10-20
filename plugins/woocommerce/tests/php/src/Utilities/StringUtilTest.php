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
}
