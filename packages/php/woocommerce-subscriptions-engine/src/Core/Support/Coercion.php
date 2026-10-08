<?php
/**
 * Coercion - shared helpers coercing untyped (mixed) values from storage rows or
 * argument maps into declared scalar and array shapes. Each guards before casting
 * and falls back when the value is not coercible. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Scalar and array coercion helpers for hydration and argument boundaries.
 *
 * @internal Engine implementation detail. Not part of the supported extension API.
 */
final class Coercion {

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

	/**
	 * Re-key an array as string-keyed, recovering `array<string, mixed>` from an
	 * `int|string`-keyed array.
	 *
	 * @param array<int|string, mixed> $value Array to re-key.
	 * @return array<string, mixed>
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_string_keyed( array $value ): array {
		$result = array();
		foreach ( $value as $key => $entry ) {
			$result[ (string) $key ] = $entry;
		}

		return $result;
	}

	/**
	 * Coerce a value to a list of string-keyed rows. A non-array yields an empty
	 * list; non-array rows are skipped.
	 *
	 * @param mixed $value The raw value.
	 * @return array<int, array<string, mixed>>
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_list_of_arrays( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$rows = array();
		foreach ( $value as $row ) {
			if ( is_array( $row ) ) {
				$rows[] = self::coerce_string_keyed( $row );
			}
		}

		return $rows;
	}

	/**
	 * Coerce a value to a string-keyed map of string-keyed entries. A non-array
	 * yields an empty map; non-array entries are skipped.
	 *
	 * @param mixed $value The raw value.
	 * @return array<string, array<string, mixed>>
	 * @internal Engine implementation detail. Not part of the supported extension API.
	 */
	public static function coerce_map_of_arrays( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$map = array();
		foreach ( $value as $key => $entry ) {
			if ( is_array( $entry ) ) {
				$map[ (string) $key ] = self::coerce_string_keyed( $entry );
			}
		}

		return $map;
	}
}
