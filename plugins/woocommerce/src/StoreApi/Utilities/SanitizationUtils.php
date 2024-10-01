<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * SanitizationUtils class.
 * Helper class which sanitizes customer info.
 */
class SanitizationUtils {

	/**
	 * Runs wp_kses on an array. This function runs wp_kses on strings in the array and recurses into arrays.
	 *
	 * @param  array $array The array to run wp_kses on.
	 * @return mixed       The array, all string keys will have been run through wp_kses.
	 */
	public function wp_kses_array( array $array ) {
		foreach ( $array as $key => $value ) {
			if ( empty( $value ) ) {
				$array[ $key ] = $value;
				continue;
			}
			if ( is_array( $value ) ) {
				$array[ $key ] = $this->wp_kses_array( $value );
			}
			if ( is_string( $value ) ) {
				$array[ $key ] = wp_kses( $value, [] );
			}
		}
		return $array;
	}
}
