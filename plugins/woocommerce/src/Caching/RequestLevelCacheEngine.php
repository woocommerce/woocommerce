<?php

namespace Automattic\WooCommerce\Caching;

/**
 * Implementation of CacheEngine that only stores objects in memory and will only persist for the duration of the request or process.
 *
 * $expiration is ignored.
 */
class RequestLevelCacheEngine implements CacheEngine {

	/**
	 * Multidimensional array containing the cached objects. Keyed by [ $blog_id ][ $group ][ $key ]
	 *
	 * @var array[][]|object[][]
	 */
	private $cache = array();

	/**
	 * Retrieves an object cached under a given key.
	 *
	 * @param string $key   They key under which the object to retrieve is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return array|object|null The cached object, or null if there's no object cached under the passed key.
	 */
	public function get_cached_object( string $key, string $group = '' ) {
		$blog_id = get_current_blog_id();
		if ( ! isset( $this->cache[ $blog_id ][ $group ][ $key ] ) ) {
			return null;
		}
		if ( is_object( $this->cache[ $blog_id ][ $group ][ $key ] ) ) {
			return clone $this->cache[ $blog_id ][ $group ][ $key ];
		}

		return $this->cache[ $blog_id ][ $group ][ $key ];
	}

	/**
	 * Caches an object under a given key
	 *
	 * @param string       $key        The key under which the object will be cached.
	 * @param array|object $object     The object to cache.
	 * @param int          $expiration Expiration for the cached object, in seconds. Ignored for this implementation.
	 * @param string       $group      The group under which the object will be cached.
	 *
	 * @return bool True if the object is cached successfully, false otherwise.
	 */
	public function cache_object( string $key, $object, int $expiration, string $group = '' ): bool {
		if ( '' === $key ) {
			return false;
		}
		$blog_id = get_current_blog_id();
		if ( ! isset( $this->cache[ $blog_id ] ) ) {
			$this->cache[ $blog_id ] = array();
		}
		if ( ! isset( $this->cache[ $blog_id ][ $group ] ) ) {
			$this->cache[ $blog_id ][ $group ] = array();
		}
		if ( is_object( $object ) ) {
			$object = clone $object;
		}
		$this->cache[ $blog_id ][ $group ][ $key ] = $object;

		return true;
	}


	/**
	 * Removes a cached object from the cache.
	 *
	 * @param string $key   They key under which the object is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool True if the object is removed from the cache successfully, false otherwise (because the object
	 *              wasn't cached or for other reason).
	 */
	public function delete_cached_object( string $key, string $group = '' ): bool {
		if ( '' === $key ) {
			return false;
		}
		$blog_id = get_current_blog_id();
		if ( ! isset( $this->cache[ $blog_id ][ $group ][ $key ] ) ) {
			return false;
		}
		unset( $this->cache[ $blog_id ][ $group ][ $key ] );

		return true;
	}

	/**
	 * Checks if an object is cached under a given key.
	 *
	 * @param string $key   The key to verify.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool True if there's an object cached under the given key, false otherwise.
	 */
	public function is_cached( string $key, string $group = '' ): bool {
		if ( '' === $key ) {
			return false;
		}
		$blog_id = get_current_blog_id();

		return isset( $this->cache[ $blog_id ][ $group ][ $key ] );
	}

	/**
	 * Deletes all cached objects under a given group.
	 *
	 * @param string $group The group to delete.
	 *
	 * @return bool True if the group is deleted successfully, false otherwise.
	 */
	public function delete_cache_group( string $group = '' ): bool {
		$blog_id = get_current_blog_id();
		if ( isset( $this->cache[ $blog_id ][ $group ] ) ) {
			unset( $this->cache[ $blog_id ][ $group ] );
		}

		return true;
	}
}
