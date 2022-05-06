<?php

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Helper functions for working with arrays.
 */
class Arrays {

	/**
	 * Convert an array to a string with tabbed indentaion.
	 *
	 * @param array|object $input   An object or array.
	 * @param array        $exclude An array of strings of params/keys to exclude.
	 * @param int          $depth   The current depth of the walker.
	 *
	 * @return string
	 */
	public static function printable_array( $input, $exclude = array(), $depth = 0 ) {
		$out   = '';
		$input = is_object( $input ) ? (array) $input : $input;
		if ( is_array( $input ) ) {
			$tabs = str_repeat( '  ', $depth );

			foreach ( $input as $key => $val ) {

				$key = strip_tags( (string) $key );

				if ( in_array( (string) $key, $exclude ) ) {
					continue;
				}

				$out .= $tabs . '[' . $key . '] => ';

				if ( is_array( $val ) || is_object( $val ) ) {
					$out .= '[&#010;' . self::printable_array( $val, $exclude, $depth + 1 ) . $tabs . ']';
				} else {
					$out .= "'$val'";
				}
				$out .= "\r\n";
			}
		}
		return $out;
	}

}
