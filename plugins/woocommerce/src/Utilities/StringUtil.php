<?php
/**
 * A class of utilities for dealing with strings.
 */

namespace Automattic\WooCommerce\Utilities;

/**
 * A class of utilities for dealing with strings.
 */
final class StringUtil {

	/**
	 * Checks to see whether or not a string starts with another.
	 *
	 * @param string $string The string we want to check.
	 * @param string $starts_with The string we're looking for at the start of $string.
	 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
	 *
	 * @return bool True if the $string starts with $starts_with, false otherwise.
	 */
	public static function starts_with( string $string, string $starts_with, bool $case_sensitive = true ): bool {
		$len = strlen( $starts_with );
		if ( $len > strlen( $string ) ) {
			return false;
		}

		$string = substr( $string, 0, $len );

		if ( $case_sensitive ) {
			return strcmp( $string, $starts_with ) === 0;
		}

		return strcasecmp( $string, $starts_with ) === 0;
	}

	/**
	 * Checks to see whether or not a string ends with another.
	 *
	 * @param string $string The string we want to check.
	 * @param string $ends_with The string we're looking for at the end of $string.
	 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
	 *
	 * @return bool True if the $string ends with $ends_with, false otherwise.
	 */
	public static function ends_with( string $string, string $ends_with, bool $case_sensitive = true ): bool {
		$len = strlen( $ends_with );
		if ( $len > strlen( $string ) ) {
			return false;
		}

		$string = substr( $string, -$len );

		if ( $case_sensitive ) {
			return strcmp( $string, $ends_with ) === 0;
		}

		return strcasecmp( $string, $ends_with ) === 0;
	}

	/**
	 * Checks if one string is contained into another at any position.
	 *
	 * @param string $string The string we want to check.
	 * @param string $contained The string we're looking for inside $string.
	 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
	 * @return bool True if $contained is contained inside $string, false otherwise.
	 */
	public static function contains( string $string, string $contained, bool $case_sensitive = true ): bool {
		if ( $case_sensitive ) {
			return false !== strpos( $string, $contained );
		} else {
			return false !== stripos( $string, $contained );
		}
	}
}
