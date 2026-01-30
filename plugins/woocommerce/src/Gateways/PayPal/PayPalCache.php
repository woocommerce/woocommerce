<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

defined( 'ABSPATH' ) || exit;

/**
 * A class for caching data as an option in the database.
 *
 * @since 10.5.0
 */
class PayPalCache {

	/**
	 * In-memory cache for the duration of a single request.
	 *
	 * This is used to avoid multiple database reads for the same data.
	 *
	 * @var array
	 */
	private static $in_memory_cache = array();

	/**
	 * The prefix used for every cache key.
	 *
	 * @var string
	 */
	public const CACHE_KEY_PREFIX = 'wc_paypal_cache_';

	/**
	 * Class constructor.
	 */
	private function __construct() {
	}

	/**
	 * Stores a value in the cache.
	 *
	 * The key is automatically prefixed with "wc_paypal_cache_".
	 *
	 * @param string $key  The key to store the value under.
	 * @param mixed  $data The value to store.
	 * @param int    $ttl  The TTL of the cache in seconds. Default 1 hour.
	 *
	 * @return void
	 */
	public static function set( string $key, $data, int $ttl = HOUR_IN_SECONDS ): void {
		$prefixed_key = self::add_key_prefix( $key );
		self::write_to_cache( $prefixed_key, $data, $ttl );
	}

	/**
	 * Gets a value from the cache.
	 *
	 * The key is automatically prefixed with "wc_paypal_cache_".
	 *
	 * @param string $key The key to look for.
	 *
	 * @return mixed|null The cache contents. NULL if the cache value is expired or missing.
	 */
	public static function get( string $key ) {
		$prefixed_key   = self::add_key_prefix( $key );
		$cache_contents = self::get_from_cache( $prefixed_key );

		if ( is_array( $cache_contents ) && array_key_exists( 'data', $cache_contents ) ) {
			if ( self::is_expired( $cache_contents ) ) {
				return null;
			}

			return $cache_contents['data'];
		}

		return null;
	}

	/**
	 * Deletes a value from the cache.
	 *
	 * The key is automatically prefixed with "wc_paypal_cache_".
	 *
	 * @param string $key The key to delete.
	 *
	 * @return void
	 */
	public static function delete( string $key ): void {
		$prefixed_key = self::add_key_prefix( $key );
		self::delete_from_cache( $prefixed_key );
	}

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $prefixed_key The key to delete (with prefix).
	 *
	 * @return void
	 */
	private static function delete_from_cache( string $prefixed_key ): void {
		// Remove from the in-memory cache.
		unset( self::$in_memory_cache[ $prefixed_key ] );

		// Remove from the DB cache.
		if ( delete_option( $prefixed_key ) ) {
			// Clear the WP object cache to ensure the new data is fetched by other processes.
			wp_cache_delete( $prefixed_key, 'options' );
		}
	}

	/**
	 * Wraps the data in the cache metadata and stores it.
	 *
	 * @param string $prefixed_key The key to store the data under (with prefix).
	 * @param mixed  $data         The data to store.
	 * @param int    $ttl          The TTL of the cache.
	 *
	 * @return void
	 */
	private static function write_to_cache( string $prefixed_key, $data, int $ttl ): void {
		// Add the data and expiry time to the array we're caching.
		$cache_contents = array(
			'data'    => $data,
			'ttl'     => $ttl,
			'updated' => time(),
		);

		// Write the in-memory cache.
		self::$in_memory_cache[ $prefixed_key ] = $cache_contents;

		// Create or update the DB option cache.
		// Note: Since we are adding the current time to the option value, WP will ALWAYS write the option because
		// the cache contents value is different from the current one, even if the data is the same.
		// A `false` result ONLY means that the DB write failed.
		// Note 2: Autoloading too many options can lead to performance problems, so we set autoload to false.
		$result = update_option( $prefixed_key, $cache_contents, false );
		if ( false !== $result ) {
			// If the DB cache write succeeded, clear the WP object cache to ensure the new data is fetched by other processes.
			wp_cache_delete( $prefixed_key, 'options' );
		}
	}

	/**
	 * Get the cache contents for a certain key.
	 *
	 * @param string $prefixed_key The cache key (with prefix).
	 *
	 * @return array|false The cache contents (array with `data`, `ttl`, and `updated` entries).
	 *                     False if there is no cached data.
	 */
	public static function get_from_cache( string $prefixed_key ) {
		// Check the in-memory cache first.
		if ( isset( self::$in_memory_cache[ $prefixed_key ] ) ) {
			return self::$in_memory_cache[ $prefixed_key ];
		}

		// Read from the DB cache.
		$data = get_option( $prefixed_key );

		// Store the data in the in-memory cache, including the case when there is no data cached (`false`).
		self::$in_memory_cache[ $prefixed_key ] = $data;

		return $data;
	}

	/**
	 * Checks if the cache value is expired.
	 *
	 * @param array  $cache_contents The cache contents.
	 *
	 * @return bool True if the contents are expired. False otherwise.
	 */
	private static function is_expired( array $cache_contents ): bool {
		if ( ! is_array( $cache_contents ) ) {
			// Treat bad/invalid cache contents as expired.
			return true;
		}

		$expires = self::get_expiry_time( $cache_contents );
		if ( null === $expires ) {
			return true;
		}

		return time() > $expires;
	}

	/**
	 * Get the expiry time for a cache entry. Includes validation for time-related fields in the array.
	 *
	 * @param array $cache_contents The cache contents.
	 *
	 * @return int|null The expiry time as a timestamp. Null if the expiry time can't be determined.
	 */
	private static function get_expiry_time( array $cache_contents ): ?int {
		// If we don't have updated and ttl keys, expiry time is unknown.
		if ( ! isset( $cache_contents['updated'], $cache_contents['ttl'] ) ) {
			return null;
		}

		// If we don't have integers for updated and ttl, expiry time is unknown.
		if ( ! is_int( $cache_contents['updated'] ) || ! is_int( $cache_contents['ttl'] ) ) {
			return null;
		}

		return $cache_contents['updated'] + $cache_contents['ttl'];
	}

	/**
	 * Adds the CACHE_KEY_PREFIX to the key.
	 *
	 * @param string $key The key to add the prefix to.
	 *
	 * @return string The key with the prefix.
	 */
	private static function add_key_prefix( string $key ): string {
		return self::CACHE_KEY_PREFIX . $key;
	}
}
