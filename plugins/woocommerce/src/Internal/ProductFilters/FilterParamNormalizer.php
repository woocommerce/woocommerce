<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Normalises filter query parameters to produce consistent cache keys.
 *
 * Bots that enumerate filter combinations (e.g. `filter_color=red,blue` vs
 * `filter_color=blue,red`) would otherwise generate separate cache entries for
 * logically identical queries.  This class eliminates that class of cache
 * pollution by sorting and lowercasing filter values before hashing.
 *
 * @since 10.8.0
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class FilterParamNormalizer {

	/**
	 * Prefixes whose values are comma-separated lists that should be sorted.
	 *
	 * Covers `filter_<attribute>`, `filter_stock_status`, `filter_<taxonomy>`, etc.
	 *
	 * @var string[]
	 */
	private const MULTI_VALUE_PREFIXES = array( 'filter_' );

	/**
	 * Param keys that hold a single sortable/lowercaseable value.
	 *
	 * `rating_filter` may also carry comma-separated integers (e.g. "3,4,5"),
	 * so it is treated as a multi-value param.
	 *
	 * @var string[]
	 */
	private const MULTI_VALUE_EXACT_KEYS = array( 'rating_filter' );

	/**
	 * Param keys whose prefix indicates a single-value param (query logic type).
	 *
	 * @var string[]
	 */
	private const SINGLE_VALUE_PREFIXES = array( 'query_type_' );

	/**
	 * Param keys that hold plain numeric-ish strings (trim only).
	 *
	 * @var string[]
	 */
	private const NUMERIC_KEYS = array( 'min_price', 'max_price' );

	/**
	 * Normalise query vars for consistent cache key generation.
	 *
	 * Operations performed:
	 * - All parameter keys are sorted alphabetically (ksort) so that insertion
	 *   order does not affect the resulting JSON hash.
	 * - Comma-separated filter values are lowercased, trimmed, and sorted so
	 *   that `filter_color=red,blue` and `filter_color=blue,red` produce the
	 *   same key.
	 * - Single-value filter params (e.g. `query_type_color`) are lowercased
	 *   and trimmed.
	 * - Numeric params (`min_price`, `max_price`) are trimmed.
	 *
	 * @since 10.8.0
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array Normalised query vars.
	 */
	public static function normalize( array $query_vars ): array {
		foreach ( $query_vars as $key => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			if ( self::is_multi_value_param( $key ) ) {
				$query_vars[ $key ] = self::normalize_multi_value( $value );
			} elseif ( self::is_single_value_param( $key ) ) {
				$query_vars[ $key ] = trim( strtolower( $value ) );
			} elseif ( in_array( $key, self::NUMERIC_KEYS, true ) ) {
				$query_vars[ $key ] = trim( $value );
			}
		}

		ksort( $query_vars );

		return $query_vars;
	}

	/**
	 * Whether a param key carries a comma-separated multi-value.
	 *
	 * @param string $key Param key.
	 * @return bool
	 */
	private static function is_multi_value_param( string $key ): bool {
		if ( in_array( $key, self::MULTI_VALUE_EXACT_KEYS, true ) ) {
			return true;
		}

		foreach ( self::MULTI_VALUE_PREFIXES as $prefix ) {
			if ( str_starts_with( $key, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a param key carries a single string value.
	 *
	 * @param string $key Param key.
	 * @return bool
	 */
	private static function is_single_value_param( string $key ): bool {
		foreach ( self::SINGLE_VALUE_PREFIXES as $prefix ) {
			if ( str_starts_with( $key, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalise a comma-separated value string.
	 *
	 * Each item is trimmed, lowercased, and the list is sorted alphabetically
	 * before being reassembled.
	 *
	 * @param string $value Comma-separated values.
	 * @return string Normalised comma-separated values.
	 */
	private static function normalize_multi_value( string $value ): string {
		$values = explode( ',', $value );
		$values = array_map( 'trim', $values );
		$values = array_map( 'strtolower', $values );
		$values = array_filter( $values );
		sort( $values );

		return implode( ',', $values );
	}
}
