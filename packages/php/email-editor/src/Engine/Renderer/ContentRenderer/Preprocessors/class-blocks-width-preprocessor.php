<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
/**
 * This class sets the width of the blocks based on the layout width or column count.
 * The final width in pixels is stored in the email_attrs array because we would like to avoid changing the original attributes.
 */
class Blocks_Width_Preprocessor implements Preprocessor {
	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                               $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                          $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left?: string, right?: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		// Root padding is distributed to individual blocks by Spacing_Preprocessor
		// (which runs before this preprocessor). Zero it out here so we don't
		// double-subtract: each block's width is reduced only if the block
		// actually received root-padding-left/right in its email_attrs.
		$variables_map                         = $styles['__variables_map'] ?? array();
		$styles['spacing']['padding']['left']  = '0px';
		$styles['spacing']['padding']['right'] = '0px';

		return $this->calculate_widths( $parsed_blocks, $layout, $styles, $variables_map );
	}

	/**
	 * Recursively calculate block widths based on layout and parent padding.
	 *
	 * At the top level, root padding is zeroed out by preprocess() since it's
	 * distributed to individual blocks. Each block that received root padding
	 * from the Spacing_Preprocessor has its width reduced accordingly. For
	 * nested blocks, the parent block's own padding is subtracted as expected.
	 *
	 * @param array $parsed_blocks Parsed blocks.
	 * @param array $layout Layout settings.
	 * @param array $styles Styles with padding from parent context.
	 * @param array $variables_map CSS variable names to resolved pixel values.
	 * @param bool  $in_grid_cell Whether the blocks sit inside a two-column grid cell.
	 * @return array
	 */
	private function calculate_widths( array $parsed_blocks, array $layout, array $styles, array $variables_map = array(), bool $in_grid_cell = false ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			$layout_width = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
			$alignment    = $block['attrs']['align'] ?? null;
			// Subtract parent padding from block width if not full-width.
			if ( 'full' !== $alignment ) {
				foreach ( array( 'left', 'right' ) as $side ) {
					$parent_padding = $styles['spacing']['padding'][ $side ] ?? '0px';
					// Inside a grid cell a rem or em padding read as px would leave fixed-width blocks
					// too wide for the cell, so convert it.
					$layout_width -= $in_grid_cell
						? $this->convert_padding_to_pixels( $parent_padding, $this->parse_number_from_string_with_pixels( $layout['contentSize'] ) )
						: $this->parse_number_from_string_with_pixels( $parent_padding );
				}
			}

			// Subtract root padding and container padding for blocks that will
			// receive them as CSS padding from Content_Renderer. This ensures
			// block widths fit inside the padding wrapper without overflow.
			if ( 'full' !== $alignment ) {
				$layout_width -= $this->parse_number_from_string_with_pixels( $block['email_attrs']['root-padding-left'] ?? '0px' );
				$layout_width -= $this->parse_number_from_string_with_pixels( $block['email_attrs']['root-padding-right'] ?? '0px' );
				// Container padding may be preset references (var:preset|spacing|20).
				$layout_width -= $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $block['email_attrs']['container-padding-left'] ?? '0px', $variables_map ) );
				$layout_width -= $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $block['email_attrs']['container-padding-right'] ?? '0px', $variables_map ) );
			}

			// Resolve block padding — may be preset references like var:preset|spacing|20.
			// When suppress-horizontal-padding is set, the block's horizontal padding
			// has been distributed per-block by the Spacing_Preprocessor. Zero it out
			// so children get the full available width.
			$suppress_h_padding  = ! empty( $block['email_attrs']['suppress-horizontal-padding'] );
			$block_padding_left  = $suppress_h_padding ? '0px' : Preset_Variable_Resolver::resolve( $block['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map );
			$block_padding_right = $suppress_h_padding ? '0px' : Preset_Variable_Resolver::resolve( $block['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map );

			// Horizontal border reduces the content area available to inner blocks,
			// just like padding does. It must be subtracted so fixed-width children
			// (e.g. images, which emit an explicit width attribute) fit inside the
			// block instead of forcing the surrounding table to expand.
			$block_border_horizontal = $this->get_block_horizontal_border( $block );

			$width_input = $block['attrs']['width'] ?? '100%';
			// Currently we support only % and px units in case only the number is provided we assume it's %
			// because editor saves percent values as a number.
			$width_input = is_numeric( $width_input ) ? "$width_input%" : $width_input;
			$width_input = is_string( $width_input ) ? $width_input : '100%';
			$width       = $this->convert_width_to_pixels( $width_input, $layout_width );
			// A fixed width wider than a grid cell (e.g. a 400px image in a 290px cell) would push into
			// the neighboring cell, so cap it to the cell. An image's border is drawn outside its width.
			if ( $in_grid_cell ) {
				$width = min( $width, 'core/image' === $block['blockName'] ? $layout_width - $block_border_horizontal : $layout_width );
			}

			if ( 'core/columns' === $block['blockName'] ) {
				// Calculate width of the columns based on the layout width and padding.
				$columns_width        = $layout_width;
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block_padding_left );
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block_padding_right );
				$border_width         = $block['attrs']['style']['border']['width'] ?? '0px';
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['border']['left']['width'] ?? $border_width );
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['border']['right']['width'] ?? $border_width );
				$block['innerBlocks'] = $this->add_missing_column_widths( $block['innerBlocks'], $columns_width, $variables_map );
			}

			// Copy layout styles and update width and padding with resolved values.
			// The content area available to inner blocks is the block width minus its
			// own horizontal border (padding is subtracted from this in the next level
			// via $modified_styles).
			$inner_content_width                            = max( 0, $width - $block_border_horizontal );
			$modified_layout                                = $layout;
			$modified_layout['contentSize']                 = "{$inner_content_width}px";
			$modified_styles                                = $styles;
			$modified_styles['spacing']['padding']['left']  = $block_padding_left;
			$modified_styles['spacing']['padding']['right'] = $block_padding_right;

			// Children of a two-column grid each get one cell: half the content area minus the
			// column gap. Without this, fixed-width children such as images are sized to the full
			// group width and overflow their cell.
			if ( ! empty( $block['email_attrs']['two-column-grid'] ) ) {
				$grid_content_width = $inner_content_width
					- $this->convert_padding_to_pixels( $block_padding_left, $inner_content_width )
					- $this->convert_padding_to_pixels( $block_padding_right, $inner_content_width )
					- $this->parse_number_from_string_with_pixels( $block['email_attrs']['grid-column-gap'] ?? '0px' );
				$cell_width         = max( 0, floor( $grid_content_width / 2 ) );

				if ( $this->fits_minimum_column_width( $block, $cell_width ) ) {
					$modified_layout['contentSize']                 = "{$cell_width}px";
					$modified_styles['spacing']['padding']['left']  = '0px';
					$modified_styles['spacing']['padding']['right'] = '0px';
				} else {
					$block = $this->stack_grid( $block );
				}
			}

			$block['email_attrs']['width'] = "{$width}px";
			$block['innerBlocks']          = $this->calculate_widths( $block['innerBlocks'], $modified_layout, $modified_styles, $variables_map, $in_grid_cell || ! empty( $block['email_attrs']['two-column-grid'] ) );
			$parsed_blocks[ $key ]         = $block;
		}
		return $parsed_blocks;
	}

	/**
	 * Convert a padding value to pixels, including rem, em and percentages, which the group still
	 * renders in those units. Other values are read as pixels.
	 *
	 * @param string $value Padding value.
	 * @param float  $containing_width Width a percentage is relative to, in px.
	 * @return float
	 */
	private function convert_padding_to_pixels( string $value, float $containing_width ): float {
		if ( str_ends_with( trim( $value ), '%' ) ) {
			return $containing_width * Styles_Helper::parse_value( $value ) / 100;
		}
		$px = Styles_Helper::convert_to_px( (string) Styles_Helper::clamp_to_static_px( $value ), false );
		return null !== $px ? Styles_Helper::parse_value( $px ) : $this->parse_number_from_string_with_pixels( $value );
	}

	/**
	 * Checks whether a grid's cells meet its minimum column width.
	 *
	 * In auto mode the editor saves both a column count and a minimum column width, and the grid shows
	 * up to that many columns of at least that width, so narrower cells mean the grid stacks.
	 *
	 * @param array $block The grid group block.
	 * @param float $cell_width Width of one cell in px.
	 * @return bool
	 */
	private function fits_minimum_column_width( array $block, float $cell_width ): bool {
		$minimum_width = $block['attrs']['layout']['minimumColumnWidth'] ?? null;
		if ( ! is_string( $minimum_width ) || '' === trim( $minimum_width ) ) {
			return true;
		}

		$minimum_px = Styles_Helper::convert_to_px( (string) Styles_Helper::clamp_to_static_px( $minimum_width ), false );
		return null !== $minimum_px && Styles_Helper::parse_value( $minimum_px ) <= $cell_width;
	}

	/**
	 * Turn a grid flagged by the Spacing_Preprocessor back into the stacked layout, spacing its items
	 * with the grid's row gap as WordPress does when a grid wraps to one column.
	 *
	 * @param array $block The grid group block.
	 * @return array
	 */
	private function stack_grid( array $block ): array {
		unset( $block['email_attrs']['two-column-grid'] );
		foreach ( $block['innerBlocks'] as $key => $inner_block ) {
			unset( $inner_block['email_attrs']['two-column-grid-item'] );
			if ( 0 !== $key ) {
				$inner_block['email_attrs']['margin-top'] = $block['email_attrs']['grid-row-gap'] ?? '0px';
			}
			$block['innerBlocks'][ $key ] = $inner_block;
		}
		return $block;
	}

	// TODO: We could add support for other units like em, rem, etc.
	/**
	 * Convert width to pixels
	 *
	 * @param string $current_width Current width.
	 * @param float  $layout_width Layout width.
	 * @return float
	 */
	private function convert_width_to_pixels( string $current_width, float $layout_width ): float {
		$width = $layout_width;
		if ( strpos( $current_width, '%' ) !== false ) {
			$width = (float) str_replace( '%', '', $current_width );
			$width = round( $width / 100 * $layout_width );
		} elseif ( strpos( $current_width, 'px' ) !== false ) {
			$width = $this->parse_number_from_string_with_pixels( $current_width );
		}

		return $width;
	}

	/**
	 * Parse number from string with pixels
	 *
	 * @param string $value Value with pixels.
	 * @return float
	 */
	private function parse_number_from_string_with_pixels( string $value ): float {
		return (float) str_replace( 'px', '', $value );
	}

	/**
	 * Sum a block's left and right border widths.
	 *
	 * Supports both the shorthand `border.width` and the per-side
	 * `border.left.width` / `border.right.width` formats, matching how the
	 * columns block border is read elsewhere in this class.
	 *
	 * @param array $block Parsed block.
	 * @return float Combined horizontal border width in pixels.
	 */
	private function get_block_horizontal_border( array $block ): float {
		$border_width = $block['attrs']['style']['border']['width'] ?? '0px';
		$left         = $block['attrs']['style']['border']['left']['width'] ?? $border_width;
		$right        = $block['attrs']['style']['border']['right']['width'] ?? $border_width;

		return $this->parse_number_from_string_with_pixels( (string) $left ) + $this->parse_number_from_string_with_pixels( (string) $right );
	}

	/**
	 * Add missing column widths
	 *
	 * @param array $columns Columns.
	 * @param float $columns_width Columns width.
	 * @param array $variables_map CSS variable names to resolved pixel values.
	 * @return array
	 */
	private function add_missing_column_widths( array $columns, float $columns_width, array $variables_map = array() ): array {
		$columns_count_with_defined_width = 0;
		$defined_column_width             = 0;
		$columns_count                    = count( $columns );
		foreach ( $columns as $column ) {
			if ( isset( $column['attrs']['width'] ) && ! empty( $column['attrs']['width'] ) ) {
				++$columns_count_with_defined_width;
				$defined_column_width += $this->convert_width_to_pixels( $column['attrs']['width'], $columns_width );
			} else {
				// When width is not set we need to add padding to the defined column width for better ratio accuracy.
				$defined_column_width += $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map ) );
				$defined_column_width += $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map ) );
				$border_width          = $column['attrs']['style']['border']['width'] ?? '0px';
				$defined_column_width += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['border']['left']['width'] ?? $border_width );
				$defined_column_width += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['border']['right']['width'] ?? $border_width );
			}
		}

		if ( $columns_count - $columns_count_with_defined_width > 0 ) {
			$default_columns_width = round( ( $columns_width - $defined_column_width ) / ( $columns_count - $columns_count_with_defined_width ), 2 );
			foreach ( $columns as $key => $column ) {
				if ( ! isset( $column['attrs']['width'] ) || empty( $column['attrs']['width'] ) ) {
					// Add padding to the specific column width because it's not included in the default width.
					$column_width                      = $default_columns_width;
					$column_width                     += $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map ) );
					$column_width                     += $this->parse_number_from_string_with_pixels( Preset_Variable_Resolver::resolve( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map ) );
					$border_width                      = $column['attrs']['style']['border']['width'] ?? '0px';
					$column_width                     += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['border']['left']['width'] ?? $border_width );
					$column_width                     += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['border']['right']['width'] ?? $border_width );
					$columns[ $key ]['attrs']['width'] = "{$column_width}px";
				}
			}
		}
		return $columns;
	}
}
