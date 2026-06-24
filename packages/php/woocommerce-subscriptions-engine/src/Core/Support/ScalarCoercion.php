<?php
/**
 * ScalarCoercion - shared helpers for coercing untyped (mixed) values read from
 * decoded storage rows or caller-supplied argument maps into the scalar types the
 * entities and value objects declare.
 *
 * Hydration boundaries receive mixed data (JSON-decoded columns, $wpdb string
 * rows, loosely-typed $args). A blind (int)/(string) cast on such a value is
 * unsafe - an array or object would warn or fatal. These helpers guard first,
 * then cast, returning a documented default when the value is not coercible.
 *
 * Lives in the WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Scalar coercion helpers for hydration boundaries.
 */
trait ScalarCoercion {

	/**
	 * Coerce a value to a string, falling back to a default when it is not scalar.
	 *
	 * @param mixed  $value    The raw value.
	 * @param string $fallback Returned when $value is not a scalar.
	 */
	private static function coerce_string( $value, string $fallback = '' ): string {
		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * Coerce a value to a string, or null when it is not a scalar.
	 *
	 * @param mixed $value The raw value.
	 */
	private static function coerce_nullable_string( $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}

	/**
	 * Coerce a value to an int, falling back to a default when it is not an integer.
	 *
	 * Only genuine integers and integer-valued strings are accepted; fractional or
	 * exponent forms (`1.5`, `1e2`) are rejected rather than silently truncated, so
	 * a corrupted identifier or counter falls back instead of changing value.
	 *
	 * @param mixed $value    The raw value.
	 * @param int   $fallback Returned when $value is not an integer.
	 */
	private static function coerce_int( $value, int $fallback = 0 ): int {
		if ( is_int( $value ) ) {
			return $value;
		}

		$validated = is_string( $value ) ? filter_var( $value, FILTER_VALIDATE_INT ) : false;

		return false !== $validated ? $validated : $fallback;
	}

	/**
	 * Coerce a value to an int, or null when it is not an integer.
	 *
	 * Same integer-only rule as {@see self::coerce_int()}: fractional/exponent
	 * forms are rejected rather than truncated.
	 *
	 * @param mixed $value The raw value.
	 */
	private static function coerce_nullable_int( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}

		$validated = is_string( $value ) ? filter_var( $value, FILTER_VALIDATE_INT ) : false;

		return false !== $validated ? $validated : null;
	}
}
