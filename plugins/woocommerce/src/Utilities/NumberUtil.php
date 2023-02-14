<?php
/**
 * A class of utilities for dealing with numbers.
 */

namespace Automattic\WooCommerce\Utilities;

/**
 * A class of utilities for dealing with numbers.
 */
final class NumberUtil {

	/**
	 * Round a number using the built-in `round` function, but unless the value to round is numeric
	 * (a number or a string that can be parsed as a number), apply 'floatval' first to it
	 * (so it will convert it to 0 in most cases).
	 *
	 * This is needed because in PHP 7 applying `round` to a non-numeric value returns 0,
	 * but in PHP 8 it throws an error. Specifically, in WooCommerce we have a few places where
	 * round('') is often executed.
	 *
	 * @param mixed $val The value to round.
	 * @param int   $precision The optional number of decimal digits to round to.
	 * @param int   $mode A constant to specify the mode in which rounding occurs.
	 *
	 * @return float The value rounded to the given precision as a float, or the supplied default value.
	 */
	public static function round( $val, int $precision = 0, int $mode = PHP_ROUND_HALF_UP ) : float {
		if ( ! is_numeric( $val ) ) {
			$val = floatval( $val );
		}
		return round( $val, $precision, $mode );
	}

	/**
	 * Works the same as the built-in `floor` function, but handles invalid inputs so as not
	 * to throw an error in PHP 8+.
	 *
	 * @param mixed $num The value to round down.
	 *
	 * @return float|false The value rounded down to the next integer value (as a float), or false if non-numeric.
	 */
	public static function floor( $num ) : float {
		if ( ! is_int( $num ) && ! is_float( $num ) ) {
			if ( is_numeric( $num ) ) {
				$num = floatval( $num );
			} else {
				return false;
			}
		}
		return floor( $num );
	}
}
