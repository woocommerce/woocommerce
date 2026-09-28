<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\DrawerName;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the DrawerName class.
 */
class DrawerNameTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should trim surrounding whitespace and keep the display spelling.
	 */
	public function test_normalize_trims_and_keeps_spelling(): void {
		$this->assertSame( 'Front counter', DrawerName::normalize( "  Front counter \t\n" ) );
		$this->assertSame( 'Front counter', DrawerName::normalize( "\u{00A0}Front counter\u{3000}" ), 'Unicode spaces should be trimmed' );
		$this->assertSame( 'Front counter', DrawerName::normalize( 'Front  counter' ), 'Whitespace should be normalized' );
	}

	/**
	 * @testdox Should remove markup and control characters before building a drawer key.
	 */
	public function test_normalize_sanitizes_name(): void {
		$this->assertSame( 'Café counter', DrawerName::normalize( "<b>Café</b> counter\x01\x7f" ) );
		$this->assertSame( DrawerName::key( 'Café counter' ), DrawerName::key( DrawerName::normalize( '<b>Café</b> counter' ) ) );
	}

	/**
	 * @testdox Should reject names that are blank after trimming.
	 *
	 * @testWith [""]
	 *           ["   "]
	 *           ["<b></b>"]
	 *           ["\u0001"]
	 *           [" \t"]
	 *
	 * @param string $value Test value.
	 */
	public function test_normalize_rejects_blank( string $value ): void {
		$this->expectException( InvalidArgumentException::class );
		DrawerName::normalize( $value );
	}

	/**
	 * @testdox Should count characters, not bytes, for the length limit.
	 */
	public function test_normalize_length_limit(): void {
		$this->assertSame( str_repeat( 'é', 128 ), DrawerName::normalize( ' ' . str_repeat( 'é', 128 ) . ' ' ) );

		$this->expectException( InvalidArgumentException::class );
		DrawerName::normalize( str_repeat( 'a', 129 ) );
	}

	/**
	 * @testdox Should compare names case-insensitively through the key.
	 */
	public function test_key_is_case_insensitive(): void {
		$this->assertSame( DrawerName::key( 'Front counter' ), DrawerName::key( 'FRONT COUNTER' ) );
		$this->assertSame( DrawerName::key( 'Caja ÑANDÚ' ), DrawerName::key( 'caja ñandú' ) );
		$this->assertNotSame( DrawerName::key( 'Front counter' ), DrawerName::key( 'Back counter' ) );
		$this->assertNotSame( DrawerName::key( 'Café' ), DrawerName::key( 'Cafe' ), 'Accents are not folded' );
	}

	/**
	 * @testdox Should give a fixed-width key even when lowercasing adds characters.
	 */
	public function test_key_has_fixed_width(): void {
		$this->assertSame( 64, strlen( DrawerName::key( str_repeat( 'İ', DrawerName::MAX_LENGTH ) ) ) );
	}
}
