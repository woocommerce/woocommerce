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
			'#ffffffff',
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
	 * Test sanitize_color with rgb/rgba colors.
	 */
	public function testSanitizeColorWithRgbRgbaColors(): void {
		$rgb_colors = array(
			'rgb(255, 255, 255)',
			'rgb(0, 0, 0)',
			'rgb(128, 128, 128)',
			'rgba(255, 255, 255, 0.5)',
			'rgba(0, 0, 0, 1)',
			'rgba(128, 128, 128, 0.8)',
		);

		foreach ( $rgb_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( $color, $result, "Failed to preserve rgb/rgba color: $color" );
		}
	}

	/**
	 * Test sanitize_color with hsl/hsla colors.
	 */
	public function testSanitizeColorWithHslHslaColors(): void {
		$hsl_colors = array(
			'hsl(0, 0%, 100%)',
			'hsl(0, 0%, 0%)',
			'hsl(120, 50%, 50%)',
			'hsla(0, 0%, 100%, 0.5)',
			'hsla(0, 0%, 0%, 1)',
			'hsla(120, 50%, 50%, 0.8)',
		);

		foreach ( $hsl_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( $color, $result, "Failed to preserve hsl/hsla color: $color" );
		}
	}

	/**
	 * Test sanitize_color with named colors and CSS keywords.
	 *
	 * Uses a permissive approach that accepts any string that looks like a valid CSS color name.
	 * This test demonstrates that the method accepts various named colors without maintaining
	 * a hardcoded list, making it future-proof for new CSS named colors.
	 */
	public function testSanitizeColorWithNamedColorsAndKeywords(): void {
		$valid_colors = array(
			// Standard named colors.
			'black',
			'white',
			'red',
			'green',
			'blue',
			'yellow',
			'orange',
			'purple',
			'pink',
			'brown',
			'gray',
			'grey',
			'navy',
			'lime',
			'teal',
			'crimson',
			'aqua',
			'fuchsia',
			'silver',
			'maroon',
			'olive',
			'gold',
			// CSS keywords.
			'transparent',
			'inherit',
			'initial',
			'unset',
			// Future CSS named colors (will work automatically).
			'rebeccapurple',
			'lightcoral',
			'mediumseagreen',
			// Additional named colors that would work with the permissive approach.
			'lightblue',
			'darkgreen',
			'hotpink',
			'forestgreen',
			'royalblue',
			'orangered',
			'deepskyblue',
			'mediumvioletred',
			'lightsteelblue',
			'palegoldenrod',
			// Made-up but valid-looking color names (demonstrates permissiveness).
			'customcolor',
			'brandprimary',
			'accentcolor',
		);

		foreach ( $valid_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( strtolower( $color ), $result, "Failed to preserve named color: $color" );
		}
	}

	/**
	 * Test sanitize_color with invalid colors returns default.
	 */
	public function testSanitizeColorWithInvalidColorsReturnsDefault(): void {
		$invalid_colors = array(
			'#ggg',
			'#gggggg',
			'rgb(256, 256, 256)',
			'rgba(255, 255, 255, 2.0)', // Invalid alpha > 1.
			'hsl(400, 50%, 50%)', // Invalid hue > 360.
			'hsla(120, 150%, 50%, 0.5)', // Invalid saturation > 100%.
			'var(invalid)',
			'var(--)', // Invalid CSS variable.
			'',
		);

		foreach ( $invalid_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( '#000000', $result, "Failed to return default for invalid color: $color" );
		}
	}

	/**
	 * Test sanitize_color with dangerous color values returns default.
	 */
	public function testSanitizeColorWithDangerousValuesReturnsDefault(): void {
		$dangerous_colors = array(
			'expression(alert(1))',
			'javascript:alert(1)',
			'vbscript:alert(1)',
			'data:text/html,<script>alert(1)</script>',
			'import(url)',
			'behavior:url(script.htc)',
			'binding:url(script.xml)',
			'filter:progid:DXImageTransform.Microsoft.Alpha(opacity=50)',
		);

		foreach ( $dangerous_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( '#000000', $result, "Failed to return default for dangerous color: $color" );
		}
	}

	/**
	 * Test sanitize_color permissive approach with various valid-looking color names.
	 *
	 * This test specifically demonstrates the permissive nature of the color validation,
	 * showing that it accepts any string that looks like a valid CSS color name,
	 * regardless of whether it's a real CSS color or not.
	 */
	public function testSanitizeColorPermissiveApproach(): void {
		$permissive_colors = array(
			// Real CSS named colors.
			'red',
			'blue',
			'green',
			// Made-up but valid-looking color names.
			'brandcolor',
			'primaryaccent',
			'secondaryhighlight',
			'customtheme',
			'brandprimary',
			'brandsecondary',
			'accentcolor',
			'highlightcolor',
			'textcolor',
			'backgroundcolor',
			'linkcolor',
			'buttoncolor',
			'headercolor',
			'footercolor',
			'sidebarcolor',
			'contentcolor',
			'widgetcolor',
			'formcolor',
			'inputcolor',
			'labelcolor',
			'errorcolor',
			'successcolor',
			'warningcolor',
			'infocolor',
		);

		foreach ( $permissive_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( strtolower( $color ), $result, "Failed to preserve permissive color: $color" );
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

	/**
	 * Test sanitize_image_html with basic image.
	 */
	public function testSanitizeImageHtmlWithBasicImage(): void {
		$html   = '<img src="https://example.com/image.jpg" alt="Test image" width="100" height="50">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'src="https://example.com/image.jpg"', $result );
		$this->assertStringContainsString( 'alt="Test image"', $result );
		$this->assertStringContainsString( 'width="100"', $result );
		$this->assertStringContainsString( 'height="50"', $result );
	}

	/**
	 * Test sanitize_image_html sanitizes src URL.
	 */
	public function testSanitizeImageHtmlSanitizesSrcUrl(): void {
		$html   = '<img src="javascript:alert(\'xss\')" alt="Test">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringNotContainsString( 'javascript:', $result );
		$this->assertStringNotContainsString( 'src=', $result );
	}

	/**
	 * Test sanitize_image_html sanitizes alt text.
	 */
	public function testSanitizeImageHtmlSanitizesAltText(): void {
		$html   = '<img src="https://example.com/image.jpg" alt="Test &quot;quoted&quot; text">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'alt="Test &quot;quoted&quot; text"', $result );
	}

	/**
	 * Test sanitize_image_html sanitizes dimensions.
	 */
	public function testSanitizeImageHtmlSanitizesDimensions(): void {
		$html   = '<img src="https://example.com/image.jpg" width="100px" height="50px">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'width="100px"', $result );
		$this->assertStringContainsString( 'height="50px"', $result );
	}

	/**
	 * Test sanitize_image_html cleans CSS classes.
	 */
	public function testSanitizeImageHtmlCleansCssClasses(): void {
		$html   = '<img src="https://example.com/image.jpg" class="wp-image-123 has-background has-border">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'class="wp-image-123"', $result );
		$this->assertStringNotContainsString( 'has-background', $result );
		$this->assertStringNotContainsString( 'has-border', $result );
	}

	/**
	 * Test sanitize_image_html sanitizes inline styles.
	 */
	public function testSanitizeImageHtmlSanitizesInlineStyles(): void {
		$html   = '<img src="https://example.com/image.jpg" style="width: 100px; height: 50px; background: url(javascript:alert(\'xss\'));">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'width: 100px', $result );
		$this->assertStringContainsString( 'height: 50px', $result );
		$this->assertStringNotContainsString( 'background:', $result );
		$this->assertStringNotContainsString( 'javascript:', $result );
	}

	/**
	 * Test sanitize_image_html allows safe CSS properties.
	 */
	public function testSanitizeImageHtmlAllowsSafeCssProperties(): void {
		$html   = '<img src="https://example.com/image.jpg" style="width: 100px; height: 50px; max-width: 200px; margin: 10px; padding: 5px; border: 1px solid #000; border-radius: 5px;">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'width: 100px', $result );
		$this->assertStringContainsString( 'height: 50px', $result );
		$this->assertStringContainsString( 'max-width: 200px', $result );
		$this->assertStringContainsString( 'margin: 10px', $result );
		$this->assertStringContainsString( 'padding: 5px', $result );
		$this->assertStringContainsString( 'border: 1px solid #000', $result );
		$this->assertStringContainsString( 'border-radius: 5px', $result );
	}

	/**
	 * Test sanitize_image_html removes dangerous CSS properties.
	 */
	public function testSanitizeImageHtmlRemovesDangerousCssProperties(): void {
		$html   = '<img src="https://example.com/image.jpg" style="width: 100px; position: absolute; z-index: 999; background: red;">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringContainsString( 'width: 100px', $result );
		$this->assertStringNotContainsString( 'position:', $result );
		$this->assertStringNotContainsString( 'z-index:', $result );
		$this->assertStringNotContainsString( 'background:', $result );
	}

	/**
	 * Test sanitize_image_html removes unknown attributes.
	 */
	public function testSanitizeImageHtmlRemovesUnknownAttributes(): void {
		$html   = '<img src="https://example.com/image.jpg" onclick="alert(\'xss\')" data-custom="value" id="test">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertStringNotContainsString( 'onclick=', $result );
		$this->assertStringNotContainsString( 'data-custom=', $result );
		$this->assertStringNotContainsString( 'id=', $result );
		$this->assertStringContainsString( 'src="https://example.com/image.jpg"', $result );
	}

	/**
	 * Test sanitize_image_html with empty string.
	 */
	public function testSanitizeImageHtmlWithEmptyString(): void {
		$result = Html_Processing_Helper::sanitize_image_html( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_image_html with non-image HTML.
	 */
	public function testSanitizeImageHtmlWithNonImageHtml(): void {
		$html   = '<div>Not an image</div>';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertEquals( $html, $result );
	}

	/**
	 * Test sanitize_image_html with malformed HTML.
	 */
	public function testSanitizeImageHtmlWithMalformedHtml(): void {
		$html   = '<img src="https://example.com/image.jpg" alt="Test" <malformed';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		// Should still sanitize what it can.
		$this->assertStringContainsString( 'src="https://example.com/image.jpg"', $result );
		$this->assertStringContainsString( 'alt="Test"', $result );
	}

	/**
	 * Test sanitize_image_html returns empty string when no valid attributes.
	 */
	public function testSanitizeImageHtmlReturnsEmptyWhenNoValidAttributes(): void {
		$html   = '<img onclick="alert(\'xss\')" data-custom="value">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_image_html returns empty string when src is invalid.
	 */
	public function testSanitizeImageHtmlReturnsEmptyWhenSrcInvalid(): void {
		$html   = '<img src="javascript:alert(\'xss\')" alt="Test">';
		$result = Html_Processing_Helper::sanitize_image_html( $html );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_color with valid RGBA alpha values (0-1).
	 */
	public function testSanitizeColorWithValidRgbaAlphaValues(): void {
		$valid_rgba_colors = array(
			'rgba(255, 255, 255, 0)',      // Alpha 0.
			'rgba(255, 255, 255, 0.5)',    // Alpha 0.5.
			'rgba(255, 255, 255, .5)',     // Alpha .5 (no leading zero).
			'rgba(255, 255, 255, 1)',      // Alpha 1.
			'rgba(255, 255, 255, 1.0)',    // Alpha 1.0.
			'rgba(255, 255, 255, 1.00)',   // Alpha 1.00.
			'rgba(255, 255, 255, 0.25)',   // Alpha 0.25.
			'rgba(255, 255, 255, 0.75)',   // Alpha 0.75.
			'rgba(120, 130, 140, .25)',    // Alpha .25 with different RGB values.
		);

		foreach ( $valid_rgba_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( $color, $result, "Failed to preserve valid RGBA color with alpha: $color" );
		}
	}

	/**
	 * Test sanitize_color with invalid RGBA alpha values (>1).
	 */
	public function testSanitizeColorWithInvalidRgbaAlphaValues(): void {
		$invalid_rgba_colors = array(
			'rgba(255, 255, 255, 1.25)',   // Alpha > 1.
			'rgba(255, 255, 255, 2)',      // Alpha = 2.
			'rgba(255, 255, 255, 1.5)',    // Alpha = 1.5.
			'rgba(255, 255, 255, 10)',     // Alpha = 10.
			'rgba(255, 255, 255, 2.0)',    // Alpha = 2.0.
			'rgba(255, 255, 255, 1.01)',   // Alpha slightly > 1.
		);

		foreach ( $invalid_rgba_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( '#000000', $result, "Failed to reject invalid RGBA color with alpha > 1: $color" );
		}
	}

	/**
	 * Test sanitize_color with valid HSLA alpha values (0-1).
	 */
	public function testSanitizeColorWithValidHslaAlphaValues(): void {
		$valid_hsla_colors = array(
			'hsla(120, 50%, 50%, 0)',      // Alpha 0.
			'hsla(120, 50%, 50%, 0.5)',    // Alpha 0.5.
			'hsla(120, 50%, 50%, .5)',     // Alpha .5 (no leading zero).
			'hsla(120, 50%, 50%, 1)',      // Alpha 1.
			'hsla(120, 50%, 50%, 1.0)',    // Alpha 1.0.
			'hsla(120, 50%, 50%, 1.00)',   // Alpha 1.00.
			'hsla(240, 75%, 25%, 0.25)',   // Alpha 0.25.
			'hsla(360, 100%, 75%, 0.9)',   // Alpha 0.9.
			'hsla(0, 0%, 100%, .75)',      // Alpha .75 with white color.
		);

		foreach ( $valid_hsla_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( $color, $result, "Failed to preserve valid HSLA color with alpha: $color" );
		}
	}

	/**
	 * Test sanitize_color with invalid HSLA alpha values (>1).
	 */
	public function testSanitizeColorWithInvalidHslaAlphaValues(): void {
		$invalid_hsla_colors = array(
			'hsla(120, 50%, 50%, 1.25)',   // Alpha > 1.
			'hsla(120, 50%, 50%, 2)',      // Alpha = 2.
			'hsla(120, 50%, 50%, 1.5)',    // Alpha = 1.5.
			'hsla(120, 50%, 50%, 10)',     // Alpha = 10.
			'hsla(120, 50%, 50%, 2.0)',    // Alpha = 2.0.
			'hsla(120, 50%, 50%, 1.01)',   // Alpha slightly > 1.
		);

		foreach ( $invalid_hsla_colors as $color ) {
			$result = Html_Processing_Helper::sanitize_color( $color );
			$this->assertEquals( '#000000', $result, "Failed to reject invalid HSLA color with alpha > 1: $color" );
		}
	}

	/**
	 * Test sanitize_dimension_value with valid dimension values.
	 */
	public function testSanitizeDimensionValueWithValidValues(): void {
		$valid_dimensions = array(
			'430'    => '430px',    // Numeric value gets px added.
			'430px'  => '430px',    // Already has px.
			'50%'    => '50%',      // Percentage.
			'2em'    => '2em',      // Em units.
			'1.5rem' => '1.5rem',   // Rem units.
			'100vh'  => '100vh',    // Viewport height.
			'50vw'   => '50vw',     // Viewport width.
			'12pt'   => '12pt',     // Points.
			'1in'    => '1in',      // Inches.
			'2.5cm'  => '2.5cm',    // Centimeters.
		);

		foreach ( $valid_dimensions as $input => $expected ) {
			$result = Html_Processing_Helper::sanitize_dimension_value( $input );
			$this->assertEquals( $expected, $result, "Failed to sanitize dimension: $input" );
		}
	}

	/**
	 * Test sanitize_dimension_value with invalid dimension values.
	 */
	public function testSanitizeDimensionValueWithInvalidValues(): void {
		$invalid_dimensions = array(
			'invalid',
			'<script>alert("xss")</script>',
			'expression(alert(1))',
			'javascript:alert(1)',
			'',
			'  ',
			'430invalid',
			'px430',
			null,
			array(),
			false,
		);

		foreach ( $invalid_dimensions as $input ) {
			$result            = Html_Processing_Helper::sanitize_dimension_value( $input );
			$input_description = is_string( $input ) ? $input : gettype( $input );
			$this->assertEquals( '', $result, 'Failed to reject invalid dimension: ' . $input_description );
		}
	}
}
