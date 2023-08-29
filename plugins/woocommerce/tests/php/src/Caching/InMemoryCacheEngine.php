<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Implementation of the CacheEngine interface that stores data in an associative array.
 *
 * To use, add this in the setUp method of the unit tests class:
 *
 * add_filter('wc_object_cache_get_engine', fn()=>new InMemoryCacheEngine());
 *
 * And in the tearDown method:
 *
 * remove_all_filters('wc_object_cache_get_engine');
 *
 * Alternatively, you can override the get_cache_engine_instance method in the cache class.
 *
 */
class InMemoryCacheEngine implements CacheEngine {

	/**
	 * The array used to store the cached data. Keys are group names,
	 * values are associative arrays having the cached object ids as keys.
	 *
	 * @var array
	 */
	public array $cache = array();

	// phpcs:disable Squiz.Commenting

	public function get_cached_object( string $key, string $group = '' ) {
		ArrayUtil::ensure_key_is_array( $this->cache, $group, true );
		return $this->cache[ $group ][ $key ] ?? null;
	}

	public function cache_object( string $key, $object, int $expiration, string $group = '' ): bool {
		ArrayUtil::ensure_key_is_array( $this->cache, $group, true );
		$this->cache[ $group ][ $key ] = $object;
		return true;
	}

	public function delete_cached_object( string $key, string $group = '' ): bool {
		ArrayUtil::ensure_key_is_array( $this->cache, $group, true );
		if ( array_key_exists( $key, $this->cache[ $group ] ) ) {
			unset( $this->cache[ $group ][ $key ] );
			return true;
		}

		return false;
	}

	public function is_cached( string $key, string $group = '' ): bool {
		ArrayUtil::ensure_key_is_array( $this->cache, $group, true );
		return array_key_exists( $key, $this->cache[ $group ] );
	}

	public function delete_cache_group( string $group = '' ): bool {
		if ( array_key_exists( $group, $this->cache ) ) {
			unset( $this->cache[ $group ] );
		}
		return true;
	}

	// phpcs:enable Squiz.Commenting
};
