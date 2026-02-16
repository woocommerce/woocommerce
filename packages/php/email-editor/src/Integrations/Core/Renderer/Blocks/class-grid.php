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
 * Renders grid layout for Group blocks with layout.type = 'grid'.
 * Converts CSS Grid layout to table-based HTML for email client compatibility.
 *
 * @since 10.6.0
 */
class Grid {
	/**
	 * Maximum number of columns allowed in the grid.
	 * Email clients typically render at 600-700px width, so more than 4 columns would be too narrow.
	 */
	private const MAX_COLUMNS = 4;

	/**
	 * Default number of columns when not specified.
	 */
	private const DEFAULT_COLUMNS = 3;

	/**
	 * Renders the grid content as a table-based layout.
	 *
	 * @param string            $block_content Block content with rendered children.
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered grid HTML.
	 */
	public function render_grid_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$children = $this->get_children_content( $block_content );

		if ( empty( $children ) ) {
			return '';
		}

		$block_attributes = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style'  => array(),
				'layout' => array(),
			)
		);

		$columns = $this->get_column_count( $block_attributes, $rendering_context );
		$gap     = $this->get_block_gap( $block_attributes, $rendering_context );

		return $this->build_grid_layout( $children, $columns, $gap, $block_content, $parsed_block, $rendering_context );
	}

	/**
	 * Extracts individual child block content from the rendered block HTML.
	 *
	 * Each child block is wrapped in a div.email-block-layout by the spacer.
	 * This method finds those wrappers and extracts the inner content,
	 * stripping the spacer's margin-top and Outlook conditional comments
	 * since the grid table handles spacing.
	 *
	 * @param string $block_content Block content with rendered children.
	 * @return array<string> Array of child block HTML strings.
	 */
	private function get_children_content( string $block_content ): array {
		$dom_helper = new Dom_Document_Helper( $block_content );
		$element    = $dom_helper->find_element( 'div' );
		$inner_html = $element ? $dom_helper->get_element_inner_html( $element ) : $block_content;

		libxml_use_internal_errors( true );
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $inner_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$children = array();
		$divs     = $dom->getElementsByTagName( 'div' );

		foreach ( $divs as $node ) {
			$class = $node->getAttribute( 'class' );
			if ( false === strpos( $class, 'email-block-layout' ) ) {
				continue;
			}

			$child_html = '';
			foreach ( $node->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$child_html .= $dom->saveHTML( $child );
			}

			if ( '' !== trim( $child_html ) ) {
				$children[] = $child_html;
			}
		}

		return $children;
	}

	/**
	 * Determines the number of columns from block attributes.
	 *
	 * Supports two grid modes:
	 * - Manual: uses layout.columnCount directly
	 * - Auto: calculates from layout.minimumColumnWidth and available width
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return int Number of columns (1 to MAX_COLUMNS).
	 */
	private function get_column_count( array $block_attributes, Rendering_Context $rendering_context ): int {
		$layout = $block_attributes['layout'] ?? array();

		if ( ! empty( $layout['columnCount'] ) ) {
			return max( 1, min( self::MAX_COLUMNS, (int) $layout['columnCount'] ) );
		}

		if ( ! empty( $layout['minimumColumnWidth'] ) ) {
			$min_width   = Styles_Helper::parse_value( $layout['minimumColumnWidth'] );
			$total_width = Styles_Helper::parse_value( $rendering_context->get_layout_width_without_padding() );

			if ( $min_width > 0 && $total_width > 0 ) {
				$columns = (int) floor( $total_width / $min_width );
				return max( 1, min( self::MAX_COLUMNS, $columns ) );
			}
		}

		return self::DEFAULT_COLUMNS;
	}

	/**
	 * Extracts the block gap value from block attributes.
	 *
	 * The blockGap can be a string ("16px") or an object ({ "top": "16px", "left": "20px" }).
	 * Returns a single pixel value to use as uniform cell padding.
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return float Gap value in pixels.
	 */
	private function get_block_gap( array $block_attributes, Rendering_Context $rendering_context ): float {
		$block_gap = $block_attributes['style']['spacing']['blockGap'] ?? null;

		if ( is_string( $block_gap ) && '' !== $block_gap ) {
			return Styles_Helper::parse_value( $block_gap );
		}

		if ( is_array( $block_gap ) ) {
			$horizontal = $block_gap['left'] ?? '';
			$vertical   = $block_gap['top'] ?? '';
			$value      = '' !== $horizontal ? $horizontal : $vertical;

			if ( '' !== $value ) {
				return Styles_Helper::parse_value( $value );
			}
		}

		$theme_styles = $rendering_context->get_theme_styles();
		$default_gap  = $theme_styles['spacing']['blockGap'] ?? '';

		return '' !== $default_gap ? Styles_Helper::parse_value( $default_gap ) : 0.0;
	}

	/**
	 * Builds the complete grid layout with wrapper and rows.
	 *
	 * @param array<string>     $children Array of child block HTML strings.
	 * @param int               $columns Number of columns.
	 * @param float             $gap Gap in pixels.
	 * @param string            $block_content Original block content.
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered grid HTML.
	 */
	private function build_grid_layout( array $children, int $columns, float $gap, string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$original_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
		$block_attributes   = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'style'           => array(),
				'backgroundColor' => '',
				'textColor'       => '',
				'borderColor'     => '',
			)
		);

		$table_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background', 'background-color', 'color', 'text-align' ) );
		$table_styles = Styles_Helper::extend_block_styles(
			$table_styles,
			array_filter(
				array(
					'border-collapse' => 'separate',
					'background-size' => $table_styles['declarations']['background-size'] ?? 'cover',
				)
			)
		);

		$cell_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'padding' ) );

		$table_attrs = array(
			'class' => 'email-block-grid ' . $original_classname,
			'style' => $table_styles['css'],
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-block-grid-content',
			'style' => $cell_styles['css'],
			'width' => $parsed_block['email_attrs']['width'] ?? '100%',
		);

		$grid_rows = $this->build_grid_rows( $children, $columns, $gap );

		return Table_Wrapper_Helper::render_table_wrapper( $grid_rows, $table_attrs, $cell_attrs );
	}

	/**
	 * Builds the grid rows as separate tables.
	 *
	 * Each row is a separate table for better email client compatibility,
	 * following the pattern used by the Gallery renderer.
	 *
	 * @param array<string> $children Array of child block HTML strings.
	 * @param int           $columns Number of columns per row.
	 * @param float         $gap Gap in pixels.
	 * @return string Concatenated row tables HTML.
	 */
	private function build_grid_rows( array $children, int $columns, float $gap ): string {
		$rows        = array();
		$child_count = count( $children );
		$cell_gap    = $gap / 2;

		for ( $i = 0; $i < $child_count; $i += $columns ) {
			$row_children = array_slice( $children, $i, $columns );
			$is_first_row = 0 === $i;
			$rows[]       = $this->build_grid_row( $row_children, $cell_gap, $is_first_row );
		}

		return implode( '', $rows );
	}

	/**
	 * Builds a single grid row as a table.
	 *
	 * @param array<string> $row_children Children for this row.
	 * @param float         $cell_gap Half-gap value for cell padding.
	 * @param bool          $is_first_row Whether this is the first row.
	 * @return string Row table HTML.
	 */
	private function build_grid_row( array $row_children, float $cell_gap, bool $is_first_row ): string {
		$items_in_row       = count( $row_children );
		$cell_width_percent = sprintf( '%.2f%%', 100 / $items_in_row );
		$row_cells          = '';

		$vertical_padding = $is_first_row ? '0' : sprintf( '%dpx', (int) ( $cell_gap * 2 ) );

		foreach ( $row_children as $index => $child_html ) {
			$padding_left  = 0 === $index ? '0' : sprintf( '%dpx', (int) $cell_gap );
			$padding_right = $items_in_row - 1 === $index ? '0' : sprintf( '%dpx', (int) $cell_gap );

			$cell_attrs = array(
				'style'  => sprintf(
					'width: %s; padding-top: %s; padding-left: %s; padding-right: %s; vertical-align: top;',
					$cell_width_percent,
					$vertical_padding,
					$padding_left,
					$padding_right
				),
				'valign' => 'top',
			);

			$row_cells .= Table_Wrapper_Helper::render_table_cell( $child_html, $cell_attrs );
		}

		return sprintf(
			'<table role="presentation" style="width: 100%%; border-collapse: collapse; table-layout: fixed;"><tr>%s</tr></table>',
			$row_cells
		);
	}
}
