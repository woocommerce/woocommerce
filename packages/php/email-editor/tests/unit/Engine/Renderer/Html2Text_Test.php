<?php
/**
 * HTML to Text Converter unit tests
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

/**
 * Unit tests for Html2Text class
 *
 * These tests are adapted from soundasleep/html2text to ensure
 * no regressions occurred during the integration.
 */
class Html2Text_Test extends \Email_Editor_Unit_Test {

	/**
	 * Test anchor links conversion
	 */
	public function test_anchors(): void {
		$html     = '<a href="http://example.com">Click here</a>';
		$expected = '[Click here](http://example.com)';

		$result = Html2Text::convert( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test anchor links with drop_links option
	 */
	public function test_anchors_drop_links(): void {
		$html     = '<a href="http://example.com">Click here</a>';
		$expected = 'Click here';

		$result = Html2Text::convert( $html, array( 'drop_links' => true ) );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test Html2Text-specific functionality and edge cases
	 *
	 * @dataProvider html_conversion_provider
	 * @param string $html HTML input to convert.
	 * @param string $expected Expected text output.
	 * @param array  $options Optional conversion options.
	 */
	public function test_html_conversion( string $html, string $expected, array $options = array() ): void {
		$result = Html2Text::convert( $html, $options );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for HTML conversion tests
	 * Focus on Html2Text-specific functionality and edge cases
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function html_conversion_provider(): array {
		return array(
			'horizontal_rule'     => array(
				'Before<hr>After',
				"Before\n---------------------------------------------------------------\nAfter",
			),
			'same_url_link'       => array(
				'<a href="http://example.com">http://example.com</a>',
				'http://example.com',
			),
			'mailto_link'         => array(
				'<a href="mailto:test@example.com">test@example.com</a>',
				'test@example.com',
			),
			'image_without_alt'   => array(
				'<img src="image.jpg">',
				'',
			),
			'drop_links_option'   => array(
				'<a href="http://example.com">Click here</a>',
				'Click here',
				array( 'drop_links' => true ),
			),
			'nested_divs_complex' => array(
				'<div>Outer<div>Middle<div>Inner</div></div></div>',
				"Outer\nMiddle\nInner",
			),
		);
	}

	/**
	 * Test non-breaking spaces conversion
	 */
	public function test_non_breaking_spaces(): void {
		$html     = 'Hello&nbsp;world&nbsp;&nbsp;test';
		$expected = 'Hello world test';

		$result = Html2Text::convert( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test zero-width non-joiner removal
	 */
	public function test_zero_width_non_joiners(): void {
		$html     = "Hello\u{200c}world\u{200c}test";
		$expected = 'Helloworldtest';

		$result = Html2Text::convert( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test empty input
	 */
	public function test_empty_input(): void {
		$result = Html2Text::convert( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test HTML entities
	 */
	public function test_html_entities(): void {
		$html     = 'Hello &quot;world&quot; &amp; &lt;test&gt; &#039;quote&#039;';
		$expected = 'Hello "world" & <test> \'quote\'';

		$result = Html2Text::convert( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test ignore_errors option with invalid HTML
	 */
	public function test_ignore_errors_option(): void {
		$html = '<p>Unclosed paragraph<div>Invalid nesting</p></div>';

		// Should not throw exception with ignore_errors = true.
		$result = Html2Text::convert( $html, array( 'ignore_errors' => true ) );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'Unclosed paragraph', $result );
		$this->assertStringContainsString( 'Invalid nesting', $result );
	}

	/**
	 * Test invalid option throws exception
	 */
	public function test_invalid_option_throws_exception(): void {
		$previous_error_log = ini_get( 'error_log' );
		ini_set( 'error_log', '/dev/null' ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- this is to prevent the error from outputting to the screen.

		try {
			$this->expectException( \InvalidArgumentException::class );
			$this->expectExceptionMessage( 'Invalid option provided for html2text conversion.' );

			Html2Text::convert( '<p>Test</p>', array( 'invalid_option' => true ) );
		} finally {
			if ( false !== $previous_error_log ) {
				ini_set( 'error_log', (string) $previous_error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- restore the previous value.
			}
		}
	}

	/**
	 * Test char_set option
	 */
	public function test_char_set_option(): void {
		$html   = '<p>Test with UTF-8: café</p>';
		$result = Html2Text::convert( $html, array( 'char_set' => 'UTF-8' ) );

		$this->assertStringContainsString( 'café', $result );
	}

	/**
	 * Test Microsoft Office document detection and processing
	 */
	public function test_office_document_processing(): void {
		$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"><body><o:p>Office content</o:p><p class="MsoNormal">Normal paragraph</p></body></html>';

		$result = Html2Text::convert( $html );

		// Office namespace tags should be removed.
		$this->assertStringNotContainsString( '<o:p>', $result );
		$this->assertStringContainsString( 'Office content', $result );
		$this->assertStringContainsString( 'Normal paragraph', $result );
	}

	/**
	 * Test Html2Text-specific link processing edge cases
	 */
	public function test_link_edge_cases(): void {
		// Test empty link.
		$html   = '<a href="">Empty link</a>';
		$result = Html2Text::convert( $html );
		$this->assertEquals( 'Empty link', $result );

		// Test link with title attribute.
		$html   = '<a href="http://example.com" title="Example Site">Link</a>';
		$result = Html2Text::convert( $html );
		$this->assertEquals( '[Link](http://example.com)', $result );

		// Test link with name attribute (anchor).
		$html   = '<a name="section1">Section</a>';
		$result = Html2Text::convert( $html );
		$this->assertEquals( '[Section]', $result );
	}

	/**
	 * Test Html2Text text rendering edge cases
	 */
	public function test_text_rendering_edge_cases(): void {
		// Test multiple consecutive nbsp.
		$html   = 'Word&nbsp;&nbsp;&nbsp;spacing';
		$result = Html2Text::convert( $html );
		$this->assertEquals( 'Word spacing', $result );

		// Test mixed whitespace normalization.
		$html   = "Text\t\t\twith\n\n\nvarious   whitespace";
		$result = Html2Text::convert( $html );
		$this->assertEquals( 'Text with various whitespace', $result );
	}

	/**
	 * Test Html2Text character set detection and processing
	 */
	public function test_character_set_processing(): void {
		// Test auto charset detection.
		$html   = '<p>UTF-8 content: àáâãäå</p>';
		$result = Html2Text::convert( $html, array( 'char_set' => 'auto' ) );
		$this->assertStringContainsString( 'àáâãäå', $result );

		// Test explicit UTF-8.
		$result = Html2Text::convert( $html, array( 'char_set' => 'UTF-8' ) );
		$this->assertStringContainsString( 'àáâãäå', $result );
	}

	/**
	 * Test old-style boolean options (backwards compatibility)
	 */
	public function test_old_style_boolean_options(): void {
		$html = '<p>Test</p>';

		// Test old-style ignore_errors = true.
		$result = Html2Text::convert( $html, true );
		$this->assertIsString( $result );

		// Test old-style ignore_errors = false.
		$result = Html2Text::convert( $html, false );
		$this->assertIsString( $result );
	}

	/**
	 * Test default options
	 */
	public function test_default_options(): void {
		$expected_defaults = array(
			'ignore_errors' => false,
			'drop_links'    => false,
			'char_set'      => 'auto',
		);

		$this->assertEquals( $expected_defaults, Html2Text::default_options() );
	}
}
