<?php

namespace Automattic\WooCommerce\Caching;

/**
 * Implementation of CacheEngine that uses the built-in WordPress cache.
 */
class WPCacheEngine implements CacheEngine {
	use CacheNameSpaceTrait;

	/**
	 * Retrieves an object cached under a given key.
	 *
	 * @param string $key The key under which the object to retrieve is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return array|object|null The cached object, or null if there's no object cached under the passed key.
	 */
	public function get_cached_object( string $key, string $group = '' ) {
		$prefixed_key = self::get_prefixed_key( $key, $group );
		$value        = wp_cache_get( $prefixed_key, $group );
		return false === $value ? null : $value;
	}

	/**
	 * Retrieves a set of objects cached under the given keys.
	 *
	 * @param string[] $keys The keys under which the object to retrieve is cached.
	 * @param string   $group The group under which the object is cached.
	 *
	 * @return array The cached array of objects keyed by the given keys, values will be null for any non-cached keys.
	 */
	public function get_cached_objects( array $keys, string $group = '' ) {
		$prefix  = self::get_cache_prefix( $group );
		$key_map = array_combine(
			$keys,
			array_map(
				function ( $key ) use ( $prefix ) {
					return $prefix . $key;
				},
				$keys
			)
		);

		$cached_values = wp_cache_get_multiple( array_values( $key_map ), $group );
		$return_values = array();
		foreach ( $key_map as $key => $prefixed_key ) {
			if ( isset( $cached_values[ $prefixed_key ] ) && false !== $cached_values[ $prefixed_key ] ) {
				$return_values[ $key ] = $cached_values[ $prefixed_key ];
			} else {
				$return_values[ $key ] = null;
			}
		}

		return $return_values;
	}

	/**
	 * Caches an object under a given key, and with a given expiration.
	 *
	 * @param string       $key The key under which the object will be cached.
	 * @param array|object $object The object to cache.
	 * @param int          $expiration Expiration for the cached object, in seconds.
	 * @param string       $group The group under which the object will be cached.
	 *
	 * @return bool True if the object is cached successfully, false otherwise.
	 */
	public function cache_object( string $key, $object, int $expiration, string $group = '' ): bool {
		$prefixed_key = self::get_prefixed_key( $key, $group );
		return false !== wp_cache_set( $prefixed_key, $object, $group, $expiration );
	}

	/**
	 * Caches an object under a given key, and with a given expiration.
	 *
	 * @param array  $objects The objects to cache keyed by the key to cache under.
	 * @param int    $expiration Expiration for the cached object, in seconds.
	 * @param string $group The group under which the object will be cached.
	 *
	 * @return array Array of return values, grouped by key. Each value is either
	 *                true on success, or false on failure
	 */
	public function cache_objects( array $objects, int $expiration, string $group = '' ): array {
		$prefix = self::get_cache_prefix( $group );

		$objects = array_combine(
			array_map(
				function ( $key ) use ( $prefix ) {
					return $prefix . $key;
				},
				array_keys( $objects )
			),
			$objects,
		);

		return wp_cache_set_multiple( $objects, $group, $expiration );
	}

	/**
	 * Removes a cached object from the cache.
	 *
	 * @param string $key They key under which the object is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool True if the object is removed from the cache successfully, false otherwise (because the object wasn't cached or for other reason).
	 */
	public function delete_cached_object( string $key, string $group = '' ): bool {
		$prefixed_key = self::get_prefixed_key( $key, $group );
		return false !== wp_cache_delete( $prefixed_key, $group );
	}

	/**
	 * Checks if an object is cached under a given key.
	 *
	 * @param string $key The key to verify.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool True if there's an object cached under the given key, false otherwise.
	 */
	public function is_cached( string $key, string $group = '' ): bool {
		$prefixed_key = self::get_prefixed_key( $key, $group );
		return false !== wp_cache_get( $prefixed_key, $group );
	}

	/**
	 * Deletes all cached objects under a given group.
	 *
	 * @param string $group The group to delete.
	 *
	 * @return bool True if the group is deleted successfully, false otherwise.
	 */
	public function delete_cache_group( string $group = '' ): bool {
		return false !== self::invalidate_cache_group( $group );
	}
}
