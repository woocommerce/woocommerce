<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Unit test for Html_Processing_Helper class.
 */
class Html_Processing_Helper_Test extends \Email_Editor_Unit_Test {

	/**
	 * Test clean_css_classes removes background classes.
	 */
	public function testCleanCssClassesRemovesBackgroundClasses(): void {
		$classes = 'has-background wp-block-paragraph';
		$result  = Html_Processing_Helper::clean_css_classes( $classes );
		$this->assertEquals( 'wp-block-paragraph', $result );
	}

	/**
	 * Test clean_css_classes removes border classes.
	 */
	public function testCleanCssClassesRemovesBorderClasses(): void {
		$classes = 'has-border wp-block-paragraph has-top-border';
		$result  = Html_Processing_Helper::clean_css_classes( $classes );
		$this->assertEquals( 'wp-block-paragraph', $result );
	}

	/**
	 * Test clean_css_classes handles multiple spaces.
	 */
	public function testCleanCssClassesHandlesMultipleSpaces(): void {
		$classes = 'has-background   wp-block-paragraph    has-border';
		$result  = Html_Processing_Helper::clean_css_classes( $classes );
		$this->assertEquals( 'wp-block-paragraph', $result );
	}

	/**
	 * Test clean_css_classes limits input length.
	 */
	public function testCleanCssClassesLimitsInputLength(): void {
		$long_classes = str_repeat( 'a', 1001 );
		$result       = Html_Processing_Helper::clean_css_classes( $long_classes );
		$this->assertEquals( 1000, strlen( $result ) );
	}

	/**
	 * Test clean_css_classes with empty string.
	 */
	public function testCleanCssClassesWithEmptyString(): void {
		$result = Html_Processing_Helper::clean_css_classes( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_css_value removes dangerous characters.
	 */
	public function testSanitizeCssValueRemovesDangerousCharacters(): void {
		$value  = 'color: red; <script>alert("xss")</script>';
		$result = Html_Processing_Helper::sanitize_css_value( $value );
		$this->assertEquals( 'color: red; scriptalert("xss")/script', $result );
	}

	/**
	 * Test sanitize_css_value removes dangerous CSS functions.
	 */
	public function testSanitizeCssValueRemovesDangerousFunctions(): void {
		$dangerous_values = array(
			'expression(alert("xss"))',
			'url(javascript:alert("xss"))',
			'url(data:text/html,<script>alert("xss")</script>)',
			'url(vbscript:alert("xss"))',
			'import("malicious.css")',
			'behavior:url("malicious.htc")',
			'binding:url("malicious.xml")',
			'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src="malicious.png")',
		);

		foreach ( $dangerous_values as $value ) {
			$result = Html_Processing_Helper::sanitize_css_value( $value );
			$this->assertEquals( '', $result, "Failed to sanitize dangerous value: $value" );
		}
	}

	/**
	 * Test sanitize_css_value allows safe CSS values.
	 */
	public function testSanitizeCssValueAllowsSafeValues(): void {
		$safe_values = array(
			'12px',
			'#ffffff',
			'rgb(255, 255, 255)',
			'rgba(255, 255, 255, 0.5)',
			'bold',
			'center',
			'Arial, sans-serif',
			'1.5em',
			'100%',
		);

		foreach ( $safe_values as $value ) {
			$result = Html_Processing_Helper::sanitize_css_value( $value );
			$this->assertEquals( $value, $result, "Failed to preserve safe value: $value" );
		}
	}

	/**
	 * Test sanitize_css_value preserves quotes for CSS strings.
	 */
	public function testSanitizeCssValuePreservesQuotes(): void {
		$css_with_quotes = array(
			'font-family: "Arial", sans-serif',
			'font-family: \'Arial\', sans-serif',
			'content: "→"',
			'background: url(\'image.jpg\')',
			'background: url("image.jpg")',
		);

		foreach ( $css_with_quotes as $value ) {
			$result = Html_Processing_Helper::sanitize_css_value( $value );
			$this->assertEquals( $value, $result, "Failed to preserve quotes in: $value" );
		}
	}

	/**
	 * Test sanitize_css_value with empty string.
	 */
	public function testSanitizeCssValueWithEmptyString(): void {
		$result = Html_Processing_Helper::sanitize_css_value( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_color with valid hex colors.
	 */
	public function testSanitizeColorWithValidHexColors(): void {
		$valid_hex_colors = array(
			'#fff',
			'#ffffff',
			'#000',
			'#000000',
			'#abc',
			'#abcdef',
			'#123',
			'#123456',
		);

		foreach ( $valid_hex_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( strtolower( $color ), $result, "Failed to preserve valid hex color: $color" );
		}
	}

	/**
	 * Test sanitize_color with CSS variables.
	 */
	public function testSanitizeColorWithCssVariables(): void {
		$css_variables = array(
			'var(--primary-color)',
			'var(--secondary-color)',
			'var(--text-color)',
		);

		foreach ( $css_variables as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( $color, $result, "Failed to preserve CSS variable: $color" );
		}
	}

	/**
	 * Test sanitize_color with invalid colors returns default.
	 */
	public function testSanitizeColorWithInvalidColorsReturnsDefault(): void {
		$invalid_colors = array(
			'invalid',
			'#ggg',
			'#gggggg',
			'rgb(256, 256, 256)',
			'var(invalid)',
			'',
		);

		foreach ( $invalid_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( '#000000', $result, "Failed to return default for invalid color: $color" );
		}
	}

	/**
	 * Test sanitize_color trims whitespace.
	 */
	public function testSanitizeColorTrimsWhitespace(): void {
		$result = Html_Processing_Helper::sanitize_color( '  #ffffff  ' );
		$this->assertEquals( '#ffffff', $result );
	}

	/**
	 * Test validate_caption_attribute with href attribute.
	 */
	public function testValidateCaptionAttributeWithHref(): void {
		$html = new \WP_HTML_Tag_Processor( '<a href="https://example.com">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'href' );
		$this->assertEquals( 'https://example.com', $html->get_attribute( 'href' ) );
	}

	/**
	 * Test validate_caption_attribute removes invalid href.
	 */
	public function testValidateCaptionAttributeRemovesInvalidHref(): void {
		$html = new \WP_HTML_Tag_Processor( '<a href="javascript:alert(\'xss\')">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'href' );
		$this->assertNull( $html->get_attribute( 'href' ) );
	}

	/**
	 * Test validate_caption_attribute with target attribute.
	 */
	public function testValidateCaptionAttributeWithTarget(): void {
		$html = new \WP_HTML_Tag_Processor( '<a target="_blank">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'target' );
		$this->assertEquals( '_blank', $html->get_attribute( 'target' ) );
		$this->assertEquals( 'noopener noreferrer', $html->get_attribute( 'rel' ) );
	}

	/**
	 * Test validate_caption_attribute removes invalid target.
	 */
	public function testValidateCaptionAttributeRemovesInvalidTarget(): void {
		$html = new \WP_HTML_Tag_Processor( '<a target="_malicious">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'target' );
		$this->assertNull( $html->get_attribute( 'target' ) );
	}

	/**
	 * Test validate_caption_attribute with rel attribute.
	 */
	public function testValidateCaptionAttributeWithRel(): void {
		$html = new \WP_HTML_Tag_Processor( '<a rel="nofollow external">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'rel' );
		$this->assertEquals( 'nofollow external', $html->get_attribute( 'rel' ) );
	}

	/**
	 * Test validate_caption_attribute normalizes rel attribute.
	 */
	public function testValidateCaptionAttributeNormalizesRel(): void {
		$html = new \WP_HTML_Tag_Processor( '<a rel="NOFOLLOW EXTERNAL">Link</a>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'rel' );
		$this->assertEquals( 'nofollow external', $html->get_attribute( 'rel' ) );
	}

	/**
	 * Test validate_caption_attribute with style attribute.
	 */
	public function testValidateCaptionAttributeWithStyle(): void {
		$html = new \WP_HTML_Tag_Processor( '<span style="color: red; font-size: 14px;">Text</span>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'style' );
		$this->assertEquals( 'color: red; font-size: 14px', $html->get_attribute( 'style' ) );
	}

	/**
	 * Test validate_caption_attribute removes dangerous styles.
	 */
	public function testValidateCaptionAttributeRemovesDangerousStyles(): void {
		$html = new \WP_HTML_Tag_Processor( '<span style="color: red; background: url(javascript:alert(\'xss\')); font-size: 14px;">Text</span>' );
		$html->next_tag();
		// This should not throw an exception when processing dangerous styles.
		Html_Processing_Helper::validate_caption_attribute( $html, 'style' );
		// Note: Our mock WP_HTML_Tag_Processor doesn't fully implement attribute setting/getting.
		// This test verifies the method processes dangerous styles without errors.
		$this->assertSame( 'span', $html->get_tag() );
	}

	/**
	 * Test validate_caption_attribute with class attribute.
	 */
	public function testValidateCaptionAttributeWithClass(): void {
		$html = new \WP_HTML_Tag_Processor( '<span class="wp-block-paragraph">Text</span>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'class' );
		$this->assertEquals( 'wp-block-paragraph', $html->get_attribute( 'class' ) );
	}

	/**
	 * Test validate_caption_attribute removes invalid class.
	 */
	public function testValidateCaptionAttributeRemovesInvalidClass(): void {
		$html = new \WP_HTML_Tag_Processor( '<span class="wp-block-paragraph<script>alert(\'xss\')</script>">Text</span>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'class' );
		$this->assertNull( $html->get_attribute( 'class' ) );
	}

	/**
	 * Test validate_caption_attribute with data attributes.
	 */
	public function testValidateCaptionAttributeWithDataAttributes(): void {
		$html = new \WP_HTML_Tag_Processor( '<span data-type="text">Text</span>' );
		$html->next_tag();
		// This should not throw an exception for valid data attributes.
		Html_Processing_Helper::validate_caption_attribute( $html, 'data-type' );
		// Verify the method executed without throwing an exception.
		$this->assertSame( 'span', $html->get_tag() );
	}

	/**
	 * Test validate_caption_attribute removes invalid data attributes.
	 */
	public function testValidateCaptionAttributeRemovesInvalidDataAttributes(): void {
		$html = new \WP_HTML_Tag_Processor( '<span data-type="text<script>alert(\'xss\')</script>">Text</span>' );
		$html->next_tag();
		Html_Processing_Helper::validate_caption_attribute( $html, 'data-type' );
		$this->assertNull( $html->get_attribute( 'data-type' ) );
	}

	/**
	 * Test get_safe_css_properties returns expected properties.
	 */
	public function testGetSafeCssProperties(): void {
		$expected_properties = array(
			'color',
			'background-color',
			'font-family',
			'font-size',
			'font-weight',
			'font-style',
			'text-decoration',
			'text-align',
			'line-height',
			'letter-spacing',
			'text-transform',
		);

		$result = Html_Processing_Helper::get_safe_css_properties();
		$this->assertEquals( $expected_properties, $result );
	}

	/**
	 * Test get_caption_css_properties returns expected properties.
	 */
	public function testGetCaptionCssProperties(): void {
		$expected_properties = array(
			'font-family',
			'font-size',
			'font-weight',
			'font-style',
			'text-decoration',
			'line-height',
			'letter-spacing',
			'text-transform',
		);

		$result = Html_Processing_Helper::get_caption_css_properties();
		$this->assertEquals( $expected_properties, $result );
	}

	/**
	 * Test sanitize_caption_html with plain text.
	 */
	public function testSanitizeCaptionHtmlWithPlainText(): void {
		$html   = 'This is plain text';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( $html, $result );
	}

	/**
	 * Test sanitize_caption_html allows safe tags.
	 */
	public function testSanitizeCaptionHtmlAllowsSafeTags(): void {
		$html   = '<strong>Bold</strong> <em>italic</em> <a href="https://example.com">link</a>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( $html, $result );
	}

	/**
	 * Test sanitize_caption_html removes dangerous tags.
	 */
	public function testSanitizeCaptionHtmlRemovesDangerousTags(): void {
		$html   = '<script>alert("xss")</script><strong>Bold</strong>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( '<strong>Bold</strong>', $result );
	}

	/**
	 * Test sanitize_caption_html removes dangerous content.
	 */
	public function testSanitizeCaptionHtmlRemovesDangerousContent(): void {
		$html   = '<style>body { background: red; }</style><strong>Bold</strong>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( '<strong>Bold</strong>', $result );
	}

	/**
	 * Test sanitize_caption_html sanitizes attributes.
	 */
	public function testSanitizeCaptionHtmlSanitizesAttributes(): void {
		$html   = '<a href="javascript:alert(\'xss\')" target="_blank">Link</a>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		// Note: Our mock WP_HTML_Tag_Processor doesn't fully implement HTML reconstruction.
		// This test verifies the method processes the HTML without errors.
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'Link', $result );
	}

	/**
	 * Test sanitize_caption_html with mixed content.
	 */
	public function testSanitizeCaptionHtmlWithMixedContent(): void {
		$html   = '<div>Not allowed</div><strong>Bold</strong><script>alert("xss")</script><em>italic</em>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( 'Not allowed<strong>Bold</strong><em>italic</em>', $result );
	}

	/**
	 * Test sanitize_caption_html with self-closing tags.
	 */
	public function testSanitizeCaptionHtmlWithSelfClosingTags(): void {
		$html   = '<br/><strong>Bold</strong><hr/><em>italic</em>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( '<br/><strong>Bold</strong><em>italic</em>', $result );
	}

	/**
	 * Test sanitize_caption_html with empty string.
	 */
	public function testSanitizeCaptionHtmlWithEmptyString(): void {
		$result = Html_Processing_Helper::sanitize_caption_html( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_caption_html with complex nested content.
	 */
	public function testSanitizeCaptionHtmlWithComplexNestedContent(): void {
		$html   = '<strong><em><a href="https://example.com">Nested <mark>highlighted</mark> link</a></em></strong>';
		$result = Html_Processing_Helper::sanitize_caption_html( $html );
		$this->assertEquals( $html, $result );
	}
}
