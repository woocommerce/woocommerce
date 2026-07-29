<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_Theme_JSON_Data;
use WP_UnitTestCase;

/**
 * Tests for the Breadcrumbs block type.
 */
class BreadcrumbsTest extends WP_UnitTestCase {

	/**
	 * Active theme before each test, restored in tearDown.
	 *
	 * @var string
	 */
	private string $original_theme;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_theme = get_stylesheet();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wp_theme_json_data_user' );
		switch_theme( $this->original_theme );
		parent::tearDown();
	}

	/**
	 * Render the Breadcrumbs block via do_blocks().
	 *
	 * @param string $attrs_json JSON object string for block attributes, e.g. '{"fontSize":"large"}'.
	 * @return string Rendered markup.
	 */
	private function render_breadcrumbs( string $attrs_json = '' ): string {
		$attrs = '' !== $attrs_json ? ' ' . $attrs_json : '';
		return do_blocks( "<!-- wp:woocommerce/breadcrumbs{$attrs} /-->" );
	}

	/**
	 * Switch to a block theme for global styles resolution.
	 */
	private function switch_to_block_theme(): void {
		switch_theme( 'twentytwentyfour' );
	}

	/**
	 * Inject a theme.json font size for the breadcrumbs block via user global styles.
	 *
	 * @param string $font_size CSS font-size value, e.g. var(--wp--preset--font-size--large).
	 */
	private function set_breadcrumbs_theme_font_size( string $font_size ): void {
		add_filter(
			'wp_theme_json_data_user',
			function ( $theme_json ) use ( $font_size ) {
				$data = $theme_json->get_data();
				$data['styles']['blocks']['woocommerce/breadcrumbs']['typography']['fontSize'] = $font_size;
				return new WP_Theme_JSON_Data( $data, 'user' );
			}
		);
	}

	/**
	 * @testdox Should render the default wrapper with base and font-size classes.
	 */
	public function test_default_wrapper_has_base_and_font_size_classes(): void {
		$markup = $this->render_breadcrumbs();

		$this->assertStringContainsString( 'wc-block-breadcrumbs', $markup, 'Wrapper should include the block class.' );
		$this->assertStringContainsString( 'has-small-font-size', $markup, 'Default font size from block.json should be small.' );
		$this->assertStringContainsString( 'alignwide', $markup, 'Default align from block.json should be wide.' );
	}

	/**
	 * @testdox Should apply preset large font-size class and remove the default small class.
	 */
	public function test_preset_large_font_size(): void {
		$markup = $this->render_breadcrumbs( '{"fontSize":"large"}' );

		$this->assertStringContainsString( 'has-large-font-size', $markup, 'Preset large font size class should be present.' );
		$this->assertStringNotContainsString( 'has-small-font-size', $markup, 'Default small font size class should be removed for large preset.' );
	}

	/**
	 * @testdox Should keep has-small-font-size when preset font size is small.
	 */
	public function test_preset_small_font_size(): void {
		$markup = $this->render_breadcrumbs( '{"fontSize":"small"}' );

		$this->assertStringContainsString( 'has-small-font-size', $markup, 'Preset small font size class should be present.' );
	}

	/**
	 * @testdox Should apply custom typography font-size inline style and remove the default small class.
	 */
	public function test_custom_typography_font_size(): void {
		$markup = $this->render_breadcrumbs( '{"style":{"typography":{"fontSize":"2rem"}}}' );

		// Fluid typography may render the size as clamp(..., 2rem), so only
		// require that 2rem appears within the font-size declaration itself.
		$this->assertMatchesRegularExpression( '/font-size:[^;"]*2rem/', $markup, 'Custom font size should be applied as inline style.' );
		$this->assertStringNotContainsString( 'has-small-font-size', $markup, 'Default small font size class should be removed for custom typography.' );
	}

	/**
	 * @testdox Should apply other styling attributes.
	 */
	public function test_align_full(): void {
		$markup = $this->render_breadcrumbs( '{"align":"full","textColor":"contrast"}' );

		$this->assertStringContainsString( 'alignfull', $markup, 'Full alignment class should be present.' );
		$this->assertStringContainsString( 'has-contrast-color', $markup, 'Text color class from palette slug should be present.' );
	}

	/**
	 * @testdox Should remove has-small-font-size when theme font size is large.
	 */
	public function test_theme_large_font_size_removes_small_class(): void {
		$this->switch_to_block_theme();
		$this->assertTrue( wp_is_block_theme(), 'Block theme is required for wp_get_global_styles().' );

		$this->set_breadcrumbs_theme_font_size( 'var(--wp--preset--font-size--large)' );

		$markup = $this->render_breadcrumbs();

		$this->assertStringNotContainsString( 'has-small-font-size', $markup, 'Theme large font size should remove the default small class.' );
		$this->assertStringContainsString( 'has-large-font-size', $markup, 'Theme large font size should add the large font size class.' );
	}

	/**
	 * @testdox Should keep has-small-font-size when theme font size is explicitly small.
	 */
	public function test_theme_small_font_size_keeps_small_class(): void {
		$this->switch_to_block_theme();
		$this->assertTrue( wp_is_block_theme(), 'Block theme is required for wp_get_global_styles().' );

		$this->set_breadcrumbs_theme_font_size( 'var(--wp--preset--font-size--small)' );

		$markup = $this->render_breadcrumbs();

		$this->assertStringContainsString( 'has-small-font-size', $markup, 'Theme small font size should keep the small class.' );
	}

	/**
	 * @testdox Should apply theme custom numeric font-size as inline style and remove the default small class.
	 */
	public function test_theme_custom_numeric_font_size(): void {
		$this->switch_to_block_theme();
		$this->assertTrue( wp_is_block_theme(), 'Block theme is required for wp_get_global_styles().' );

		$this->set_breadcrumbs_theme_font_size( '50px' );

		$markup = $this->render_breadcrumbs();

		$this->assertStringContainsString( 'font-size: 50px', $markup, 'Theme custom numeric font size should be applied as inline style.' );
		$this->assertStringNotContainsString( 'has-small-font-size', $markup, 'Theme custom numeric font size should remove the default small class.' );
	}

	/**
	 * @testdox Should apply preset font-size class when theme font size is also set.
	 */
	public function test_preset_font_size_with_theme_override(): void {
		$this->switch_to_block_theme();
		$this->set_breadcrumbs_theme_font_size( 'var(--wp--preset--font-size--large)' );

		$markup = $this->render_breadcrumbs( '{"fontSize":"small"}' );

		$this->assertStringContainsString( 'has-font-size', $markup, 'Preset small font size should add the has-font-size class.' );
		$this->assertStringContainsString( 'has-small-font-size', $markup, 'Breadcrumbs block font size should take priority over theme font size.' );
		$this->assertStringNotContainsString( 'has-large-font-size', $markup, 'Theme large font size should not be applied.' );
	}

	/**
	 * @testdox Should apply custom typography font size and remove default small class when theme font size is also set.
	 */
	public function test_custom_typography_takes_precedence_over_theme(): void {
		$this->switch_to_block_theme();
		$this->set_breadcrumbs_theme_font_size( 'var(--wp--preset--font-size--large)' );

		$markup = $this->render_breadcrumbs( '{"style":{"typography":{"fontSize":"3rem"}}}' );

		$this->assertStringContainsString( 'font-size:', $markup, 'Custom typography font size should be applied as inline style.' );
		$this->assertStringContainsString( '3rem', $markup, 'Custom typography value should appear in the rendered style.' );
		$this->assertStringNotContainsString( 'has-small-font-size', $markup, 'Custom typography should remove the default small class.' );
		$this->assertStringNotContainsString( 'has-large-font-size', $markup, 'Theme large font size should not be applied.' );
	}
}
