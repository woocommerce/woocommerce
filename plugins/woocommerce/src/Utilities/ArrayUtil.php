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

	/**
	 * Converts an array of numbers to a human-readable range, such as "1,2,3,5" to "1-3, 5". It also supports
	 * floating point numbers, however with some perhaps unexpected / undefined behaviour if used within a range.
	 * Source: https://stackoverflow.com/a/34254663/4574
	 *
	 * @param array     $items    An array (in any order, see $sort) of individual numbers.
	 * @param string    $item_separator  The string that separates sequential range groups.  Defaults to ', '.
	 * @param string    $range_separator The string that separates ranges.  Defaults to '-'.  A plausible example otherwise would be ' to '.
	 * @param bool|true $sort     Sort the array prior to iterating?  You'll likely always want to sort, but if not, you can set this to false.
	 *
	 * @return string
	 */
	public static function to_ranges_string( array $items, string $item_separator = ', ', string $range_separator = '-', bool $sort = true ): string {
		if ( $sort ) {
			sort( $items );
		}

		$point = null;
		$range = false;
		$str   = '';

		foreach ( $items as $i ) {
			if ( null === $point ) {
				$str .= $i;
			} elseif ( ( $point + 1 ) === $i ) {
				$range = true;
			} else {
				if ( $range ) {
					$str  .= $range_separator . $point;
					$range = false;
				}
				$str .= $item_separator . $i;
			}
			$point = $i;
		}

		if ( $range ) {
			$str .= $range_separator . $point;
		}

		return $str;
	}
}

