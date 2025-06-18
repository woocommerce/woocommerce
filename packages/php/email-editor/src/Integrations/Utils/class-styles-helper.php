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
	 * Parse number value from a string.
	 *
	 * @param string $value String value with value and unit.
	 * @return float
	 */
	public static function parse_value( string $value ): float {
		if ( preg_match( '/^\s*(-?\d+(?:\.\d+)?)/', $value, $m ) ) {
			return (float) $m[1];
		}
		return 0.0;
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
			$style = explode( ':', $style, 2 );
			if ( count( $style ) === 2 ) {
				$parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
			}
		}
		return $parsed_styles;
	}
}
