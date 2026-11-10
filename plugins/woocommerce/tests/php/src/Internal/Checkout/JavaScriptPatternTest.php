<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Checkout;

use Automattic\WooCommerce\Internal\Checkout\JavaScriptPattern;
use WC_Unit_Test_Case;

/**
 * Tests for the JavaScriptPattern class.
 */
class JavaScriptPatternTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Patterns JavaScript rejects in `u` mode are reported.
	 *
	 * @testWith ["^[0-9]{3}\\-[0-9]{4}$"]
	 *           ["(?i)^abc$"]
	 *           ["^\\@x$"]
	 *           ["\\Aabc\\z"]
	 *           ["^a++$"]
	 *           ["^[[:alpha:]]+$"]
	 *           ["a*+"]
	 *           ["a{2}+"]
	 *           ["\\z"]
	 *           ["\\Z"]
	 *           ["\\h"]
	 *           ["\\R"]
	 *           ["(?>a)"]
	 *           ["(?#comment)a"]
	 *           ["(?P<name>a)"]
	 *           ["[\\B]"]
	 *           ["a}"]
	 *           ["a]"]
	 *           ["a\\"]
	 *           ["\\00"]
	 *           ["\\x4"]
	 *           ["\\u12"]
	 *           ["\\u{}"]
	 *           ["\\pL"]
	 *           ["\\c1"]
	 *           ["[\\k]"]
	 *           ["a{"]
	 *           ["(?x)"]
	 *           ["(?-i)"]
	 *           ["(?R)"]
	 *           ["(?|a)"]
	 *           ["(?=a)+"]
	 *           ["(?=a)*"]
	 *           ["\\10"]
	 *           ["[\\1]"]
	 *           ["\\V"]
	 *           ["\\Qa\\E"]
	 *           ["(a)\\g1"]
	 *           ["\\o{101}"]
	 *           ["(?<n>a)\\k'n'"]
	 *           ["(a)(?1)"]
	 *           ["(?&n)(?<n>a)"]
	 *           ["(?'n'a)"]
	 *           ["(a)(?(1)a|b)"]
	 *
	 * @param string $pattern The pattern to check.
	 */
	public function test_unsupported_patterns_are_reported( string $pattern ): void {
		$this->assertNotNull(
			JavaScriptPattern::find_unsupported_construct( $pattern ),
			sprintf( 'The pattern "%s" should be reported as unsupported by JavaScript.', $pattern )
		);
	}

	/**
	 * @testdox Patterns built from the tokens both engines accept are left alone.
	 *
	 * @testWith ["^[0-9]{5}$"]
	 *           ["^[A-Z]{2}[0-9]{8,12}$"]
	 *           ["^\\+?[0-9 ]{7,15}$"]
	 *           ["^[\\p{L} '-]+$"]
	 *           ["^\\w+$"]
	 *           ["^\\d+$"]
	 *           ["^\\s*$"]
	 *           ["^(?:a|b)+$"]
	 *           ["(?<name>a)\\k<name>"]
	 *           ["(?<=a)b"]
	 *           ["a+?"]
	 *           ["[\\-]"]
	 *           ["[^<>()]*"]
	 *           ["\\$1\\.00"]
	 *           ["^\\u0041$"]
	 *           ["(?i:abc)"]
	 *           [""]
	 *           ["\\u{1}"]
	 *           ["\\u{A}"]
	 *           ["\\u{1F600}"]
	 *           ["\\p{Script=Greek}"]
	 *           ["(?i-s:a)"]
	 *           ["\\cA"]
	 *           ["\\0"]
	 *           ["[\\0]"]
	 *           ["\\10(a)(b)(c)(d)(e)(f)(g)(h)(i)(?<tenth>j)"]
	 *           ["(?:(?=a))+"]
	 *           ["\\b\\B\\d\\D\\f\\n\\r\\s\\S\\t\\v\\w\\W"]
	 *
	 * @param string $pattern The pattern to check.
	 */
	public function test_supported_patterns_are_left_alone( string $pattern ): void {
		$this->assertNull(
			JavaScriptPattern::find_unsupported_construct( $pattern ),
			sprintf( 'The pattern "%s" is valid ECMA-262 and should not be reported.', $pattern )
		);
	}

	/**
	 * @testdox A report names the construct, so the message can say what to change.
	 */
	public function test_report_describes_the_construct(): void {
		$this->assertStringContainsString(
			'\\-',
			JavaScriptPattern::find_unsupported_construct( '^[0-9]{3}\\-[0-9]{4}$' )
		);

		$this->assertStringContainsString(
			'inline flag',
			JavaScriptPattern::find_unsupported_construct( '(?i)^abc$' )
		);

		$this->assertStringContainsString(
			'possessive quantifier',
			JavaScriptPattern::find_unsupported_construct( '^a++$' )
		);
	}

	/**
	 * @testdox A character class is not read as pattern syntax.
	 */
	public function test_character_class_contents_are_not_read_as_syntax(): void {
		// The `+` and `{` inside the class are literals, not quantifiers.
		$this->assertNull( JavaScriptPattern::find_unsupported_construct( '^[+{]+$' ) );
		// A `-` may be escaped inside a class, even though `\-` throws outside one.
		$this->assertNull( JavaScriptPattern::find_unsupported_construct( '^[\\-+]$' ) );
	}

	/**
	 * @testdox An unterminated class is reported, since `u` mode throws on it.
	 */
	public function test_unterminated_class_is_reported(): void {
		$this->assertNotNull( JavaScriptPattern::find_unsupported_construct( '^[a-z$' ) );
	}
}
