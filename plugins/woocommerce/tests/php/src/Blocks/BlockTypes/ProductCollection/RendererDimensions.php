<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Renderer;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductCollection Renderer dimension handling.
 */
class RendererDimensions extends WC_Unit_Test_Case {

	/**
	 * Test that handle_block_dimensions does not throw a warning when fixedWidth is missing.
	 *
	 * @testdox No PHP warning when widthType is 'fixed' but fixedWidth key is absent from block attributes.
	 */
	public function test_no_warning_when_fixedwidth_is_missing(): void {
		$renderer = new Renderer();
		$html     = '<div class="wc-block-product-collection"><ul><li>Product</li></ul></div>';
		$p        = new \WP_HTML_Tag_Processor( $html );

		$p->next_tag( 'div' );

		$block = array(
			'attrs' => array(
				'dimensions' => array(
					'widthType' => 'fixed',
				),
			),
		);

		// Should not throw a PHP warning when fixedWidth is missing.
		$method = new \ReflectionMethod( $renderer, 'handle_block_dimensions' );
		$method->setAccessible( true );
		$method->invoke( $renderer, $p, $block );

		// The div should not have a style attribute set since fixedWidth was not provided.
		$this->assertNull( $p->get_attribute( 'style' ), 'Style attribute should not be set when fixedWidth is missing.' );
	}

	/**
	 * Test that handle_block_dimensions applies style when fixedWidth is provided.
	 *
	 * @testdox Style is applied when widthType is 'fixed' and fixedWidth has a valid value.
	 */
	public function test_style_applied_when_fixedwidth_is_provided(): void {
		$renderer = new Renderer();
		$html     = '<div class="wc-block-product-collection"><ul><li>Product</li></ul></div>';
		$p        = new \WP_HTML_Tag_Processor( $html );

		$p->next_tag( 'div' );

		$block = array(
			'attrs' => array(
				'dimensions' => array(
					'widthType'  => 'fixed',
					'fixedWidth' => '300px',
				),
			),
		);

		$method = new \ReflectionMethod( $renderer, 'handle_block_dimensions' );
		$method->setAccessible( true );
		$method->invoke( $renderer, $p, $block );

		$style = $p->get_attribute( 'style' );
		$this->assertNotNull( $style, 'Style attribute should be set when fixedWidth is provided.' );
		$this->assertStringContainsString( '300px', $style, 'Style should contain the fixed width value.' );
	}

	/**
	 * Test that handle_block_dimensions does not apply style when fixedWidth is empty string.
	 *
	 * @testdox No style applied when fixedWidth is an empty string.
	 */
	public function test_no_style_when_fixedwidth_is_empty_string(): void {
		$renderer = new Renderer();
		$html     = '<div class="wc-block-product-collection"><ul><li>Product</li></ul></div>';
		$p        = new \WP_HTML_Tag_Processor( $html );

		$p->next_tag( 'div' );

		$block = array(
			'attrs' => array(
				'dimensions' => array(
					'widthType'  => 'fixed',
					'fixedWidth' => '',
				),
			),
		);

		$method = new \ReflectionMethod( $renderer, 'handle_block_dimensions' );
		$method->setAccessible( true );
		$method->invoke( $renderer, $p, $block );

		$this->assertNull( $p->get_attribute( 'style' ), 'Style attribute should not be set when fixedWidth is empty.' );
	}

	/**
	 * Test that handle_block_dimensions does nothing when widthType is 'fill'.
	 *
	 * @testdox No style applied when widthType is not 'fixed'.
	 */
	public function test_no_style_when_widthtype_is_fill(): void {
		$renderer = new Renderer();
		$html     = '<div class="wc-block-product-collection"><ul><li>Product</li></ul></div>';
		$p        = new \WP_HTML_Tag_Processor( $html );

		$p->next_tag( 'div' );

		$block = array(
			'attrs' => array(
				'dimensions' => array(
					'widthType'  => 'fill',
					'fixedWidth' => '300px',
				),
			),
		);

		$method = new \ReflectionMethod( $renderer, 'handle_block_dimensions' );
		$method->setAccessible( true );
		$method->invoke( $renderer, $p, $block );

		$this->assertNull( $p->get_attribute( 'style' ), 'Style attribute should not be set when widthType is fill.' );
	}
}
