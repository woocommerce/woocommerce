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
 * Renders a columns block.
 */
class Columns extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for columns.
	 * Spacing is applied on wrapping columns block. Columns are rendered side by side so no spacer is needed.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$content = '';
		foreach ( $parsed_block['innerBlocks'] ?? array() as $block ) {
			$content .= render_block( $block );
		}

		return str_replace(
			'{columns_content}',
			$content,
			$this->getBlockWrapper( $block_content, $parsed_block, $rendering_context )
		);
	}

	/**
	 * Based on MJML <mj-section>
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 */
	private function getBlockWrapper( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes           = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'align' => null,
				'width' => $rendering_context->get_layout_width_without_padding(),
				'style' => array(),
			)
		);

		$columns_styles = $this->get_styles_from_block(
			array(
				'spacing'    => array( 'padding' => $block_attributes['style']['spacing']['padding'] ?? array() ),
				'color'      => $block_attributes['style']['color'] ?? array(),
				'background' => $block_attributes['style']['background'] ?? array(),
			)
		)['declarations'];

		$border_styles = $this->get_styles_from_block( array( 'border' => $block_attributes['style']['border'] ?? array() ) )['declarations'];

		if ( ! empty( $border_styles ) ) {
			$columns_styles = array_merge( $columns_styles, array( 'border-style' => 'solid' ), $border_styles );
		}

		if ( empty( $columns_styles['background-size'] ) ) {
			$columns_styles['background-size'] = 'cover';
		}

		$columns_table_attrs = array(
			'class' => 'email-block-columns ' . $original_wrapper_classname,
			'style' => 'width:100%;border-collapse:separate;text-align:left;' . WP_Style_Engine::compile_css( $columns_styles, '' ),
			'align' => 'center',
		);

		$columns_content = Table_Wrapper_Helper::render_table_wrapper( '{columns_content}', $columns_table_attrs, array(), array(), false );

		// Margins are not supported well in outlook for tables, so wrap in another table.
		$margins = $block_attributes['style']['spacing']['margin'] ?? array();

		if ( ! empty( $margins ) ) {
			$margin_to_padding_styles = $this->get_styles_from_block(
				array(
					'spacing' => array( 'margin' => $margins ),
				)
			)['css'];

			$wrapper_table_attrs = array(
				'class' => 'email-block-columns-wrapper',
				'style' => 'width:100%;border-collapse:separate;text-align:left;' . $margin_to_padding_styles,
				'align' => 'center',
			);

			$wrapper_cell_attrs = array();

			return Table_Wrapper_Helper::render_table_wrapper( $columns_content, $wrapper_table_attrs, $wrapper_cell_attrs );
		}

		return $columns_content;
	}
}
