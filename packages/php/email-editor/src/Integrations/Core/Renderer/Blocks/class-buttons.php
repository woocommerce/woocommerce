<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

/**
 * Renders a buttons block.
 */
class Buttons extends Abstract_Block_Renderer {
	/**
	 * Provides the Flex_Layout_Renderer instance.
	 *
	 * @var Flex_Layout_Renderer
	 */
	private $flex_layout_renderer;

	/**
	 * Buttons constructor.
	 *
	 * @param Flex_Layout_Renderer $flex_layout_renderer Flex layout renderer.
	 */
	public function __construct(
		Flex_Layout_Renderer $flex_layout_renderer
	) {
		$this->flex_layout_renderer = $flex_layout_renderer;
	}

	/**
	 * Render the block.
	 *
	 * Flex_Layout_Renderer applies margin-top on its inner div/td where Gmail
	 * preserves it. Strip margin-top from email_attrs so add_spacer() doesn't
	 * apply it again on the outer wrapper (which Gmail ignores).
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context The rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$content     = $this->render_content( $block_content, $parsed_block, $rendering_context );
		$email_attrs = $parsed_block['email_attrs'] ?? array();
		unset( $email_attrs['margin-top'] );
		return $this->add_spacer( $content, $email_attrs );
	}

	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Ignore font size set on the buttons block.
		// We rely on TypographyPreprocessor to set the font size on the buttons.
		// Rendering font size on the wrapper causes unwanted whitespace below the buttons.
		if ( isset( $parsed_block['attrs']['style']['typography']['fontSize'] ) ) {
			unset( $parsed_block['attrs']['style']['typography']['fontSize'] );
		}
		return $this->flex_layout_renderer->render_inner_blocks_in_layout( $parsed_block, $rendering_context );
	}
}
