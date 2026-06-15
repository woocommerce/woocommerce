<?php
/**
 * MultiCurrencyDatabaseCache class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;

/**
 * Database-backed cache for the native multi-currency runtime.
 *
 * Uses the same option payload shape as WooPayments so cached currency data can
 * survive ownership flips between plugin and native runtimes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyDatabaseCache implements MultiCurrencyCacheInterface {

	/**
	 * In-request cache for option payloads.
	 *
	 * @var array<string,mixed>
	 */
	private array $in_memory_cache = array();

	/**
	 * Get a value from cache.
	 *
	 * @param string $key   Cache key.
	 * @param bool   $force Whether to return cached data without checking expiry.
	 * @return mixed|null
	 */
	public function get( string $key, bool $force = false ) {
		$cache_contents = $this->get_from_cache( $key );

		if ( is_array( $cache_contents ) && array_key_exists( 'data', $cache_contents ) ) {
			if ( ! $force && $this->is_expired( $key, $cache_contents ) ) {
				return null;
			}

			return $cache_contents['data'];
		}

		return null;
	}

	/**
	 * Get a value from cache or regenerate and store it.
	 *
	 * @param string   $key           Cache key.
	 * @param callable $generator     Regenerates missing data.
	 * @param callable $validate_data Validates cached data.
	 * @param bool     $force_refresh Whether to force regeneration.
	 * @param bool     $refreshed     Set true when cache is refreshed successfully.
	 * @return mixed|null
	 */
	public function get_or_add( string $key, callable $generator, callable $validate_data, bool $force_refresh = false, bool &$refreshed = false ) {
		$cache_contents = $this->get_from_cache( $key );
		$data           = null;
		$old_data       = null;

		if ( is_array( $cache_contents ) && array_key_exists( 'data', $cache_contents ) && $validate_data( $cache_contents['data'] ) ) {
			$data     = $cache_contents['data'];
			$old_data = $data;
		}

		if ( $this->should_refresh_cache( $key, $cache_contents, $validate_data, $force_refresh ) ) {
			try {
				$data    = $generator();
				$errored = false === $data || null === $data;
			} catch ( \Throwable $e ) {
				$errored = true;
			}

			$refreshed = ! $errored;

			if ( $errored ) {
				$data = $old_data;
			}

			$this->write_to_cache( $key, $data, $errored );
		}

		return $data;
	}

	/**
	 * Delete a cache value.
	 *
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): void {
		unset( $this->in_memory_cache[ $key ] );

		delete_option( $key );
		wp_cache_delete( $key, 'options' );
	}

	/**
	 * Determine whether the cache should be refreshed.
	 *
	 * @param string   $key            Cache key.
	 * @param mixed    $cache_contents Stored cache payload.
	 * @param callable $validate_data  Data validation callback.
	 * @param bool     $force_refresh  Whether to force regeneration.
	 * @return bool
	 */
	private function should_refresh_cache( string $key, $cache_contents, callable $validate_data, bool $force_refresh ): bool {
		if ( $force_refresh ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) || wp_doing_ajax() ) {
			return false;
		}

		if ( false === $cache_contents ) {
			return true;
		}

		if (
			! is_array( $cache_contents )
			|| empty( $cache_contents )
			|| ! array_key_exists( 'data', $cache_contents )
			|| ! isset( $cache_contents['fetched'] )
			|| ! array_key_exists( 'errored', $cache_contents )
		) {
			return true;
		}

		if ( ! $cache_contents['errored'] && ! $validate_data( $cache_contents['data'] ) ) {
			return true;
		}

		return $this->is_expired( $key, $cache_contents );
	}

	/**
	 * Read the raw cache payload.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	private function get_from_cache( string $key ) {
		if ( array_key_exists( $key, $this->in_memory_cache ) ) {
			return $this->in_memory_cache[ $key ];
		}

		$data                          = get_option( $key );
		$this->in_memory_cache[ $key ] = $data;

		return $data;
	}

	/**
	 * Store the raw cache payload.
	 *
	 * @param string $key     Cache key.
	 * @param mixed  $data    Cache data.
	 * @param bool   $errored Whether regeneration failed.
	 */
	private function write_to_cache( string $key, $data, bool $errored ): void {
		$consecutive_errors = 0;

		if ( $errored ) {
			$previous           = $this->get_from_cache( $key );
			$previous_count     = is_array( $previous ) && isset( $previous['consecutive_errors'] )
				? (int) $previous['consecutive_errors']
				: 0;
			$consecutive_errors = $previous_count + 1;
		}

		$cache_contents = array(
			'data'               => $data,
			'fetched'            => time(),
			'errored'            => $errored,
			'consecutive_errors' => $consecutive_errors,
		);

		$this->in_memory_cache[ $key ] = $cache_contents;

		if ( false !== update_option( $key, $cache_contents, false ) ) {
			wp_cache_delete( $key, 'options' );
		}
	}

	/**
	 * Tell whether the raw cache payload is expired.
	 *
	 * @param string              $key            Cache key.
	 * @param array<string,mixed> $cache_contents Stored cache payload.
	 * @return bool
	 */
	private function is_expired( string $key, array $cache_contents ): bool {
		$ttl     = $this->get_ttl( $key, $cache_contents );
		$fetched = isset( $cache_contents['fetched'] ) ? (int) $cache_contents['fetched'] : 0;

		return $fetched + $ttl < time();
	}

	/**
	 * Get the cache TTL for a payload.
	 *
	 * @param string              $key            Cache key.
	 * @param array<string,mixed> $cache_contents Stored cache payload.
	 * @return int
	 */
	private function get_ttl( string $key, array $cache_contents ): int {
		if ( self::CURRENCIES_KEY === $key ) {
			if ( defined( 'DOING_CRON' ) || is_admin() ) {
				return ! empty( $cache_contents['errored'] )
					? $this->get_errored_ttl( (int) ( $cache_contents['consecutive_errors'] ?? 0 ) )
					: 3 * HOUR_IN_SECONDS;
			}

			return 12 * HOUR_IN_SECONDS;
		}

		return DAY_IN_SECONDS;
	}

	/**
	 * Map consecutive cache errors to a retry TTL.
	 *
	 * @param int $consecutive_errors Number of consecutive errors.
	 * @return int
	 */
	private function get_errored_ttl( int $consecutive_errors ): int {
		$ladder = array(
			2 * MINUTE_IN_SECONDS,
			5 * MINUTE_IN_SECONDS,
			10 * MINUTE_IN_SECONDS,
			15 * MINUTE_IN_SECONDS,
		);

		$index = max( 0, min( count( $ladder ) - 1, $consecutive_errors - 1 ) );

		return $ladder[ $index ];
	}
}
