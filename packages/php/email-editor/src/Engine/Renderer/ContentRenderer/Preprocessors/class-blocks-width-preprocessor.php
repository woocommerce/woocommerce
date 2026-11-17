<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
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
	 * @return array
	 */
	private function calculate_widths( array $parsed_blocks, array $layout, array $styles, array $variables_map = array() ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			$layout_width = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
			$alignment    = $block['attrs']['align'] ?? null;
			// Subtract parent padding from block width if not full-width.
			if ( 'full' !== $alignment ) {
				$layout_width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['left'] ?? '0px' );
				$layout_width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['right'] ?? '0px' );
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

			// A column shrunk to fit its row carries its shrunk width in email_attrs; the saved width would overflow.
			$width_input = $this->normalize_width_input( $block['email_attrs']['column-width'] ?? $block['attrs']['width'] ?? '100%' );
			$width       = $this->convert_width_to_pixels( $width_input, $layout_width );

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

			$block['email_attrs']['width'] = "{$width}px";
			$block['innerBlocks']          = $this->calculate_widths( $block['innerBlocks'], $modified_layout, $modified_styles, $variables_map );
			$parsed_blocks[ $key ]         = $block;
		}
		return $parsed_blocks;
	}

	/**
	 * Normalize a saved width to a string with a unit.
	 *
	 * Only % and px are supported. A bare number is treated as %, because the editor saves percent values as numbers.
	 *
	 * @param mixed $width Saved width.
	 * @return string
	 */
	private function normalize_width_input( $width ): string {
		if ( is_numeric( $width ) ) {
			return "{$width}%";
		}
		return is_string( $width ) ? $width : '100%';
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
				$defined_column_width += $this->convert_width_to_pixels( $this->normalize_width_input( $column['attrs']['width'] ), $columns_width );
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
			// When the defined widths already fill the row, give each unset column an equal share
			// so it isn't zero or negative; shrink_overflowing_columns() then fits the row.
			if ( $default_columns_width <= 0 ) {
				$default_columns_width = round( $columns_width / $columns_count, 2 );
			}
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
		return $this->shrink_overflowing_columns( $columns, $columns_width );
	}

	/**
	 * Scale every column down by the same factor when their widths add up to more than the row.
	 *
	 * Matches flex-shrink on the web, e.g. two columns saved at 100% each become 50%.
	 * The shrunk width goes in email_attrs['column-width']; rows that fit are left untouched.
	 *
	 * @param array $columns Columns, each with a width set.
	 * @param float $columns_width Columns width.
	 * @return array
	 */
	private function shrink_overflowing_columns( array $columns, float $columns_width ): array {
		$pixel_widths = array();
		foreach ( $columns as $key => $column ) {
			$pixel_widths[ $key ] = $this->convert_width_to_pixels( $this->normalize_width_input( $column['attrs']['width'] ?? '100%' ), $columns_width );
		}
		$total_width = array_sum( $pixel_widths );

		// Percent widths are rounded to whole pixels, so allow half a pixel per column
		// before treating the row as overflowing (e.g. 3 x 33.33% of 620px is 621px).
		if ( $total_width <= $columns_width + count( $columns ) * 0.5 ) {
			return $columns;
		}

		$ratio = $columns_width / $total_width;
		foreach ( $pixel_widths as $key => $pixel_width ) {
			$shrunk_width                                   = round( $pixel_width * $ratio, 2 );
			$columns[ $key ]['email_attrs']['column-width'] = "{$shrunk_width}px";
		}
		return $columns;
	}
}
