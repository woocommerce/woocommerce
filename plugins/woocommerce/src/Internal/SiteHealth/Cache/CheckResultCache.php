<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Transient-backed cache for Site Health async check results.
 *
 * @internal
 */
class CheckResultCache {

	private const DEFAULT_TTL = 6 * HOUR_IN_SECONDS;
	private const KEY_PREFIX  = 'woocommerce_site_health_';

	/**
	 * Get a cached result or run the factory and cache the result.
	 *
	 * @param string   $check_id The check ID (without `woocommerce_` prefix is fine).
	 * @param callable $factory  Callable that returns the result array.
	 * @return array
	 */
	public function remember( string $check_id, callable $factory ): array {
		$key    = $this->key( $check_id );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$result = $factory();
		if ( ! is_array( $result ) ) {
			throw new \TypeError(
				sprintf(
					'CheckResultCache factory for "%s" must return an array; got %s.',
					$check_id,
					get_debug_type( $result )
				)
			);
		}
		if ( ! empty( $result ) ) {
			set_transient( $key, $result, $this->ttl( $check_id ) );
		}
		return $result;
	}

	/**
	 * Delete the cached result for a check.
	 *
	 * @param string $check_id The check ID.
	 */
	public function forget( string $check_id ): void {
		delete_transient( $this->key( $check_id ) );
	}

	/**
	 * Build the transient key for a check.
	 *
	 * Embeds an md5 hash of the WC version so that cached results from a
	 * previous WooCommerce version are automatically bypassed (they will
	 * expire on their own and never be returned for the new version).
	 *
	 * @param string $check_id The check ID.
	 * @return string
	 */
	private function key( string $check_id ): string {
		$version = function_exists( 'WC' ) ? WC()->version : ( defined( 'WC_VERSION' ) ? WC_VERSION : '0' );
		return self::KEY_PREFIX . $check_id . '_' . md5( $version );
	}

	/**
	 * Get the TTL for a check, applying the filter.
	 *
	 * @param string $check_id The check ID.
	 * @return int TTL in seconds.
	 */
	private function ttl( string $check_id ): int {
		/**
		 * Filter the cache TTL (in seconds) for a Site Health check result.
		 *
		 * @since 10.9.0
		 *
		 * @param int    $ttl      TTL in seconds.
		 * @param string $check_id The check ID.
		 */
		return (int) apply_filters( "woocommerce_site_health_check_{$check_id}_cache_ttl", self::DEFAULT_TTL, $check_id );
	}
}
