<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * A class of utilities for dealing with arrays.
 */
class ArrayUtil {

	/**
	 * Determines if the given array is a list.
	 *
	 * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array)-1.
	 *
	 * Polyfill for array_is_list() in PHP 8.1.
	 *
	 * @param array $arr The array being evaluated.
	 *
	 * @return bool True if array is a list, false otherwise.
	 */
	public static function array_is_list( array $arr ): bool {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $arr );
		}

		if ( ( array() === $arr ) || ( array_values( $arr ) === $arr ) ) {
			return true;
		}

		$next_key = -1;

		foreach ( $arr as $k => $v ) {
			if ( ++$next_key !== $k ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Merge two lists of associative arrays by a key.
	 *
	 * @param array  $arr1 The first array.
	 * @param array  $arr2 The second array.
	 * @param string $key  The key to merge by.
	 *
	 * @return array The merged list sorted by the key values.
	 */
	public static function merge_by_key( array $arr1, array $arr2, string $key ): array {
		$merged = array();
		// Overwrite items in $arr1 with items in $arr2 if they have the same key entry value.
		// The rest of items in $arr1 will be appended.
		foreach ( $arr1 as $item1 ) {
			$found = false;
			foreach ( $arr2 as $item2 ) {
				if ( $item1[ $key ] === $item2[ $key ] ) {
					$merged[] = array_merge( $item1, $item2 );
					$found    = true;
					break;
				}
			}
			if ( ! $found ) {
				$merged[] = $item1;
			}
		}

		// Append items from $arr2 that are don't have a corresponding key entry value in $arr1.
		foreach ( $arr2 as $item2 ) {
			$found = false;
			foreach ( $arr1 as $item1 ) {
				if ( $item1[ $key ] === $item2[ $key ] ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$merged[] = $item2;
			}
		}

		// Sort the merged list by the key values.
		usort(
			$merged,
			function ( $a, $b ) use ( $key ) {
				return $a[ $key ] <=> $b[ $key ];
			}
		);

		return array_values( $merged );
	}

	/**
	 * Recursively filters null values from an array.
	 *
	 * This method removes all null values from the array, including nested arrays.
	 * Array keys are preserved for associative arrays. For lists (sequential numeric
	 * keys starting from 0), the array is reindexed to maintain the list structure.
	 *
	 * @param array $arr The array to filter.
	 *
	 * @return array The filtered array with null values removed.
	 */
	public static function filter_null_values_recursive( array $arr ): array {
		$is_list  = self::array_is_list( $arr );
		$filtered = array();

		foreach ( $arr as $key => $value ) {
			// Skip null values.
			if ( is_null( $value ) ) {
				continue;
			}

			// Recursively filter nested arrays.
			if ( is_array( $value ) ) {
				$filtered[ $key ] = self::filter_null_values_recursive( $value );
			} else {
				$filtered[ $key ] = $value;
			}
		}

		// Reindex if the original array was a list.
		return $is_list ? array_values( $filtered ) : $filtered;
	}
}
