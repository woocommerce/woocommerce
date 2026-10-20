<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplatesRegistry lazy-load content population.
 */
final class BlockTemplatesRegistryTest extends WC_Unit_Test_Case {
	/**
	 * @testdox lazy_load_requested_templates_content populates a template matching slug__in.
	 */
	public function test_lazy_load_populates_template_by_slug(): void {
		$sut = wc_get_container()->get( BlockTemplatesRegistry::class );
		// On classic themes Bootstrap skips init(), so register the hook directly.
		add_filter( 'pre_get_block_templates', array( $sut, 'lazy_load_requested_templates_content' ), 0, 3 );

		// Inject the checkout template path (init() on classic theme only registers ComingSoonTemplate).
		$directory  = BlockTemplateUtils::get_templates_directory( 'wp_template' );
		$reflection = new \ReflectionProperty( BlockTemplatesRegistry::class, 'template_paths' );
		$reflection->setAccessible( true );
		$reflection->setValue( $sut, array_merge( $reflection->getValue( $sut ), array( 'woocommerce//page-checkout' => $directory . '/page-checkout.html' ) ) );

		register_block_template( 'woocommerce//page-checkout', array( 'title' => 'Checkout' ) );
		$template = \WP_Block_Templates_Registry::get_instance()->get_registered( 'woocommerce//page-checkout' );

		$this->assertSame( '', $template->content, 'Template content should start empty before lazy-load.' );

		get_block_templates( array( 'slug__in' => array( 'page-checkout' ) ), 'wp_template' );

		$this->assertStringContainsString( '<main class="wp-block-group">', $template->content, 'Content should contain WooCommerce markup.' );
	}
}
