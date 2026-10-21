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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;

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
		$group_content = ! empty( $parsed_block['email_attrs']['two-column-grid'] )
			? $this->render_two_column_grid( $block_content, $parsed_block, $rendering_context )
			: $this->get_inner_content( $block_content );

		return str_replace(
			'{group_content}',
			$group_content,
			$this->get_block_wrapper( $block_content, $parsed_block, $rendering_context )
		);
	}

	/**
	 * Lays out the children of a two-column grid as table rows of two cells.
	 *
	 * Email clients don't support CSS grid, so the Spacing_Preprocessor flags the grids that map
	 * cleanly to a table (see `two-column-grid`); every other grid keeps the stacked layout. Each rendered
	 * child is one cell, cells share the column gap equally so both keep the width the
	 * Blocks_Width_Preprocessor sized their content to, and an odd last item keeps its column width
	 * next to an empty cell.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function render_two_column_grid( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$dom_helper = new Dom_Document_Helper( $block_content );
		$wrapper    = $dom_helper->find_element( 'div' );
		if ( ! $wrapper ) {
			return $block_content;
		}

		// Each child block renders as one top-level element. Comments (the children's Outlook
		// wrappers) and whitespace between them are dropped, since the cell is the wrapper now.
		$items = array();
		foreach ( $wrapper->childNodes as $node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $node instanceof \DOMElement ) {
				$items[] = $dom_helper->get_outer_html( $node );
			}
		}

		$half_column_gap = Styles_Helper::parse_value( $parsed_block['email_attrs']['grid-column-gap'] ?? '0px' ) / 2;
		$row_gap         = Styles_Helper::parse_value( $parsed_block['email_attrs']['grid-row-gap'] ?? '0px' );

		$rows = '';
		foreach ( array_chunk( $items, 2 ) as $row_index => $row_items ) {
			$cells = '';
			for ( $column = 0; $column < 2; $column++ ) {
				// The gap sits between the cells: on the end side of the first and the start side of the second.
				$gap_side    = 0 === $column ? $rendering_context->get_end_side() : $rendering_context->get_start_side();
				$cell_styles = array(
					'width'                => '50%',
					'vertical-align'       => 'top',
					'padding-' . $gap_side => $half_column_gap . 'px',
				);
				if ( $row_index > 0 ) {
					$cell_styles['padding-top'] = $row_gap . 'px';
				}

				$cells .= Table_Wrapper_Helper::render_table_cell(
					$row_items[ $column ] ?? '',
					array(
						'class'  => 'email-block-grid-cell',
						'style'  => $this->compile_css( $cell_styles ),
						'valign' => 'top',
						'width'  => '50%',
					)
				);
			}

			$rows .= Table_Wrapper_Helper::render_table_wrapper(
				$cells,
				array(
					'class' => 'email-block-grid-row',
					'style' => 'width: 100%; border-collapse: collapse; table-layout: fixed;',
					'width' => '100%',
				),
				array(),
				array(),
				false
			);
		}

		return $rows;
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

		if ( 'grid' === ( $parsed_block['attrs']['layout']['type'] ?? '' ) ) {
			// The grid layout classes pull CSS grid rules onto the wrapper table when styles are inlined.
			// The grid is rebuilt as rows or stacked, so drop them.
			$original_classname = (string) preg_replace( '/(^|\s)(is-layout-grid|wp-block-group-is-layout-grid|wp-container-core-group-is-layout-[\w-]+)(?=\s|$)/', '', $original_classname );
		}

		$table_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background', 'background-color', 'color', 'text-align' ) );
		$table_styles = Styles_Helper::extend_block_styles(
			$table_styles,
			array_filter(
				array(
					'border-collapse' => 'separate',
					'background-size' => $table_styles['background-size'] ?? 'cover',
				)
			)
		);

		// Padding properties need to be added to the table cell.
		// When suppress-horizontal-padding is set, the container's horizontal
		// padding has been distributed per-block by the Spacing_Preprocessor.
		// Only vertical padding is kept on the group's own CSS output.
		$cell_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'padding' ) );
		if ( ! empty( $parsed_block['email_attrs']['suppress-horizontal-padding'] ) ) {
			$cell_styles = $this->remove_horizontal_padding( $cell_styles );
		}

		$table_attrs = array(
			'class' => 'email-block-group ' . $original_classname,
			'style' => $table_styles['css'],
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-block-group-content',
			'style' => $cell_styles['css'],
			'width' => $parsed_block['email_attrs']['width'] ?? '100%',
		);

		return Table_Wrapper_Helper::render_table_wrapper( '{group_content}', $table_attrs, $cell_attrs );
	}

	/**
	 * Remove horizontal padding properties from compiled cell styles.
	 *
	 * Used when suppress-horizontal-padding is set to prevent the group
	 * from applying its own horizontal padding (which has been distributed
	 * per-block by the Spacing_Preprocessor).
	 *
	 * @param array $cell_styles The compiled cell styles from Styles_Helper::get_block_styles().
	 * @return array Styles with horizontal padding removed.
	 */
	private function remove_horizontal_padding( array $cell_styles ): array {
		if ( ! isset( $cell_styles['declarations'] ) || ! is_array( $cell_styles['declarations'] ) ) {
			return $cell_styles;
		}

		unset(
			$cell_styles['declarations']['padding-left'],
			$cell_styles['declarations']['padding-right']
		);

		// Rebuild CSS string from remaining declarations.
		return Styles_Helper::extend_block_styles( $cell_styles, array() );
	}
}
