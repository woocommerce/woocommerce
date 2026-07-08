<?php
/**
 * ScalarCoercion - shared helpers coercing untyped (mixed) values from storage rows
 * or argument maps into declared scalar types. Each guards before casting (a blind
 * cast on an array/object would warn or fatal) and returns a default when the value
 * is not coercible. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Scalar coercion helpers for hydration boundaries.
 *
 * @internal Engine implementation detail. Not part of the supported extension API.
 */
final class ScalarCoercion {

	/**
	 * Static helper only.
	 *
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	private function __construct() {}

	/**
	 * Coerce a value to a string, falling back to a default when it is not scalar.
	 *
	 * @param mixed  $value    The raw value.
	 * @param string $fallback Returned when $value is not a scalar.
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_string( $value, string $fallback = '' ): string {
		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * Coerce a value to a string, or null when it is not a scalar.
	 *
	 * @param mixed $value The raw value.
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_nullable_string( $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}

	/**
	 * Coerce a value to an int, falling back when it is not an integer. Only genuine
	 * integers and integer-valued strings pass; fractional/exponent forms (`1.5`,
	 * `1e2`) fall back rather than being silently truncated.
	 *
	 * @param mixed $value    The raw value.
	 * @param int   $fallback Returned when $value is not an integer.
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_int( $value, int $fallback = 0 ): int {
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
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_nullable_int( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}

		$validated = is_string( $value ) ? filter_var( $value, FILTER_VALIDATE_INT ) : false;

		return false !== $validated ? $validated : null;
	}

	/**
	 * Coerce a value to a float, falling back when it is not numeric. The
	 * money/decimal coercion: numbers and numeric strings (a DECIMAL column reads
	 * back as one) pass; a non-numeric value falls back rather than casting to 0.0.
	 *
	 * @param mixed $value    The raw value.
	 * @param float $fallback Returned when $value is not numeric.
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_float( $value, float $fallback = 0.0 ): float {
		return is_numeric( $value ) ? (float) $value : $fallback;
	}
}
