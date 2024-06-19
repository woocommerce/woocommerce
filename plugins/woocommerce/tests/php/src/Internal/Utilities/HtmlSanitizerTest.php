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
	 * @var HtmlSanitizer
	 */
	private $sut;

	/**
	 * Set-up subject under test.
	 */
	public function set_up() {
		$this->sut = wc_get_container()->get( HtmlSanitizer::class );
		parent::set_up();
	}

	/**
	 * @testdox Test inputs and outputs for the HtmlSanitizer's pre-configured TRIMMED_BALANCED_LOW_HTML_NO_LINKS rule.
	 * @dataProvider expectations_for_trimmed_balanced_low_html_no_links_rule
	 *
	 * @param string $test_string The string to be sanitized.
	 * @param string $expected    How we expect the string to look after sanitization.
	 * @param string $explanation Notes about why we expect this/what we're testing.
	 */
	public function test_trimmed_balanced_low_html_sanitizer( string $test_string, string $expected, string $explanation ) {
		$this->assertEquals( $expected, $this->sut->sanitize( $test_string, HtmlSanitizer::LOW_HTML_BALANCED_TAGS_NO_LINKS ), $explanation );
	}

	/**
	 * Describes expectations for the HtmlSanitizer's pre-configured TRIMMED_BALANCED_LOW_HTML_NO_LINKS rule.
	 *
	 * @return string[][]
	 */
	public function expectations_for_trimmed_balanced_low_html_no_links_rule() {
		return array(
			array( 'Simple Text', 'Simple Text', 'Plain text without HTML tags passes through unchanged.' ),
			array( ' Leading/trailing whitespace ', ' Leading/trailing whitespace ', 'Leading and trailing whitespace will be maintained.' ),
			array( '<p>Paragraph</p>', '<p>Paragraph</p>', 'Paragraph tags are allowed' ),
			array( '<div><p><i>Paragraph</i></p></div>', '<p>Paragraph</p>', 'Disallowed tags are removed, allowed tags remain.' ),
			array( '</p> <p><img src="http://bar/icon.png" /> Purchase</p>', ' <p><img src="http://bar/icon.png" /> Purchase</p>', 'Unbalanced tags are removed.' ),
		);
	}

	/**
	 * @testdox Tests that 'bad' string processor callbacks are handled correctly.
	 */
	public function test_handling_of_invalid_processor_callbacks() {
		$this->setExpectedIncorrectUsage( HtmlSanitizer::class . '::apply' );

		$output = $this->sut->sanitize(
			'Test string',
			array(
				'pre_processors' => array(
					'strtoupper',
					'invalid_callback_1',
				),
				'post_processors' => array(
					'invalid_callback_2',
					'strrev',
				),
			)
		);

		$this->assertEquals( '', $output, 'When invalid callbacks are provided, an empty string will be returned.' );
	}

	/**
	 * @testdox An empty ruleset is equivalent to asking that all HTML elements be removed.
	 */
	public function test_no_kses_rules_specified() {
		$this->assertEquals( 'foo', $this->sut->sanitize( '<p>foo</p>', array() ) );
	}

	/**
	 * Describes expected behavior for the sanitizer's styled_post_content method.
	 * @return void
	 */
	public function test_styled_post_content(): void {
		$initial_html = '
			<style> p { color: teal; } </style>
			<script> alert( "I am bad, and I live by my own rules." ); </script>
			<div>
				<p>Waltz, bad nymph, for quick jigs vex.</p>
				<p>Two driven jocks help fax my big quiz.</p>
				<a href="http://five.quacking">Five quacking zephyrs jolt my wax bed.</a>
			</div>
		';

		$expected_output = str_replace( '<script>', '', $initial_html );
		$expected_output = str_replace( '</script>', '', $expected_output );

		$this->assertEquals(
			$this->sut->styled_post_content( $initial_html ),
			$expected_output,
			'We retain the protections offered by wp_kses_post, but also allow the use of `style` elements.'
		);
	}
}
