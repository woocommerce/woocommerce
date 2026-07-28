<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Utils;

use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;

/**
 * Integration test for Html_Processing_Helper.
 *
 * These run against the real WordPress WP_HTML_Tag_Processor, which the unit
 * tests only stub, so caption sanitization is exercised end to end.
 */
class Html_Processing_Helper_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Dangerous caption markup must be neutralized against the real HTML API.
	 *
	 * @dataProvider caption_xss_provider
	 * @param string $input        Raw caption HTML.
	 * @param string $must_not_contain Substring that must not survive.
	 */
	public function test_sanitize_caption_html_strips_dangerous_markup( string $input, string $must_not_contain ): void {
		$result = Html_Processing_Helper::sanitize_caption_html( $input );
		$this->assertStringNotContainsStringIgnoringCase( $must_not_contain, $result );
	}

	/**
	 * Data provider for dangerous caption markup.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function caption_xss_provider(): array {
		return array(
			'javascript href'       => array( '<a href="javascript:alert(1)">x</a>', 'javascript:' ),
			'event handler on link' => array( '<a href="https://x.test" onmouseover="alert(1)">x</a>', 'onmouseover' ),
			'event handler on span' => array( '<span onclick="alert(1)">x</span>', 'onclick' ),
			'void img with onerror' => array( '<img src=x onerror=alert(1)>', 'onerror' ),
			'void svg with onload'  => array( '<svg onload=alert(1)>', 'onload' ),
			'script element'        => array( '<script>alert(1)</script>caption', 'alert' ),
			// Custom-element names that start with an allowed tag name must not
			// slip through the tag-strip pass (e.g. "a-b" masquerading as "a").
			'hyphenated custom tag' => array( '<a-b onmouseover="alert(1)">x</a-b>', 'onmouseover' ),
			'colon custom tag'      => array( '<a:b onmouseover="alert(1)">x</a:b>', 'onmouseover' ),
			'span-prefixed tag'     => array( '<span-x onclick="alert(1)">x</span-x>', 'onclick' ),
		);
	}

	/**
	 * Safe caption markup must be preserved.
	 */
	public function test_sanitize_caption_html_preserves_safe_markup(): void {
		$this->assertSame(
			'My <strong>video</strong> caption',
			Html_Processing_Helper::sanitize_caption_html( 'My <strong>video</strong> caption' )
		);

		// A valid link is preserved; target="_blank" gains rel="noopener noreferrer".
		$linked = Html_Processing_Helper::sanitize_caption_html( '<a href="https://example.com" target="_blank">link</a>' );
		$this->assertStringContainsString( 'href="https://example.com"', $linked );
		$this->assertStringContainsString( 'target="_blank"', $linked );
		$this->assertStringContainsString( 'noopener', $linked );
	}
}
