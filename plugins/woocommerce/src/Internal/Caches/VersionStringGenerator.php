<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Version string generator/cache class.
 *
 * Provides a generic mechanism for generating and caching unique version strings
 * for any identifiable item. Each item is identified by a string ID, and has
 * an associated version string (UUID) that can be regenerated to invalidate caches.
 * This is useful for cache invalidation strategies where items change over time.
 * The standard WordPress cache is used to store the version strings.
 */
class VersionStringGenerator {

	/**
	 * Cache group name.
	 */
	private const CACHE_GROUP = 'woocommerce_version_strings';

	/**
	 * Can the version string cache be used?
	 *
	 * @var bool|null
	 */
	private ?bool $can_use = null;

	/**
	 * Legacy proxy instance.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class dependencies.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Legacy proxy instance.
	 */
	final public function init( LegacyProxy $legacy_proxy ) {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Tells whether the version string cache can be used or not.
	 *
	 * This will return true only if an external object cache is configured in WordPress,
	 * since otherwise the cached entries will only persist for the current request.
	 *
	 * @return bool
	 */
	public function can_use(): bool {
		if ( ! is_null( $this->can_use ) ) {
			return $this->can_use;
		}

		$this->can_use = $this->legacy_proxy->call_function( 'wp_using_ext_object_cache' ) ?? false;

		return $this->can_use;
	}

	/**
	 * Get the current version string for an ID.
	 *
	 * If no valid version exists and $generate is true, a new version will be created.
	 * If no valid version exists and $generate is false, null will be returned.
	 *
	 * Cached values that aren't non-empty strings are treated as invalid and are never
	 * returned. When $generate is true they are overwritten by the newly generated
	 * version; when $generate is false they are deleted, which means a call with
	 * $generate set to false can write to the cache.
	 *
	 * @param string $id       The ID to get the version string for.
	 * @param bool   $generate Whether to generate a new version if one doesn't exist. Default true.
	 * @return string|null Version string, or null if not found and $generate is false.
	 * @throws \InvalidArgumentException If id is invalid.
	 *
	 * @since 10.4.0
	 */
	public function get_version( string $id, bool $generate = true ): ?string {
		$this->validate_input( $id );

		$cache_key = $this->get_cache_key( $id );
		$found     = false;
		$version   = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

		if ( ! is_string( $version ) || '' === $version ) {
			$entry_exists = $this->cache_entry_exists( $version, $found );

			if ( $entry_exists ) {
				$this->log_invalid_cached_value( $id, $version, $generate );
			}

			if ( $generate ) {
				// The new version is written to the same cache key, replacing the invalid
				// value, so deleting it first would only add a redundant round-trip.
				return $this->generate_version( $id );
			}

			if ( $entry_exists ) {
				wp_cache_delete( $cache_key, self::CACHE_GROUP );
			}

			return null;
		}

		// Refresh the cache lifetime.
		$this->store_version( $id, $version );
		return $version;
	}

	/**
	 * Generate and store a new version string for an ID.
	 * The already existing version string, if any, will be replaced.
	 *
	 * @param string $id The ID to generate a version string for.
	 * @return string The new version string.
	 * @throws \InvalidArgumentException If id is invalid.
	 *
	 * @since 10.4.0
	 */
	public function generate_version( string $id ): string {
		$this->validate_input( $id );

		$version = wp_generate_uuid4();
		$this->store_version( $id, $version );
		return $version;
	}

	/**
	 * Store the version string in cache with a filterable TTL.
	 *
	 * @param string $id      The ID to store the version string for.
	 * @param string $version The version string to store.
	 * @return bool True on success, false on failure.
	 */
	protected function store_version( string $id, string $version ): bool {
		$cache_key = $this->get_cache_key( $id );

		/**
		 * Filter the TTL for version string cache.
		 *
		 * @param int    $ttl Time to live in seconds. Default 1 day.
		 * @param string $id  The ID.
		 *
		 * @since 10.4.0
		 */
		$ttl = apply_filters( 'woocommerce_version_string_generator_ttl', DAY_IN_SECONDS, $id );
		$ttl = max( 0, (int) $ttl );

		$result = wp_cache_set( $cache_key, $version, self::CACHE_GROUP, $ttl );

		if ( is_bool( $result ) ) {
			return $result;
		}

		// Some object cache implementations may return non-boolean values.
		// Verify the store by reading the value back.
		$found        = false;
		$stored_value = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( $stored_value === $version ) {
			return true;
		}

		// The stored value doesn't match; clean up and report failure.
		if ( $this->cache_entry_exists( $stored_value, $found ) ) {
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
		}
		return false;
	}

	/**
	 * Tell whether a value read from the cache is evidence that an entry is stored.
	 *
	 * Object cache drop-ins replace wp_cache_get() wholesale, so neither the returned
	 * value nor the found flag is trustworthy on its own: some drop-ins never populate
	 * $found, and some signal a miss with null rather than false. The value is therefore
	 * the primary evidence, with $found as a tie-breaker that tells a stored false apart
	 * from a genuine miss. $found is checked loosely because a drop-in may report it as a
	 * truthy non-boolean.
	 *
	 * @param mixed $value The value returned by wp_cache_get().
	 * @param mixed $found The found flag as populated by wp_cache_get(), if it populates it at all.
	 * @return bool True if an entry appears to be stored, false if this looks like a miss.
	 */
	private function cache_entry_exists( $value, $found ): bool {
		return (bool) $found || ( false !== $value && null !== $value );
	}

	/**
	 * Log a value found in the version string cache that isn't a usable version string.
	 *
	 * This should never happen with a well-behaved object cache, so surface it for
	 * diagnosis rather than silently self-healing.
	 *
	 * @param string $id           The ID the invalid value was cached for.
	 * @param mixed  $value        The invalid cached value.
	 * @param bool   $regenerating Whether a replacement version is being generated.
	 * @return void
	 */
	private function log_invalid_cached_value( string $id, $value, bool $regenerating ): void {
		$this->legacy_proxy->call_function( 'wc_get_logger' )->warning(
			sprintf(
				'Discarded an invalid version string cache entry for ID "%1$s" (got %2$s); %3$s.',
				$id,
				gettype( $value ),
				$regenerating ? 'the version will be regenerated' : 'the entry will be deleted'
			),
			array( 'source' => 'version-string-generator' )
		);
	}

	/**
	 * Delete the version string for an ID by deleting its cached entry.
	 *
	 * @param string $id The ID to delete the version string for.
	 * @return bool True on success, false on failure.
	 * @throws \InvalidArgumentException If id is invalid.
	 *
	 * @since 10.4.0
	 */
	public function delete_version( string $id ): bool {
		$this->validate_input( $id );

		$cache_key = $this->get_cache_key( $id );
		$result    = wp_cache_delete( $cache_key, self::CACHE_GROUP );

		// Some object cache implementations may return non-boolean values.
		return ! is_bool( $result ) || $result;
	}

	/**
	 * Get the cache key for an ID.
	 *
	 * The ID is hashed to ensure a consistent key length and avoid issues
	 * with special characters or very long IDs.
	 *
	 * @param string $id The ID to get the cache key for.
	 * @return string The cache key.
	 */
	private function get_cache_key( string $id ): string {
		return 'wc_version_string_' . md5( $id );
	}

	/**
	 * Validate ID input.
	 *
	 * @param string $id The ID to validate.
	 * @return void
	 * @throws \InvalidArgumentException If id is invalid.
	 */
	private function validate_input( string $id ): void {
		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'ID cannot be empty.' );
		}
	}
}
