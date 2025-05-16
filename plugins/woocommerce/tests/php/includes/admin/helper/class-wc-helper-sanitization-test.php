<?php
/**
 * Unit tests for WC_Helper_Sanitization class
 *
 * @package WooCommerce\Tests\Admin\Helper
 */

declare(strict_types=1);

/**
 * Class WC_Helper_Sanitization_Test
 */
class WC_Helper_Sanitization_Test extends WC_Unit_Test_Case {

	/**
	 * Test basic CSS sanitization
	 */
	public function test_basic_css_sanitization() {
		$css       = '.my-class { color: red; font-size: 14px; }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertEquals( $css, $sanitized, 'Basic valid CSS should not be modified' );
	}

	/**
	 * Test @import sanitization
	 */
	public function test_import_sanitization() {
		$css       = '@import url("malicious.css"); .my-class { color: red; }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertStringNotContainsString( '@import', $sanitized, '@import statements should be removed' );
		$this->assertStringContainsString( '.my-class', $sanitized, 'Valid CSS should remain' );
	}

	/**
	 * Test data URI sanitization
	 */
	public function test_data_uri_sanitization() {
		$css       = '.logo { background-image: url(\'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxzY3JpcHQ+YWxlcnQoMSk8L3NjcmlwdD48L3N2Zz4=\'); }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertStringNotContainsString( 'data:', $sanitized, 'data: URIs should be blocked' );
		$this->assertStringContainsString( 'invalid:', $sanitized, 'data: URIs should be replaced with invalid:' );
	}

	/**
	 * Test URL domain whitelisting
	 */
	public function test_url_domain_whitelisting() {
		$allowed_domains = array(
			'https://woocommerce.com/image.jpg',
			'https://wordpress.com/image.jpg',
			'https://cdn.woocommerce.com/image.jpg',
			'https://s0.wp.com/image.jpg',
			'https://woocommerce.test/image.jpg',
		);

		$disallowed_domains = array(
			'https://evil.com/image.jpg',
			'https://woocommerce.evil.com/image.jpg',
			'https://wordpress.org/image.jpg',
		);

		// Test allowed domains.
		foreach ( $allowed_domains as $url ) {
			$css       = ".logo { background-image: url('$url'); }";
			$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

			$this->assertStringContainsString( $url, $sanitized, "Allowed domain $url should remain unchanged" );
		}

		// Test disallowed domains.
		foreach ( $disallowed_domains as $url ) {
			$css       = ".logo { background-image: url('$url'); }";
			$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

			$this->assertStringNotContainsString( $url, $sanitized, "Disallowed domain $url should be blocked" );
			$this->assertStringContainsString(
				'#blocked-url',
				$sanitized,
				'Disallowed URLs should be replaced with #blocked-url'
			);
		}
	}

	/**
	 * Test HTML tag sanitization
	 */
	public function test_html_tag_sanitization() {
		$css       = '.logo { content: "<script>alert(1)</script>"; }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertStringNotContainsString( '<script>', $sanitized, 'HTML script tags should be removed' );
	}

	/**
	 * Test JavaScript event sanitization
	 */
	public function test_javascript_event_sanitization() {
		$css       = '.dangerous { background: expression(alert(1)); color: red; }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertStringNotContainsString(
			'expression(alert(1))',
			$sanitized,
			'JavaScript expressions should be removed'
		);
		$this->assertStringContainsString( 'color: red', $sanitized, 'Valid CSS should remain' );

		// Test javascript: protocol.
		$css       = '.evil { background-image: url(javascript:alert(1)); }';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		$this->assertStringNotContainsString( 'javascript:', $sanitized, 'javascript: protocol should be removed' );
	}

	/**
	 * Test dangerous function sanitization
	 */
	public function test_dangerous_function_sanitization() {
		$dangerous_functions = array( 'behavior', 'eval', 'calc', 'mocha' );

		foreach ( $dangerous_functions as $func ) {
			$css       = ".danger { $func: something; }";
			$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

			$this->assertStringNotContainsString( "$func:", $sanitized, "$func function should be removed" );
			$this->assertStringContainsString( 'blocked', $sanitized, "$func should be replaced with 'blocked'" );
		}
	}

	/**
	 * Test relative URL preservation
	 */
	public function test_relative_url_preservation() {
		$relative_urls = array(
			'/images/logo.png',
			'../assets/icon.svg',
			'./style.css',
		);

		foreach ( $relative_urls as $url ) {
			$css       = ".logo { background-image: url('$url'); }";
			$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

			$this->assertStringContainsString( $url, $sanitized, "Relative URL $url should be preserved" );
		}
	}

	/**
	 * Test size limitation
	 */
	public function test_size_limitation() {
		// Generate CSS that exceeds the size limit.
		$large_css = str_repeat( '.a{color:red;}', 20000 );
		$sanitized = WC_Helper_Sanitization::sanitize_css( $large_css );

		$this->assertLessThanOrEqual( 100000, strlen( $sanitized ), 'CSS should be limited to 100,000 characters' );
	}

	/**
	 * Test edge cases
	 */
	public function test_edge_cases() {
		// Empty input.
		$this->assertEquals( '', WC_Helper_Sanitization::sanitize_css( '' ), 'Empty CSS should remain empty' );

		// Null input (though PHP would typically convert this to empty string).
		$this->assertEquals(
			'',
			WC_Helper_Sanitization::sanitize_css( null ),
			'Null CSS should be handled gracefully'
		);

		// Non-string input.
		$this->assertEquals(
			'',
			WC_Helper_Sanitization::sanitize_css( 123 ),
			'Non-string CSS should be handled gracefully'
		);

		// Input with only dangerous content.
		$css       = '@import url("bad.css");';
		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );
		$this->assertEquals( '', trim( $sanitized ), 'CSS with only dangerous content should be effectively emptied' );
	}

	/**
	 * Test complex CSS
	 */
	public function test_complex_css() {
		$css = '
            @import url("bad.css");
            .logo {
                background-image: url("https://woocommerce.com/logo.png");
                content: "<script>alert(1)</script>";
                behavior: something-bad;
            }
            .evil {
                background: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxzY3JpcHQ+YWxlcnQoMSk8L3NjcmlwdD48L3N2Zz4=");
                color: expression(alert(1));
            }
            .external {
                background: url("https://evil.com/bad.jpg");
            }
            .local {
                background: url("/images/good.jpg");
            }
        ';

		$sanitized = WC_Helper_Sanitization::sanitize_css( $css );

		// Check what should be gone.
		$this->assertStringNotContainsString( '@import', $sanitized );
		$this->assertStringNotContainsString( '<script>', $sanitized );
		$this->assertStringNotContainsString( 'behavior:', $sanitized );
		$this->assertStringNotContainsString( 'data:', $sanitized );
		$this->assertStringNotContainsString( 'expression', $sanitized );
		$this->assertStringNotContainsString( 'evil.com', $sanitized );

		// Check what should remain.
		$this->assertStringContainsString( 'woocommerce.com/logo.png', $sanitized );
		$this->assertStringContainsString( '/images/good.jpg', $sanitized );
	}

	/**
	 * Test asterisk selector preservation
	 */
	public function test_asterisk_selector_preservation() {
		$css_with_asterisks = '
            * { box-sizing: border-box; }
            div > * { margin: 0; }
            div, * { padding: 0; }
            *.special { color: blue; }
            div > *.item { font-weight: bold; }
            p *:not(span) { text-decoration: underline; }
            .modal__main > *, .modal__sidebar > * { margin-bottom: 12px; }
            .selector > * + * { margin-top: 10px; }
            h2 ~ * { font-weight: normal; }
            section > *[hidden] { display: none; }
        ';

		$sanitized = WC_Helper_Sanitization::sanitize_css( $css_with_asterisks );

		// Check that all asterisk patterns are preserved.
		$this->assertStringContainsString( '* {', $sanitized, 'Universal selector should be preserved.' );
		$this->assertStringContainsString( 'div > *', $sanitized, 'Child universal selector should be preserved.' );
		$this->assertStringContainsString( 'div, *', $sanitized, 'Comma-separated universal selector should be preserved.' );
		$this->assertStringContainsString( '*.special', $sanitized, 'Class-qualified universal selector should be preserved.' );
		$this->assertStringContainsString( '*.item', $sanitized, 'Nested class-qualified universal selector should be preserved.' );
		$this->assertStringContainsString( '*:not', $sanitized, 'Universal selector with pseudo-class should be preserved.' );
		$this->assertStringContainsString( '.modal__main > *', $sanitized, 'Child universal selector with class should be preserved.' );
		$this->assertStringContainsString( '.modal__sidebar > *', $sanitized, 'Second child universal selector with class should be preserved.' );
		$this->assertStringContainsString( '* + *', $sanitized, 'Adjacent sibling universal selector should be preserved.' );
		$this->assertStringContainsString( '~ *', $sanitized, 'General sibling universal selector should be preserved.' );
		$this->assertStringContainsString( '*[hidden]', $sanitized, 'Universal selector with attribute should be preserved.' );
	}
}
