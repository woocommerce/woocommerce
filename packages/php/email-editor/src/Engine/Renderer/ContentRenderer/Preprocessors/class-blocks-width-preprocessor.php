<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

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
				$layout_width -= $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $block['email_attrs']['container-padding-left'] ?? '0px', $variables_map ) );
				$layout_width -= $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $block['email_attrs']['container-padding-right'] ?? '0px', $variables_map ) );
			}

			// Resolve block padding — may be preset references like var:preset|spacing|20.
			// When suppress-horizontal-padding is set, the block's horizontal padding
			// has been distributed per-block by the Spacing_Preprocessor. Zero it out
			// so children get the full available width.
			$suppress_h_padding  = ! empty( $block['email_attrs']['suppress-horizontal-padding'] );
			$block_padding_left  = $suppress_h_padding ? '0px' : $this->resolve_preset_value( $block['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map );
			$block_padding_right = $suppress_h_padding ? '0px' : $this->resolve_preset_value( $block['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map );

			$width_input = $block['attrs']['width'] ?? '100%';
			// Currently we support only % and px units in case only the number is provided we assume it's %
			// because editor saves percent values as a number.
			$width_input = is_numeric( $width_input ) ? "$width_input%" : $width_input;
			$width_input = is_string( $width_input ) ? $width_input : '100%';
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
			$modified_layout                                = $layout;
			$modified_layout['contentSize']                 = "{$width}px";
			$modified_styles                                = $styles;
			$modified_styles['spacing']['padding']['left']  = $block_padding_left;
			$modified_styles['spacing']['padding']['right'] = $block_padding_right;

			$block['email_attrs']['width'] = "{$width}px";
			$block['innerBlocks']          = $this->calculate_widths( $block['innerBlocks'], $modified_layout, $modified_styles, $variables_map );
			$parsed_blocks[ $key ]         = $block;
		}
		return $parsed_blocks;
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
	 * Resolve a CSS value that may contain a preset variable reference.
	 *
	 * Block attributes store padding as preset references like
	 * "var:preset|spacing|20" which resolve to actual pixel values
	 * (e.g. "8px"). This method converts the reference to its resolved
	 * value using the variables map passed through styles.
	 *
	 * @param string $value The CSS value, possibly a preset reference.
	 * @param array  $variables_map Map of CSS variable names to resolved values.
	 * @return string The resolved value (e.g. "8px") or the original value.
	 */
	private function resolve_preset_value( string $value, array $variables_map ): string {
		if ( strpos( $value, 'var:preset|' ) !== 0 ) {
			return $value;
		}

		$css_var_name = '--wp--' . str_replace( '|', '--', str_replace( 'var:', '', $value ) );
		return $variables_map[ $css_var_name ] ?? $value;
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
				$defined_column_width += $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map ) );
				$defined_column_width += $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map ) );
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
					$column_width                     += $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px', $variables_map ) );
					$column_width                     += $this->parse_number_from_string_with_pixels( $this->resolve_preset_value( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px', $variables_map ) );
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
