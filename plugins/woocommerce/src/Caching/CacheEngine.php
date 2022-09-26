<?php

namespace Automattic\WooCommerce\Caching;

/**
 * Interface for cache engines used by objects inheriting from ObjectCache.
 * Here "object" means either an array or an actual PHP object.
 */
interface CacheEngine {

	/**
	 * Retrieves an object cached under a given key.
	 *
	 * @param string $key They key under which the object to retrieve is cached.
	 * @return array|object|null The cached object, or null if there's no object cached under the passed key.
	 */
	public function get_cached_object( string $key);

	/**
	 * Caches an object under a given key, and with a given expiration.
	 *
	 * @param string       $key The key under which the object will be cached.
	 * @param array|object $object The object to cache.
	 * @param int          $expiration Expiration for the cached object, in seconds.
	 * @return bool True if the object is cached successfully, false otherwise.
	 */
	public function cache_object( string $key, $object, int $expiration): bool;

	/**
	 * Removes a cached object from the cache.
	 *
	 * @param string $key They key under which the object is cached.
	 * @return bool True if the object is removed from the cache successfully, false otherwise (because the object wasn't cached or for other reason).
	 */
	public function delete_cached_object( string $key): bool;

	/**
	 * Checks if an object is cached under a given key.
	 *
	 * @param string $key The key to verify.
	 * @return bool True if there's an object cached under the given key, false otherwise.
	 */
	public function is_cached( string $key): bool;
}
