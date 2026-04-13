<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Resolves WordPress preset variable references to their actual values.
 *
 * Block attributes store spacing values as preset references like
 * "var:preset|spacing|20". This class provides shared methods to:
 * - Convert these references to CSS variable names (--wp--preset--spacing--20)
 * - Resolve them to pixel values (e.g. "20px") using a variables map
 * - Convert them to CSS var() syntax for stylesheet use
 *
 * Used by Content_Renderer, Spacing_Preprocessor, and Blocks_Width_Preprocessor
 * to avoid duplicating the same resolution logic.
 */
class Preset_Variable_Resolver {
	/**
	 * Convert a preset variable reference to its CSS variable name.
	 *
	 * Transforms "var:preset|spacing|20" to "--wp--preset--spacing--20".
	 *
	 * @param string $value The preset reference string.
	 * @return string The CSS variable name.
	 */
	private static function to_css_variable_name( string $value ): string {
		return '--wp--' . str_replace( '|', '--', str_replace( 'var:', '', $value ) );
	}

	/**
	 * Check if a value is a preset variable reference.
	 *
	 * @param string $value The CSS value to check.
	 * @return bool True if the value starts with "var:preset|".
	 */
	public static function is_preset_reference( string $value ): bool {
		return strpos( $value, 'var:preset|' ) === 0;
	}

	/**
	 * Resolve a preset variable reference to its actual value.
	 *
	 * Converts "var:preset|spacing|20" to the resolved pixel value (e.g. "20px")
	 * using the provided variables map. Returns the original value if not a preset
	 * reference or if the variable is not found in the map.
	 *
	 * @param string $value The CSS value, possibly a preset reference.
	 * @param array  $variables_map Map of CSS variable names to resolved values.
	 * @return string The resolved value or the original value.
	 */
	public static function resolve( string $value, array $variables_map ): string {
		if ( empty( $variables_map ) || ! self::is_preset_reference( $value ) ) {
			return $value;
		}

		$css_var_name = self::to_css_variable_name( $value );
		return $variables_map[ $css_var_name ] ?? $value;
	}

	/**
	 * Convert a preset variable reference to CSS var() syntax.
	 *
	 * Transforms "var:preset|spacing|20" to "var(--wp--preset--spacing--20)".
	 * Returns the original value if not a preset reference.
	 *
	 * @param string $value The CSS value, possibly a preset reference.
	 * @return string The CSS var() expression or the original value.
	 */
	public static function to_css_var( string $value ): string {
		if ( ! self::is_preset_reference( $value ) ) {
			return $value;
		}

		return 'var(' . self::to_css_variable_name( $value ) . ')';
	}
}
