<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Dummy block renderer for testing purposes.
 */
class Dummy_Block_Renderer implements Block_Renderer {
	/**
	 * Renders the block.
	 *
	 * @param string            $block_content The block content.
	 * @param array             $parsed_block The parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		return $parsed_block['innerHTML'] ?? '';
	}
}
