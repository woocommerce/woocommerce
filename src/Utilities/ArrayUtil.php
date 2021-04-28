<?php
/**
 * A class of utilities for dealing with arrays.
 */

namespace Automattic\WooCommerce\Utilities;

/**
 * A class of utilities for dealing with arrays.
 */
class ArrayUtil {
	/**
	 * Get a value from an nested array by specifying the entire key hierarchy with '::' as separator.
	 *
	 * E.g. for [ 'foo' => [ 'bar' => [ 'fizz' => 'buzz' ] ] ] the value for key 'foo::bar::fizz' would be 'buzz'.
	 *
	 * @param array  $array The array to get the value from.
	 * @param string $key The complete key hierarchy, using '::' as separator.
	 * @param mixed  $default The value to return if the key doesn't exist in the array.
	 *
	 * @return mixed The retrieved value, or the supplied default value.
	 * @throws \Exception $array is not an array.
	 */
	public static function get_nested_value( array $array, string $key, $default = null ) {
		$key_stack = explode( '::', $key );
		$subkey    = array_shift( $key_stack );

		if ( isset( $array[ $subkey ] ) ) {
			$value = $array[ $subkey ];

			if ( count( $key_stack ) ) {
				foreach ( $key_stack as $subkey ) {
					if ( is_array( $value ) && isset( $value[ $subkey ] ) ) {
						$value = $value[ $subkey ];
					} else {
						$value = $default;
						break;
					}
				}
			}
		} else {
			$value = $default;
		}

		return $value;
	}

	/**
	 * Checks if a given key exists in an array and its value can be evaluated as 'true'.
	 *
	 * @param array  $array The array to check.
	 * @param string $key The key for the value to check.
	 * @return bool True if the key exists in the array and the value can be evaluated as 'true'.
	 */
	public static function is_truthy( array $array, string $key ) {
		return isset( $array[ $key ] ) && $array[ $key ];
	}

	/**
	 * Gets the value for a given key from an array, or a default value if the key doesn't exist in the array.
	 *
	 * @param array  $array The array to get the value from.
	 * @param string $key The key to use to retrieve the value.
	 * @param null   $default The default value to return if the key doesn't exist in the array.
	 * @return mixed|null The value for the key, or the default value passed.
	 */
	public static function get_value_or_default( array $array, string $key, $default = null ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}
}

