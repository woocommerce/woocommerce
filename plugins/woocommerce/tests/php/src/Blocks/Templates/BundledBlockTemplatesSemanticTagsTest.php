<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use WP_UnitTestCase;

/**
 * Regression tests ensuring that bundled WooCommerce block templates declare
 * the correct semantic tagName attribute on header/footer template parts.
 *
 * Without `tagName`, the WordPress block-renderer falls back to `<div>`, which
 * breaks FSE semantics (e.g. sticky-footer CSS, accessibility landmarks).
 *
 * @see https://github.com/woocommerce/woocommerce/issues/32947
 */
class BundledBlockTemplatesSemanticTagsTest extends WP_UnitTestCase {

	/**
	 * Absolute path to the bundled templates root, e.g. ".../plugins/woocommerce/templates".
	 *
	 * @var string
	 */
	private $templates_root;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->templates_root = dirname( __DIR__, 5 ) . '/templates';
	}

	/**
	 * Data provider listing all bundled top-level block templates that render
	 * a header/footer template part.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function bundled_template_provider(): array {
		return array(
			'archive-product'                       => array( 'templates/archive-product.html' ),
			'single-product'                        => array( 'templates/single-product.html' ),
			'product-search-results'                => array( 'templates/product-search-results.html' ),
			'taxonomy-product_attribute'            => array( 'templates/taxonomy-product_attribute.html' ),
			'order-confirmation'                    => array( 'templates/order-confirmation.html' ),
			'page-cart'                             => array( 'templates/page-cart.html' ),
			'blockified/archive-product'            => array( 'templates/blockified/archive-product.html' ),
			'blockified/single-product'             => array( 'templates/blockified/single-product.html' ),
			'blockified/product-search-results'     => array( 'templates/blockified/product-search-results.html' ),
			'blockified/taxonomy-product_attribute' => array( 'templates/blockified/taxonomy-product_attribute.html' ),
			'blockified/order-confirmation'         => array( 'templates/blockified/order-confirmation.html' ),
			'blockified/page-cart'                  => array( 'templates/blockified/page-cart.html' ),
		);
	}

	/**
	 * @testdox Bundled block templates should declare tagName on header and footer template parts.
	 * @dataProvider bundled_template_provider
	 */
	public function test_header_and_footer_template_parts_declare_tag_name( string $relative_path ): void {
		$absolute_path = $this->templates_root . '/' . $relative_path;

		$this->assertFileExists( $absolute_path, "Expected bundled template at {$relative_path}" );

		$content = file_get_contents( $absolute_path );
		$this->assertIsString( $content );

		$this->assert_template_part_has_tag_name( $content, 'header', $relative_path );
		$this->assert_template_part_has_tag_name( $content, 'footer', $relative_path );
	}

	/**
	 * Assert that every `wp:template-part` block referencing the given header/footer
	 * slug declares the matching `tagName` attribute.
	 *
	 * Only slugs that exactly match `header` or `footer` are checked; templates such
	 * as `checkout-header` already pin `tagName` explicitly via their own slugs.
	 */
	private function assert_template_part_has_tag_name( string $content, string $slug, string $relative_path ): void {
		if ( ! preg_match_all( '/<!--\s*wp:template-part\s*(\{[^}]*\})\s*\/-->/', $content, $matches ) ) {
			return;
		}

		foreach ( $matches[1] as $attrs_json ) {
			$attrs = json_decode( $attrs_json, true );
			if ( ! is_array( $attrs ) || ( $attrs['slug'] ?? '' ) !== $slug ) {
				continue;
			}

			$this->assertSame(
				$slug,
				$attrs['tagName'] ?? null,
				sprintf(
					'Template "%s" must declare tagName="%s" on the %s template-part to preserve FSE semantic markup.',
					$relative_path,
					$slug,
					$slug
				)
			);
		}
	}
}
