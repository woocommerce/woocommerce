<?php //phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
// many places seem to be calling round with a string, and that causes PHP > 8.1 to throw a TypeError.
// It's not respecting the 'mixed' type hint in the docblock, and type unions aren't supported until PHP > 8.0.
// so I'm not sure how to handle this since adding the type union will cause errors below PHP < 8.0.
/**
 * A class of utilities for dealing with numbers.
 */


namespace Automattic\WooCommerce\Utilities;

/**
 * A class of utilities for dealing with numbers.
 */
final class NumberUtil {
	/**
	 * Converts numbers (floats, strings, integers) to numeric values to be safely used in PHP functions like floor() which expect int or float.
	 *
	 * @param mixed $value The value to convert.
	 * @param mixed $fallback The value to return if the conversion fails.
	 * @return int|float|mixed Returns the numeric value or the fallback value if conversion fails.
	 */
	public static function normalize( $value, $fallback = 0 ) {
		// Trim string values to handle whitespace consistently across PHP versions.
		if ( is_string( $value ) ) {
			$value = trim( $value );
		}

		if ( is_numeric( $value ) ) {
			$numeric_value = is_string( $value ) ? floatval( $value ) : $value;

			// Round to precision to avoid floating-point precision issues.
			return is_int( $numeric_value ) ? $numeric_value : round( $numeric_value, WC_ROUNDING_PRECISION );
		}

		return $fallback;
	}

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
	 * @return float The value rounded to the given precision as a float.
	 */
	public static function round( $val, int $precision = 0, int $mode = PHP_ROUND_HALF_UP ): float {
		return round( self::normalize( $val ), $precision, $mode );
	}

	/**
	 * Floor a number using the built-in `floor` function.
	 *
	 * @param mixed $val The value to floor.
	 * @return float
	 */
	public static function floor( $val ): float {
		return floor( self::normalize( $val ) );
	}

	/**
	 * Ceil a number using the built-in `ceil` function.
	 *
	 * @param mixed $val The value to ceil.
	 * @return float
	 */
	public static function ceil( $val ): float {
		return ceil( self::normalize( $val ) );
	}

	/**
	 * Get the sum of an array of values using the built-in array_sum function, but sanitize the array values
	 * first to ensure they are all floats.
	 *
	 * This is needed because in PHP 8.3 non-numeric values that cannot be cast as an int or a float will
	 * cause an E_WARNING to be emitted. Prior to PHP 8.3 these values were just ignored.
	 *
	 * Note that, unlike the built-in array_sum, this one will always return a float, never an int.
	 *
	 * @param array $arr The array of values to sum.
	 *
	 * @return float
	 */
	public static function array_sum( array $arr ): float {
		$sanitized_array = array_map( 'floatval', $arr );

		return array_sum( $sanitized_array );
	}

	/**
	 * Sanitize a cost value based on the current locale decimal and thousand separators.
	 *
	 * @param string $value               The value to sanitize.
	 * @return string                     The sanitized value.
	 * @throws \InvalidArgumentException If the value is not a valid numeric value.
	 */
	public static function sanitize_cost_in_current_locale( $value ): string {
		$value                      = is_null( $value ) ? '' : $value;
		$value                      = wp_kses_post( trim( wp_unslash( $value ) ) );
		$currency_symbol_encoded    = get_woocommerce_currency_symbol();
		$currency_symbol_variations = array( $currency_symbol_encoded, wp_kses_normalize_entities( $currency_symbol_encoded ), html_entity_decode( $currency_symbol_encoded, ENT_COMPAT ) );

		$value = str_replace( $currency_symbol_variations, '', $value );

		// Count the number of decimal points.
		$decimal_point_count = substr_count( $value, '.' );

		// If it's a standard decimal number (single decimal point and is_numeric), accept it directly. This could be in the case where the frontend has de-localised the value.
		// We check for the decimal point count in addition to using is_numeric.
		// This is because is_numeric is much looser and accepts non-base10 numbers as well as 'e' to demarcate exponents.
		if ( 1 === $decimal_point_count && is_numeric( $value ) ) {
			return $value;
		}

		// Otherwise, attempt to delocalise according to localisation rules.
		$allowed_characters_regex = sprintf(
			'/^[0-9\%s\%s]*$/',
			wc_get_price_thousand_separator(),
			wc_get_price_decimal_separator()
		);

		if ( 1 !== preg_match( $allowed_characters_regex, $value ) ) {
			throw new \InvalidArgumentException(
				esc_html(
					sprintf(
						/* translators: %1$s: Invalid value that was input by the user, %2$s: thousand separator, %3$s: decimal separator */
						__( '%1$s is not a valid numeric value. Allowed characters are numbers, the thousand (%2$s), and decimal (%3$s) separators.', 'woocommerce' ),
						$value,
						wc_get_price_thousand_separator(),
						wc_get_price_decimal_separator()
					)
				)
			);
		}

		// Validate decimal and thousand separator positions.
		$decimal_separator  = wc_get_price_decimal_separator();
		$thousand_separator = wc_get_price_thousand_separator();

		if (
			// Check that there is only 1 decimal separator.
			substr_count( $value, $decimal_separator ) > 1 ||
			(
				// Check that decimal separator appears after thousand separator if both exist.
				false !== strpos( $value, $thousand_separator ) &&
				false !== strpos( $value, $decimal_separator ) &&
				strpos( $value, $decimal_separator ) <= strpos( $value, $thousand_separator )
			)
		) {
			throw new \InvalidArgumentException(
				esc_html(
					sprintf(
						/* translators: %s: Invalid value that was input by the user */
						__( '%s is not a valid numeric value: there should be one decimal separator and it has to be after the thousands separator.', 'woocommerce' ),
						$value
					)
				)
			);
		}

		/**
		 * For context, as of 2025.
		 * The full set of thousands separators is PERIOD, COMMA, SPACE, APOSTROPHE.
		 * And the full set of decimal separators is PERIOD, COMMA.
		 * There are no locales that use the same thousands and decimal separators.
		 */

		$value = str_replace( wc_get_price_thousand_separator(), '', $value );
		$value = str_replace( wc_get_price_decimal_separator(), '.', $value );

		if ( $value && ! is_numeric( $value ) ) {
			/* translators: %s: Invalid value that was input by the user */
			throw new \InvalidArgumentException(
				esc_html(
					sprintf(
						/* translators: %s: Invalid value that was input by the user */
						__( '%s is not a valid numeric value.', 'woocommerce' ),
						$value
					)
				)
			);
		}

		return $value;
	}
}
