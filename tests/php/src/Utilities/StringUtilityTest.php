<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\StringUtility;

/**
 * A collection of tests for the string utility class.
 */
class StringUtilityTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `starts_with` should check whether one string starts with another.
	 */
	public function test_starts_with() {
		$this->assertTrue( StringUtility::starts_with( 'test', 'te' ) );
		$this->assertTrue( StringUtility::starts_with( ' foo bar', ' foo' ) );
		$this->assertFalse( StringUtility::starts_with( 'test', 'st' ) );
		$this->assertFalse( StringUtility::starts_with( ' foo bar', ' bar' ) );

		$this->assertTrue( StringUtility::starts_with( 'TEST', 'te', false ) );
		$this->assertTrue( StringUtility::starts_with( ' FOO BAR', ' foo', false ) );
		$this->assertFalse( StringUtility::starts_with( 'TEST', 'st', false ) );
		$this->assertFalse( StringUtility::starts_with( ' FOO BAR', ' bar', false ) );

		$this->assertTrue( StringUtility::starts_with( 'test', 'TE', false ) );
		$this->assertTrue( StringUtility::starts_with( ' foo bar', ' FOO', false ) );
		$this->assertFalse( StringUtility::starts_with( 'test', 'ST', false ) );
		$this->assertFalse( StringUtility::starts_with( ' foo bar', ' BAR', false ) );
	}

	/**
	 * @testdox `ends_with` should check whether one string ends with another.
	 */
	public function test_ends_with() {
		$this->assertFalse( StringUtility::ends_with( 'test', 'te' ) );
		$this->assertFalse( StringUtility::ends_with( ' foo bar', ' foo' ) );
		$this->assertTrue( StringUtility::ends_with( 'test', 'st' ) );
		$this->assertTrue( StringUtility::ends_with( ' foo bar', ' bar' ) );

		$this->assertFalse( StringUtility::ends_with( 'TEST', 'te', false ) );
		$this->assertFalse( StringUtility::ends_with( ' FOO BAR', ' foo', false ) );
		$this->assertTrue( StringUtility::ends_with( 'TEST', 'st', false ) );
		$this->assertTrue( StringUtility::ends_with( ' FOO BAR', ' bar', false ) );

		$this->assertFalse( StringUtility::ends_with( 'test', 'TE', false ) );
		$this->assertFalse( StringUtility::ends_with( ' foo bar', ' FOO', false ) );
		$this->assertTrue( StringUtility::ends_with( 'test', 'ST', false ) );
		$this->assertTrue( StringUtility::ends_with( ' foo bar', ' BAR', false ) );
	}
}
