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
