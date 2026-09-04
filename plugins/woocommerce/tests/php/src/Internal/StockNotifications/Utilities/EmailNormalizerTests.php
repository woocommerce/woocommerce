<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EmailNormalizer;

/**
 * Tests for EmailNormalizer.
 */
class EmailNormalizerTests extends \WC_Unit_Test_Case {

	/**
	 * @testdox normalize() should trim and lowercase while preserving plus tags and dots.
	 * @testWith [" Foo@Bar.COM ", "foo@bar.com"]
	 *           ["First.Last+Tag@Example.com", "first.last+tag@example.com"]
	 *           ["deleted@site.invalid", "deleted@site.invalid"]
	 *           ["not-an-email", "not-an-email"]
	 *
	 * @param string $input    Raw input.
	 * @param string $expected Expected canonical form.
	 */
	public function test_normalize( string $input, string $expected ): void {
		$this->assertSame( $expected, EmailNormalizer::normalize( $input ) );
	}

	/**
	 * @testdox sanitize() should return the canonical form for valid input and an empty string otherwise.
	 * @testWith [" Foo@Bar.COM ", "foo@bar.com"]
	 *           ["First.Last+Tag@Example.com", "first.last+tag@example.com"]
	 *           ["not-an-email", ""]
	 *           ["", ""]
	 *
	 * @param string $input    Raw input.
	 * @param string $expected Expected result.
	 */
	public function test_sanitize( string $input, string $expected ): void {
		$this->assertSame( $expected, EmailNormalizer::sanitize( $input ) );
	}
}
