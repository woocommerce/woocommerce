<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\MarkdownProductFeed\HtmlToMarkdown;
use WC_Unit_Test_Case;

/**
 * Tests for the HtmlToMarkdown class.
 */
class HtmlToMarkdownTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var HtmlToMarkdown
	 */
	private HtmlToMarkdown $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new HtmlToMarkdown();
	}

	/**
	 * @testdox Should return empty string when given empty input.
	 */
	public function test_converts_empty_string(): void {
		$result = $this->sut->convert( '' );

		$this->assertSame( '', $result, 'Empty HTML input should produce empty string output' );
	}

	/**
	 * @testdox Should convert headings with a level offset of +2.
	 */
	public function test_converts_headings_with_level_offset(): void {
		$result_h1 = $this->sut->convert( '<h1>Title</h1>' );
		$result_h2 = $this->sut->convert( '<h2>Subtitle</h2>' );
		$result_h4 = $this->sut->convert( '<h4>Deep heading</h4>' );

		$this->assertStringContainsString( '### Title', $result_h1, 'H1 should become ### (level + 2)' );
		$this->assertStringContainsString( '#### Subtitle', $result_h2, 'H2 should become #### (level + 2)' );
		$this->assertStringContainsString( '###### Deep heading', $result_h4, 'H4 should become ###### (capped at 6)' );
	}

	/**
	 * @testdox Should convert paragraphs to text with double newlines.
	 */
	public function test_converts_paragraphs(): void {
		$result = $this->sut->convert( '<p>Hello world</p>' );

		$this->assertStringContainsString( 'Hello world', $result, 'Paragraph text should appear in output' );
	}

	/**
	 * @testdox Should convert bold and italic inline formatting.
	 */
	public function test_converts_bold_and_italic(): void {
		$result = $this->sut->convert( '<p><strong>bold</strong> and <em>italic</em></p>' );

		$this->assertStringContainsString( '**bold**', $result, 'Strong tags should produce ** wrapping' );
		$this->assertStringContainsString( '*italic*', $result, 'Em tags should produce * wrapping' );
	}

	/**
	 * @testdox Should convert anchor tags to markdown links.
	 */
	public function test_converts_links(): void {
		$result = $this->sut->convert( '<a href="https://example.com">Click here</a>' );

		$this->assertStringContainsString( '[Click here](https://example.com)', $result, 'Anchor tags should become markdown links' );
	}

	/**
	 * @testdox Should convert image tags to markdown images.
	 */
	public function test_converts_images(): void {
		$result = $this->sut->convert( '<img src="https://example.com/photo.jpg" alt="A photo">' );

		$this->assertStringContainsString( '![A photo](https://example.com/photo.jpg)', $result, 'Img tags should become markdown images' );
	}

	/**
	 * @testdox Should convert unordered lists with dash prefix.
	 */
	public function test_converts_unordered_lists(): void {
		$result = $this->sut->convert( '<ul><li>First</li><li>Second</li></ul>' );

		$this->assertStringContainsString( '- First', $result, 'Unordered list items should have dash prefix' );
		$this->assertStringContainsString( '- Second', $result, 'Second list item should also have dash prefix' );
	}

	/**
	 * @testdox Should convert ordered lists with numbered prefix.
	 */
	public function test_converts_ordered_lists(): void {
		$result = $this->sut->convert( '<ol><li>First</li><li>Second</li></ol>' );

		$this->assertStringContainsString( '1. First', $result, 'First ordered list item should be numbered 1' );
		$this->assertStringContainsString( '2. Second', $result, 'Second ordered list item should be numbered 2' );
	}

	/**
	 * @testdox Should convert nested lists with 2-space indentation.
	 */
	public function test_converts_nested_lists(): void {
		$html   = '<ul><li>Parent</li><ul><li>Child</li></ul></ul>';
		$result = $this->sut->convert( $html );

		$this->assertStringContainsString( '- Parent', $result, 'Parent list item should have no indentation' );
		$this->assertStringContainsString( '  - Child', $result, 'Nested list item should have 2-space indentation' );
	}

	/**
	 * @testdox Should convert blockquotes with angle bracket prefix.
	 */
	public function test_converts_blockquotes(): void {
		$result = $this->sut->convert( '<blockquote>A quote</blockquote>' );

		$this->assertStringContainsString( '> A quote', $result, 'Blockquote content should be prefixed with > ' );
	}

	/**
	 * @testdox Should convert br tags to newlines.
	 */
	public function test_converts_line_breaks(): void {
		$result = $this->sut->convert( '<p>Line one<br>Line two</p>' );

		$this->assertStringContainsString( "Line one\nLine two", $result, 'BR tags should produce newlines' );
	}

	/**
	 * @testdox Should convert HTML tables to markdown tables with separators.
	 */
	public function test_converts_tables(): void {
		$html = '<table>'
			. '<thead><tr><th>Name</th><th>Value</th></tr></thead>'
			. '<tbody><tr><td>Color</td><td>Red</td></tr></tbody>'
			. '</table>';

		$result = $this->sut->convert( $html );

		$this->assertStringContainsString( '| Name | Value |', $result, 'Table header row should appear' );
		$this->assertStringContainsString( '| --- | --- |', $result, 'Table separator row should appear' );
		$this->assertStringContainsString( '| Color | Red |', $result, 'Table data row should appear' );
	}

	/**
	 * @testdox Should strip content inside script and style tags.
	 */
	public function test_skips_script_and_style_tags(): void {
		$html = '<p>Visible</p><script>alert("evil")</script><style>.foo{color:red}</style><p>Also visible</p>';

		$result = $this->sut->convert( $html );

		$this->assertStringContainsString( 'Visible', $result, 'Text outside script/style should remain' );
		$this->assertStringContainsString( 'Also visible', $result, 'Text after script/style should remain' );
		$this->assertStringNotContainsString( 'alert', $result, 'Script content should be stripped' );
		$this->assertStringNotContainsString( '.foo', $result, 'Style content should be stripped' );
	}

	/**
	 * @testdox Should handle malformed HTML without crashing.
	 */
	public function test_handles_malformed_html(): void {
		$html = '<p>Unclosed paragraph<div>Mixed <b>nesting</i></div>';

		$result = $this->sut->convert( $html );

		$this->assertIsString( $result, 'Malformed HTML should still produce a string result' );
		$this->assertStringContainsString( 'Unclosed paragraph', $result, 'Text content should still be extracted from malformed HTML' );
	}

	/**
	 * @testdox Should apply the woocommerce_markdown_feed_html_convert filter.
	 */
	public function test_applies_filter(): void {
		$filter_called = false;

		add_filter(
			'woocommerce_markdown_feed_html_convert',
			function ( $result, $html ) use ( &$filter_called ) {
				unset( $html ); // Avoid parameter not used PHPCS errors.
				$filter_called = true;
				return $result . ' [filtered]';
			},
			10,
			2
		);

		$result = $this->sut->convert( '<p>Test</p>' );

		$this->assertTrue( $filter_called, 'The woocommerce_markdown_feed_html_convert filter should be called' );
		$this->assertStringContainsString( '[filtered]', $result, 'Filter modification should be reflected in output' );

		remove_all_filters( 'woocommerce_markdown_feed_html_convert' );
	}
}
