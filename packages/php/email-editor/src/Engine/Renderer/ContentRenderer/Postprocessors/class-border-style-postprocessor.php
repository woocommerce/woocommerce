<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

/**
 * Postprocessor that handles border-style declarations to ensure consistent rendering across email clients.
 *
 * This postprocessor addresses two main issues:
 *
 * 1. Normalize border-style declarations:
 *    When using a uniform border-style declaration with non-uniform border-widths,
 *    some email clients (like Outlook) will incorrectly display borders on all sides
 *    even when the width is 0. For example:
 *    `border-color: #000000; border-style: solid; border-width: 0 1px 0 0;`
 *    would render borders on all sides in Outlook. This postprocessor normalizes
 *    the border-style declarations to only set styles for sides where border-width > 0:
 *    `border-color: currentColor; border-width: 0 1px 0 0; border-right-style: solid;`
 *
 * 2. Add fallback border styles:
 *    The block editor provides a default solid style for borders that have a width
 *    but no style specified. This postprocessor adds the same `border-style: solid`
 *    fallback to ensure the email rendering matches what users see in the editor.
 *
 * The postprocessor handles all border cases including:
 * - Shorthand border declarations (border: 1px solid black)
 * - Individual side declarations (border-top, border-right, etc.)
 * - Individual property declarations (border-width, border-style, etc.)
 * - Mixed combinations of the above
 */
class Border_Style_Postprocessor implements Postprocessor {
	/**
	 * Postprocess the HTML.
	 *
	 * @param string $html HTML to postprocess.
	 * @return string
	 */
	public function postprocess( string $html ): string {
		$html = (string) preg_replace_callback(
			'/style="(.*?)"/i',
			function ( $matches ) {
				return 'style="' . esc_attr( $this->process_style( $matches[1] ) ) . '"';
			},
			$html
		);

		return $html;
	}

	/**
	 * Processes a style string to ensure border-style is set for borders with width > 0 and removes extra border-style properties.
	 *
	 * @param string $style The style attribute value.
	 * @return string
	 */
	private function process_style( string $style ): string {
		// Parse style into associative array.
		$styles = array();
		foreach ( explode( ';', $style ) as $declaration ) {
			if ( strpos( $declaration, ':' ) !== false ) {
				list( $prop, $value )          = array_map( 'trim', explode( ':', $declaration, 2 ) );
				$styles[ strtolower( $prop ) ] = $value;
			}
		}

		$should_update_style = false;

		// Collect border-widths and styles.
		$border_widths = array();
		$border_styles = array();

		foreach ( $styles as $prop => $value ) {
			if ( 'border' === $prop ) {
				$border_width = $this->extract_width_from_shorthand_value( $value );

				if ( $border_width ) {
					$border_widths['top']    = $border_width;
					$border_widths['right']  = $border_width;
					$border_widths['bottom'] = $border_width;
					$border_widths['left']   = $border_width;
				}

				$border_style = $this->extract_style_from_shorthand_value( $value );
				if ( $border_style ) {
					$border_styles['top']    = $border_style;
					$border_styles['right']  = $border_style;
					$border_styles['bottom'] = $border_style;
					$border_styles['left']   = $border_style;
				}
			}

			if ( preg_match( '/^border-(top|right|bottom|left)$/', $prop, $matches ) ) {
				$border_width = $this->extract_width_from_shorthand_value( $value );
				if ( $border_width ) {
					$border_widths[ $matches[1] ] = $border_width;
				}

				$border_style = $this->extract_style_from_shorthand_value( $value );
				if ( $border_style ) {
					$border_styles[ $matches[1] ] = $border_style;
				}
			}

			if ( 'border-width' === $prop ) {
				$border_widths = array_merge( $border_widths, $this->expand_shorthand_value( $value ) );
			}

			if ( preg_match( '/^border-(top|right|bottom|left)-width$/', $prop, $matches ) ) {
				$border_widths[ $matches[1] ] = $value;
			}

			if ( 'border-style' === $prop ) {
				$border_styles = array_merge( $border_styles, $this->expand_shorthand_value( $value ) );

				// Remove the original border style declaration, as it will be added later.
				unset( $styles[ $prop ] );
				$should_update_style = true;
			}

			if ( preg_match( '/^border-(top|right|bottom|left)-style$/', $prop, $matches ) ) {
				$border_styles[ $matches[1] ] = $value;

				// Remove the original border style declaration, as it will be added later.
				unset( $styles[ $prop ] );
				$should_update_style = true;
			}
		}

		if ( array_diff( array_keys( $border_widths ), array_keys( $border_styles ) ) ) {
			$should_update_style = true;
		}

		if ( ! $should_update_style ) {
			return $style;
		}

		$border_styles_declarations = array();

		foreach ( $border_widths as $side => $value ) {
			if ( $this->is_nonzero_width( $value ) ) {
				$border_styles_declarations[ "border-$side-style" ] = isset( $border_styles[ $side ] ) ? $border_styles[ $side ] : 'solid';
			}
		}

		// If border style declarations for all sides are present and have the same value, use shorthand syntax.
		if ( 4 === count( $border_styles_declarations ) && 1 === count( array_unique( $border_styles_declarations ) ) ) {
			$border_styles_declarations = array( 'border-style' => array_values( $border_styles_declarations )[0] );
		}

		$merged_styles = array_merge( $styles, $border_styles_declarations );
		$updated_style = '';

		foreach ( $merged_styles as $prop => $value ) {
			if ( '' !== $value ) {
				$updated_style .= $prop . ': ' . $value . '; ';
			}
		}

		return trim( $updated_style );
	}

	/**
	 * Expands shorthand border width and style values into individual properties.
	 *
	 * @param string $value The shorthand border value.
	 * @return array<string, string> The expanded border values.
	 */
	private function expand_shorthand_value( string $value ): array {
		$values = preg_split( '/\s+/', trim( $value ) );

		if ( count( $values ) === 4 ) {
			return array(
				'top'    => $values[0],
				'right'  => $values[1],
				'bottom' => $values[2],
				'left'   => $values[3],
			);
		}
		if ( count( $values ) === 3 ) {
			return array(
				'top'    => $values[0],
				'right'  => $values[1],
				'bottom' => $values[2],
				'left'   => $values[1],
			);
		}
		if ( count( $values ) === 2 ) {
			return array(
				'top'    => $values[0],
				'right'  => $values[1],
				'bottom' => $values[0],
				'left'   => $values[1],
			);
		}
		if ( count( $values ) === 1 ) {
			return array(
				'top'    => $values[0],
				'right'  => $values[0],
				'bottom' => $values[0],
				'left'   => $values[0],
			);
		}

		return array();
	}

	/**
	 * Extracts the width from a shorthand value.
	 *
	 * @param string $value The shorthand value.
	 * @return string|null The extracted width or null if no width is found.
	 */
	private function extract_width_from_shorthand_value( string $value ): ?string {
		$parts = preg_split( '/\s+/', trim( $value ) );

		foreach ( $parts as $part ) {
			if ( preg_match( '/^\d+([a-z%]+)?$/', $part ) ) {
				return $part;
			}
		}

		return null;
	}

	/**
	 * Extracts the style from a shorthand value.
	 *
	 * @param string $value The shorthand value.
	 * @return string|null The extracted style or null if no style is found.
	 */
	private function extract_style_from_shorthand_value( string $value ): ?string {
		$parts = preg_split( '/\s+/', trim( $value ) );

		foreach ( $parts as $part ) {
			if ( in_array( $part, array( 'none', 'hidden', 'dotted', 'dashed', 'solid', 'double', 'groove', 'ridge', 'inset', 'outset' ), true ) ) {
				return $part;
			}
		}

		return null;
	}

	/**
	 * Checks if a border width is nonzero.
	 *
	 * @param string $width The width value.
	 * @return bool
	 */
	private function is_nonzero_width( string $width ): bool {
		return preg_match( '/^0([a-z%]+)?$/', trim( $width ) ) ? false : true;
	}
}
