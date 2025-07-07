<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use WP_Style_Engine;

/**
 * Renders a group block.
 */
class Group extends Abstract_Block_Renderer {
	/**
	 * Renders the block content
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$content      = '';
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		foreach ( $inner_blocks as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{group_content}',
			$content,
			$this->get_block_wrapper( $block_content, $parsed_block, $rendering_context )
		);
	}

	/**
	 * Returns the block wrapper.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 */
	private function get_block_wrapper( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$original_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes   = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style'           => array(),
				'backgroundColor' => '',
				'textColor'       => '',
				'borderColor'     => '',
				'layout'          => array(),
			)
		);

		// Layout, background, borders need to be on the outer table element.
		$table_styles = $this->get_styles_from_block(
			array(
				'color'      => array_filter(
					array(
						'background' => $block_attributes['backgroundColor'] ? $rendering_context->translate_slug_to_color( $block_attributes['backgroundColor'] ) : null,
						'text'       => $block_attributes['textColor'] ? $rendering_context->translate_slug_to_color( $block_attributes['textColor'] ) : null,
						'border'     => $block_attributes['borderColor'] ? $rendering_context->translate_slug_to_color( $block_attributes['borderColor'] ) : null,
					)
				),
				'background' => $block_attributes['style']['background'] ?? array(),
				'border'     => $block_attributes['style']['border'] ?? array(),
				'spacing'    => array( 'padding' => $block_attributes['style']['spacing']['margin'] ?? array() ),
			)
		)['declarations'];

		$table_styles['border-collapse'] = 'separate'; // Needed for the border radius to work.

		// Padding properties need to be added to the table cell.
		$cell_styles = $this->get_styles_from_block(
			array(
				'spacing' => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
			)
		)['declarations'];

		$table_styles['background-size'] = empty( $table_styles['background-size'] ) ? 'cover' : $table_styles['background-size'];
		$width                           = $parsed_block['email_attrs']['width'] ?? '100%';

		$table_attrs = array(
			'class' => 'email-block-group ' . $original_classname,
			'style' => WP_Style_Engine::compile_css( $table_styles, '' ),
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-block-group-content',
			'style' => WP_Style_Engine::compile_css( $cell_styles, '' ),
			'width' => $width,
		);

		return Table_Wrapper_Helper::render_table_wrapper( '{group_content}', $table_attrs, $cell_attrs );
	}
}
