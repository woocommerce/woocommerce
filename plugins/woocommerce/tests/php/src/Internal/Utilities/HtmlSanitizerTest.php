<?php
/**
 * Tests for the HtmlSanitizer utility.
 */

use Automattic\WooCommerce\Internal\Utilities\HtmlSanitizer;

/**
 * Tests relating to HtmlSanitizer.
 */
class HtmlSanitizerTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Test inputs and outputs for the HtmlSanitizer's pre-configured TRIMMED_BALANCED_LOW_HTML_NO_LINKS rule.
	 * @dataProvider expectations_for_trimmed_balanced_low_html_no_links_rule
	 *
	 * @param string $test_string The string to be sanitized.
	 * @param string $expected    How we expect the string to look after sanitization.
	 * @param string $explanation Notes about why we expect this/what we're testing.
	 */
	public function test_trimmed_balanced_low_html_sanitizer( string $test_string, string $expected, string $explanation ) {
		$sanitizer = new HtmlSanitizer( $test_string );
		$this->assertEquals( $expected, $sanitizer->apply( HtmlSanitizer::TRIMMED_BALANCED_LOW_HTML_NO_LINKS ), $explanation );
	}

	/**
	 * Describes expectations for the HtmlSanitizer's pre-configured TRIMMED_BALANCED_LOW_HTML_NO_LINKS rule.
	 *
	 * @return string[][]
	 */
	public function expectations_for_trimmed_balanced_low_html_no_links_rule() {
		return array(
			array( 'Simple Text', 'Simple Text', 'Plain text without HTML tags passes through unchanged.' ),
			array( ' Leading/trailing whitespace ', 'Leading/trailing whitespace', 'Leading and trailing whitespace will be removed.' ),
			array( '<p>Paragraph</p>', '<p>Paragraph</p>', 'Paragraph tags are allowed' ),
			array( '<div><p><i>Paragraph</i></p></div>', '<p>Paragraph</p>', 'Disallowed tags are removed, allowed tags remain.' ),
			array( '</p> <p><img src="http://bar/icon.png" /> Purchase</p>', '<p><img src="http://bar/icon.png" /> Purchase</p>', 'Unbalanced tags are removed.' ),
		);
	}
}
