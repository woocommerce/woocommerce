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

	/**
	 * Get the name of a plugin in the form 'directory/file.php', as in the keys of the array returned by 'get_plugins'.
	 *
	 * @param string $plugin_file_path The path of the main plugin file (can be passed as __FILE__ from the plugin itself).
	 * @return string The name of the plugin in the form 'directory/file.php'.
	 */
	public static function plugin_name_from_plugin_file( string $plugin_file_path ): string {
		return basename( dirname( $plugin_file_path ) ) . DIRECTORY_SEPARATOR . basename( $plugin_file_path );
	}

	/**
	 * Check if a string is null or is empty.
	 *
	 * @param string|null $value The string to check.
	 * @return bool True if the string is null or is empty.
	 */
	public static function is_null_or_empty( ?string $value ) {
		return is_null( $value ) || '' === $value;
	}

	/**
	 * Check if a string is null, is empty, or has only whitespace characters
	 * (space, tab, vertical tab, form feed, carriage return, new line)
	 *
	 * @param string|null $value The string to check.
	 * @return bool True if the string is null, is empty, or contains only whitespace characters.
	 */
	public static function is_null_or_whitespace( ?string $value ) {
		return is_null( $value ) || '' === $value || ctype_space( $value );
	}

	/**
	 * Convert an array of values to a list suitable for a SQL "IN" statement
	 * (so comma separated and delimited by parenthesis).
	 * e.g.: [1,2,3] --> (1,2,3)
	 *
	 * @param array $values The values to convert.
	 * @return string A parenthesized and comma-separated string generated from the values.
	 * @throws \InvalidArgumentException Empty values array passed.
	 */
	public static function to_sql_list( array $values ) {
		if ( empty( $values ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \InvalidArgumentException( self::class_name_without_namespace( __CLASS__ ) . '::' . __FUNCTION__ . ': the values array is empty' );
		}

		return '(' . implode( ',', $values ) . ')';
	}

	/**
	 * Get the name of a class without the namespace.
	 *
	 * @param string $class_name The full class name.
	 * @return string The class name without the namespace.
	 */
	public static function class_name_without_namespace( string $class_name ) {
		// A '?:' would convert this to a one-liner, but WP coding standards disallow these :shrug:.
		$result = substr( strrchr( $class_name, '\\' ), 1 );
		return $result ? $result : $class_name;
	}

	/**
	 * Normalize the slashes (/ and \) of a local filesystem path by converting them to DIRECTORY_SEPARATOR.
	 *
	 * @param string|null $path Path to normalize.
	 * @return string|null Normalized path, or null if the input was null.
	 */
	public static function normalize_local_path_slashes( ?string $path ) {
		return is_null( $path ) ? null : str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $path );
	}
}
