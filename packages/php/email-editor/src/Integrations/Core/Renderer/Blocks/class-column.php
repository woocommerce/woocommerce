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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a column block.
 */
class Column extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for columns.
	 * Spacing is applied on wrapping columns block. Columns are rendered side by side so no spacer is needed.
	 *
	 * @param string $content Content.
	 * @param array  $email_attrs Email attributes.
	 */
	protected function add_spacer( $content, $email_attrs ): string {
		return $content;
	}

	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		return str_replace(
			'{column_content}',
			$this->get_inner_content( $block_content ),
			$this->get_block_wrapper( $block_content, $parsed_block, $rendering_context )
		);
	}

	/**
	 * Based on MJML <mj-column>
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 */
	private function get_block_wrapper( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes           = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'verticalAlignment' => 'stretch',
				'width'             => $rendering_context->get_layout_width_without_padding(),
				'style'             => array(),
			)
		);

		// The default column alignment is `stretch to fill` which means that we need to set the background color to the main cell
		// to create a feeling of a stretched column. This also needs to apply to CSS classnames which can also apply styles.
		$is_stretched = empty( $block_attributes['verticalAlignment'] ) || 'stretch' === $block_attributes['verticalAlignment'];

		$padding_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'padding' ) );
		$padding_styles = Styles_Helper::extend_block_styles( $padding_styles, array( 'text-align' => 'left' ) );

		$cell_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background', 'background-color', 'color' ) );
		$cell_styles = Styles_Helper::extend_block_styles(
			$cell_styles,
			array_filter(
				array(
					'background-size' => ! empty( $cell_styles['background-image'] ) && empty( $cell_styles['background-size'] ) ? 'cover' : null,
				)
			)
		);

		$wrapper_classname = 'block wp-block-column email-block-column';
		$content_classname = 'email-block-column-content';
		$wrapper_styles    = Styles_Helper::extend_block_styles(
			Styles_Helper::$empty_block_styles,
			array( 'vertical-align' => $is_stretched ? 'top' : $block_attributes['verticalAlignment'] ),
		);
		$content_styles    = Styles_Helper::extend_block_styles( Styles_Helper::$empty_block_styles, array( 'vertical-align' => 'top' ) );

		if ( $is_stretched ) {
			$wrapper_classname .= ' ' . $original_wrapper_classname;
			$wrapper_styles     = Styles_Helper::extend_block_styles( $wrapper_styles, $cell_styles['declarations'] );
		} else {
			$content_classname .= ' ' . $original_wrapper_classname;
			$content_styles     = Styles_Helper::extend_block_styles( $content_styles, $cell_styles['declarations'] );
		}

		// Create the inner table using the helper.
		$inner_table_attrs = array(
			'class' => $content_classname,
			'style' => $content_styles['css'],
			'width' => '100%',
		);

		$inner_cell_attrs = array(
			'align' => 'left',
			'style' => $padding_styles['css'],
		);

		$inner_table = Table_Wrapper_Helper::render_table_wrapper( '{column_content}', $inner_table_attrs, $inner_cell_attrs );

		// Create the outer td element (since this is meant to be used within a columns structure).
		$wrapper_cell_attrs = array(
			'class' => $wrapper_classname,
			'style' => $wrapper_styles['css'],
			'width' => Styles_Helper::parse_value( $block_attributes['width'] ),
		);

		return Table_Wrapper_Helper::render_table_cell( $inner_table, $wrapper_cell_attrs );
	}
}
