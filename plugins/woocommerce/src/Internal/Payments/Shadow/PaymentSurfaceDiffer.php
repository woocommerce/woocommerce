<?php
/**
 * PaymentSurfaceDiffer class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Shadow;

/**
 * Diffs machine-readable payment surfaces using stable dot paths.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentSurfaceDiffer {

	/**
	 * Diff expected and actual payment surfaces.
	 *
	 * @param array<string,mixed> $expected Expected payment surface.
	 * @param array<string,mixed> $actual   Actual payment surface.
	 * @return array<string,array{expected:mixed,actual:mixed}>
	 */
	public function diff( array $expected, array $actual ): array {
		return $this->diff_recursive( $expected, $actual );
	}

	/**
	 * Recursively diff two arrays.
	 *
	 * @param array<mixed> $expected Expected array.
	 * @param array<mixed> $actual   Actual array.
	 * @param string       $prefix   Dot-path prefix.
	 * @return array<string,array{expected:mixed,actual:mixed}>
	 */
	private function diff_recursive( array $expected, array $actual, string $prefix = '' ): array {
		$diff = array();
		$keys = array_values( array_unique( array_merge( array_keys( $expected ), array_keys( $actual ) ) ) );
		sort( $keys );

		foreach ( $keys as $key ) {
			$expected_has = array_key_exists( $key, $expected );
			$actual_has   = array_key_exists( $key, $actual );
			$path         = '' === $prefix ? (string) $key : $prefix . '.' . $key;

			if ( $expected_has && $actual_has && is_array( $expected[ $key ] ) && is_array( $actual[ $key ] ) ) {
				$diff = array_merge( $diff, $this->diff_recursive( $expected[ $key ], $actual[ $key ], $path ) );
				continue;
			}

			if ( ! $expected_has || ! $actual_has || $expected[ $key ] !== $actual[ $key ] ) {
				$diff[ $path ] = array(
					'expected' => $expected_has ? $expected[ $key ] : null,
					'actual'   => $actual_has ? $actual[ $key ] : null,
				);
			}
		}

		return $diff;
	}
}
