<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\Utils;
use WC_Unit_Test_Case;

/**
 * Tests for the Blocks Utils class.
 */
class UtilsTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * Make wp_scripts() return a stand-in exposing the given base and content URLs.
	 *
	 * @param string $base_url    The value for WP_Scripts::$base_url.
	 * @param string $content_url The value for WP_Scripts::$content_url.
	 */
	private function mock_wp_scripts( string $base_url, string $content_url ): void {
		$wp_scripts = (object) array(
			'base_url'    => $base_url,
			'content_url' => $content_url,
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_scripts' => function () use ( $wp_scripts ) {
					return $wp_scripts;
				},
			)
		);
	}

	/**
	 * @testdox get_absolute_script_url() resolves a script src the same way WP_Scripts::do_item() would.
	 * @dataProvider provider_script_urls
	 *
	 * @param string $content_url The WP_Scripts content URL.
	 * @param string $src         The script src to resolve.
	 * @param string $expected    The expected resolved URL.
	 */
	public function test_get_absolute_script_url( string $content_url, string $src, string $expected ): void {
		$this->mock_wp_scripts( 'https://example.com/wp', $content_url );

		$this->assertSame( $expected, Utils::get_absolute_script_url( $src ) );
	}

	/**
	 * Data provider of script srcs and their expected resolved URLs (base URL = https://example.com/wp).
	 *
	 * @return array<array<string>>
	 */
	public function provider_script_urls(): array {
		$abs_content = 'https://example.com/wp-content';

		return array(
			// Absolute https URL is unchanged.
			array( $abs_content, 'https://cdn.test/a.js', 'https://cdn.test/a.js' ),
			// Absolute http URL is unchanged.
			array( $abs_content, 'http://cdn.test/a.js', 'http://cdn.test/a.js' ),
			// Protocol-relative URL is unchanged.
			array( $abs_content, '//cdn.test/a.js', '//cdn.test/a.js' ),
			// Root-relative path gets the base URL prepended.
			array( $abs_content, '/wp-includes/a.js', 'https://example.com/wp/wp-includes/a.js' ),
			// Relative path gets the base URL prepended.
			array( $abs_content, 'wp-includes/a.js', 'https://example.com/wpwp-includes/a.js' ),
			// Absolute content URL is unchanged.
			array( $abs_content, 'https://example.com/wp-content/x.js', 'https://example.com/wp-content/x.js' ),
			// An empty src is a degenerate input (callers normally guard against it) and yields the base URL.
			array( $abs_content, '', 'https://example.com/wp' ),
			// Empty content URL still prepends the base URL.
			array( '', '/wp-includes/a.js', 'https://example.com/wp/wp-includes/a.js' ),
			// With a relative content URL, a content-dir-prefixed src is left untouched (the content_url short-circuit).
			array( '/wp-content', '/wp-content/plugins/x.js', '/wp-content/plugins/x.js' ),
			// A name-prefix sibling ("/wp-contentX") also matches the content_url prefix and is left untouched,
			// mirroring WP_Scripts::do_item()'s str_starts_with() check (a plain prefix, with no segment boundary).
			array( '/wp-content', '/wp-contentX/plugin.js', '/wp-contentX/plugin.js' ),
			// A non-content relative src still gets the base URL prepended.
			array( '/wp-content', '/wp-includes/x.js', 'https://example.com/wp/wp-includes/x.js' ),
		);
	}

	/**
	 * @testdox get_absolute_script_url() reproduces the behavior of the code it replaced.
	 * @dataProvider provider_legacy_srcs
	 *
	 * @param string $content_url                  The WP_Scripts content URL.
	 * @param string $src                          The script src to resolve.
	 * @param bool   $diverges_from_simple_variant Whether the result is expected to diverge from the simpler MiniCart/DependencyDetection variant.
	 */
	public function test_get_absolute_script_url_matches_replaced_code( string $content_url, string $src, bool $diverges_from_simple_variant ): void {
		$base_url = 'https://example.com/wp';
		$this->mock_wp_scripts( $base_url, $content_url );

		$result = Utils::get_absolute_script_url( $src );

		// Reference implementation of the removed AssetsController::get_absolute_url(), which is identical to
		// WP_Scripts::do_item(). The helper must reproduce it for every input.
		$assets_controller_legacy = $src;
		if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $content_url && 0 === strpos( $src, $content_url ) ) ) {
			$assets_controller_legacy = $base_url . $src;
		}
		$this->assertSame( $assets_controller_legacy, $result, 'Must match the replaced AssetsController::get_absolute_url() logic.' );

		// Reference for the simpler MiniCart/DependencyDetection variant (no content_url short-circuit; their
		// base URL, site_url(), equals WP_Scripts::$base_url). The helper matches it everywhere except for a
		// content-dir-prefixed src under a relative content URL, where it intentionally skips the prepend.
		$simple_variant = preg_match( '|^(https?:)?//|', $src ) ? $src : $base_url . $src;
		if ( $diverges_from_simple_variant ) {
			$this->assertNotSame( $simple_variant, $result, 'Should diverge from the simpler variant only for content-dir-prefixed srcs.' );
		} else {
			$this->assertSame( $simple_variant, $result, 'Must match the replaced MiniCart/DependencyDetection logic.' );
		}
	}

	/**
	 * Data provider of srcs covering every branch of the replaced logic.
	 *
	 * The third value is whether the result is expected to diverge from the simpler variant.
	 *
	 * @return array<array{string, string, bool}>
	 */
	public function provider_legacy_srcs(): array {
		return array(
			// Absolute https URL.
			array( 'https://example.com/wp-content', 'https://cdn.test/a.js', false ),
			// Protocol-relative URL.
			array( 'https://example.com/wp-content', '//cdn.test/a.js', false ),
			// Root-relative path.
			array( 'https://example.com/wp-content', '/wp-includes/a.js', false ),
			// Relative path.
			array( 'https://example.com/wp-content', 'wp-includes/a.js', false ),
			// Content-dir-prefixed src under an absolute content URL (caught by the scheme check, not content_url).
			array( 'https://example.com/wp-content', 'https://example.com/wp-content/x.js', false ),
			// Content-dir-prefixed src under a relative content URL: the one case that diverges from the simpler variant.
			array( '/wp-content', '/wp-content/plugins/x.js', true ),
			// A name-prefix sibling ("/wp-contentX") also matches the content_url prefix (mirroring core), so it is
			// left untouched and likewise diverges from the simpler base-URL-prepending variant.
			array( '/wp-content', '/wp-contentX/plugin.js', true ),
			// Non-content src under a relative content URL.
			array( '/wp-content', '/wp-includes/x.js', false ),
		);
	}
}
