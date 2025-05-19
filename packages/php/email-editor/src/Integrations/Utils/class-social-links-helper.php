<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * This class should provide helper functions for the Social Links block.
 */
class Social_Links_Helper {
	/**
	 * Detects if the input color is whiteish.
	 * This is a helper function to detect if the input color is white or white-ish colors when
	 * provided an input color in format #ffffff or #fff.
	 *
	 * @param string $input_color The input color.
	 * @return bool True if the color is whiteish, false otherwise.
	 */
	public static function detect_whiteish_color( $input_color ) {

		if ( empty( $input_color ) ) {
			return false;
		}

		// Remove # if present.
		$color = ltrim( $input_color, '#' );

		// Convert 3-digit hex to 6-digit hex.
		if ( strlen( $color ) === 3 ) {
			$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
		}

		// Convert hex to RGB.
		$r = hexdec( substr( $color, 0, 2 ) );
		$g = hexdec( substr( $color, 2, 2 ) );
		$b = hexdec( substr( $color, 4, 2 ) );

		// Calculate brightness using perceived brightness formula.
		// Using the formula: (0.299*R + 0.587*G + 0.114*B).
		$brightness = ( 0.299 * $r + 0.587 * $g + 0.114 * $b );

		// Consider colors with brightness above 240 as whiteish.
		// This threshold can be adjusted based on requirements.
		return $brightness > 240;
	}
}
