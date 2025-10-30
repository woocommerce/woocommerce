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
	 * @var LegacyProxy|null
	 */
	private ?LegacyProxy $legacy_proxy = null;

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
	 * If no version exists and $generate is true, a new version will be created.
	 * If no version exists and $generate is false, null will be returned.
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

		if ( ! $found ) {
			if ( ! $generate ) {
				return null;
			}
			$version = $this->generate_version( $id );
		} else {
			// Refresh the cache lifetime.
			$this->store_version( $id, $version );
		}
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

		return wp_cache_set( $cache_key, $version, self::CACHE_GROUP, $ttl );
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
		return wp_cache_delete( $cache_key, self::CACHE_GROUP );
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
