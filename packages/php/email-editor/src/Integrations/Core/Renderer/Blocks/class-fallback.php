<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Fallback block renderer.
 * This renderer is used when no specific renderer is found for a block.
 *
 * AbstractBlockRenderer applies some adjustments to the block content, like adding spacers.
 * By using fallback renderer for all blocks we apply there adjustments to all blocks that don't have any renderer.
 *
 * We need to find a better abstraction/architecture for this.
 */
class Fallback extends Abstract_Block_Renderer {
	/**
	 * Renders the block content
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Content that shows nothing, e.g. only a `<style>` tag, is left unwrapped so the block adds no gap.
		if ( ! Html_Processing_Helper::has_visible_content( $block_content ) ) {
			return $block_content;
		}

		$block_attrs = $parsed_block['attrs'] ?? array();

		$table_attrs = array(
			'style' => 'border-collapse: separate;', // Needed because of border radius.
			'width' => '100%',
		);

		$align = $rendering_context->sanitize_text_align( $block_attrs['textAlign'] ?? null )
			?? $rendering_context->sanitize_text_align( $block_attrs['align'] ?? null )
			?? $rendering_context->get_default_text_align();

		$cell_attrs = array(
			'align' => $align,
		);

		return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
	}
}
