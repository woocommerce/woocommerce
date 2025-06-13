<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * This class should guarantee that our work with the DOMDocument is unified and safe.
 */
class Styles_Helper {
	/**
	 * Parse number value from a string with an optional unit as a parameter.
	 *
	 * @param string $value String value with value and unit.
	 * @param string $unit Unit that should be removed from the value.
	 * @return float
	 */
	public static function parse_value( string $value, string $unit = 'px' ): float {
		return (float) str_replace( $unit, '', trim( $value ) );
	}

	/**
	 * Parse styles string to array.
	 *
	 * @param string $styles Styles string.
	 * @return array
	 */
	public static function parse_styles_to_array( string $styles ): array {
		$styles = explode( ';', $styles );

		$parsed_styles = array();
		foreach ( $styles as $style ) {
			$style = explode( ':', $style );
			if ( count( $style ) === 2 ) {
				$parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
			}
		}
		return $parsed_styles;
	}
}
