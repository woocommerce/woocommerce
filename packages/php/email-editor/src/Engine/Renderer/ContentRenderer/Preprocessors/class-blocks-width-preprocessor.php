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
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string}                                                                                        $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			// Layout width is recalculated for each block because full-width blocks don't exclude padding.
			$layout_width = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
			$alignment    = $block['attrs']['align'] ?? null;
			// Subtract padding from the block width if it's not full-width.
			if ( 'full' !== $alignment ) {
				$layout_width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['left'] ?? '0px' );
				$layout_width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['right'] ?? '0px' );
			}

			$width_input = $block['attrs']['width'] ?? '100%';
			// Currently we support only % and px units in case only the number is provided we assume it's %
			// because editor saves percent values as a number.
			$width_input = is_numeric( $width_input ) ? "$width_input%" : $width_input;
			$width_input = is_string( $width_input ) ? $width_input : '100%';
			$width       = $this->convert_width_to_pixels( $width_input, $layout_width );

			if ( 'core/columns' === $block['blockName'] ) {
				// Calculate width of the columns based on the layout width and padding.
				$columns_width        = $layout_width;
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['spacing']['padding']['left'] ?? '0px' );
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['spacing']['padding']['right'] ?? '0px' );
				$border_width         = $block['attrs']['style']['border']['width'] ?? '0px';
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['border']['left']['width'] ?? $border_width );
				$columns_width       -= $this->parse_number_from_string_with_pixels( $block['attrs']['style']['border']['right']['width'] ?? $border_width );
				$block['innerBlocks'] = $this->add_missing_column_widths( $block['innerBlocks'], $columns_width );
			}

			// Copy layout styles and update width and padding.
			$modified_layout                                = $layout;
			$modified_layout['contentSize']                 = "{$width}px";
			$modified_styles                                = $styles;
			$modified_styles['spacing']['padding']['left']  = $block['attrs']['style']['spacing']['padding']['left'] ?? '0px';
			$modified_styles['spacing']['padding']['right'] = $block['attrs']['style']['spacing']['padding']['right'] ?? '0px';

			$block['email_attrs']['width'] = "{$width}px";
			$block['innerBlocks']          = $this->preprocess( $block['innerBlocks'], $modified_layout, $modified_styles );
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
	 * Add missing column widths
	 *
	 * @param array $columns Columns.
	 * @param float $columns_width Columns width.
	 * @return array
	 */
	private function add_missing_column_widths( array $columns, float $columns_width ): array {
		$columns_count_with_defined_width = 0;
		$defined_column_width             = 0;
		$columns_count                    = count( $columns );
		foreach ( $columns as $column ) {
			if ( isset( $column['attrs']['width'] ) && ! empty( $column['attrs']['width'] ) ) {
				++$columns_count_with_defined_width;
				$defined_column_width += $this->convert_width_to_pixels( $column['attrs']['width'], $columns_width );
			} else {
				// When width is not set we need to add padding to the defined column width for better ratio accuracy.
				$defined_column_width += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px' );
				$defined_column_width += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px' );
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
					$column_width                     += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['spacing']['padding']['left'] ?? '0px' );
					$column_width                     += $this->parse_number_from_string_with_pixels( $column['attrs']['style']['spacing']['padding']['right'] ?? '0px' );
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
