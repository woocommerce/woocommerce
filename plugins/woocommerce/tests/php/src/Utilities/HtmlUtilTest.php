<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\HtmlUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the HtmlUtil class.
 */
class HtmlUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox esc_html_exact() output reads as the original text after wptexturize() and HTML decoding.
	 * @testWith ["info@48x17.com"]
	 *           ["shop--orders@example.com"]
	 *           ["o'neil@example.com"]
	 *           ["\"john..doe\"@example.com"]
	 *           ["a...b@example.com"]
	 *           ["a`b@example.com"]
	 *           ["a``b@example.com"]
	 *           ["a&b@example.com"]
	 *           ["first_last+tag@example.com"]
	 *           ["555--0100"]
	 *           ["+1 (555) 010-0100 x12"]
	 *           ["555 &#x41;"]
	 *           ["555 &amp; 010"]
	 *           ["555-0100 (tm)"]
	 *           ["<b>555</b> \"0100\""]
	 *           ["Ünïcødé--1 (tm)"]
	 *
	 * @param string $text Text to escape.
	 */
	public function test_esc_html_exact_round_trips_through_wptexturize( string $text ): void {
		$escaped = HtmlUtil::esc_html_exact( $text );

		$this->assertSame( $text, html_entity_decode( wptexturize( $escaped ), ENT_QUOTES | ENT_HTML5 ), 'The browser should show the original text.' );
		$this->assertSame( $escaped, wptexturize( $escaped ), 'wptexturize() should leave the escaped text unchanged.' );
		$this->assertDoesNotMatchRegularExpression( '/[<>"\']|&(?!#[0-9]+;)/', $escaped, 'The output should be safe for an HTML text node.' );
	}

	/**
	 * @testdox esc_html_exact() writes printable ASCII characters as numeric entities and keeps other characters.
	 * @testWith ["a-1", "&#97;&#45;&#49;"]
	 *           ["ü-1", "ü&#45;&#49;"]
	 *
	 * @param string $text     Text to escape.
	 * @param string $expected Expected escaped HTML.
	 */
	public function test_esc_html_exact_encodes_ascii_as_entities( string $text, string $expected ): void {
		$this->assertSame( $expected, HtmlUtil::esc_html_exact( $text ) );
	}
}
