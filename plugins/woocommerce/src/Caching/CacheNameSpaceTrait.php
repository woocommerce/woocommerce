<?php

namespace Automattic\WooCommerce\Caching;

/**
 * Implements namespacing algorithm to simulate grouping and namespacing for wp_cache, memcache and other caching engines that don't support grouping natively.
 *
 * See the algorithm details here: https://github.com/memcached/memcached/wiki/ProgrammingTricks#namespacing.
 *
 * To use the namespacing algorithm in the CacheEngine class:
 * 1. Use a group string to identify all objects of a type.
 * 2. Before setting cache, prefix the cache key by using the `get_cache_prefix`.
 * 3. Use `invalidate_cache_group` function to invalidate all caches in entire group at once.
 */
trait CacheNameSpaceTrait {

	/**
	 * Cache prefixes that have already been validated in this request.
	 *
	 * @var array<string,string>
	 */
	private static $validated_cache_prefixes = array();

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string Prefix.
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed.
		$cache_key = 'wc_' . $group . '_cache_prefix';
		$found     = false;
		$prefix    = wp_cache_get( $cache_key, $group, false, $found );

		if (
			$found
			&& isset( self::$validated_cache_prefixes[ $group ] )
			&& self::$validated_cache_prefixes[ $group ] === $prefix
		) {
			return 'wc_cache_' . $prefix . '_';
		}

		if ( ! $found || ! self::is_valid_cache_prefix( $prefix ) ) {
			$prefix = self::regenerate_cache_prefix( $cache_key, $group, $found );
		}

		self::$validated_cache_prefixes[ $group ] = $prefix;

		return 'wc_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param string $group Group of cache to clear.
	 */
	public static function incr_cache_prefix( $group ) {
		wc_deprecated_function( 'WC_Cache_Helper::incr_cache_prefix', '3.9.0', 'WC_Cache_Helper::invalidate_cache_group' );
		self::invalidate_cache_group( $group );
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 3.9.0
	 */
	public static function invalidate_cache_group( $group ) {
		$prefix = self::generate_cache_prefix();
		$result = wp_cache_set( 'wc_' . $group . '_cache_prefix', $prefix, $group );

		if ( $result ) {
			self::$validated_cache_prefixes[ $group ] = $prefix;
		}

		return $result;
	}

	/**
	 * Helper method to get prefixed key.
	 *
	 * @param  string $key   Key to prefix.
	 * @param  string $group Group of cache to get.
	 *
	 * @return string Prefixed key.
	 */
	public static function get_prefixed_key( $key, $group ) {
		return self::get_cache_prefix( $group ) . $key;
	}

	/**
	 * Regenerate a cache prefix and prefer a concurrent writer's value when one exists.
	 *
	 * @param string $cache_key Cache key.
	 * @param string $group Cache group.
	 * @param bool   $found Whether an existing prefix was found.
	 * @return string Cache prefix.
	 *
	 * @since 10.9.0
	 */
	private static function regenerate_cache_prefix( $cache_key, $group, $found ) {
		$prefix = self::generate_cache_prefix();

		if ( ! $found && wp_cache_add( $cache_key, $prefix, $group ) ) {
			return $prefix;
		}

		$cached_prefix = wp_cache_get( $cache_key, $group );

		if ( self::is_valid_cache_prefix( $cached_prefix ) ) {
			return $cached_prefix;
		}

		return self::replace_invalid_cache_prefix( $cache_key, $group );
	}

	/**
	 * Replace an invalid cache prefix with one shared by concurrent regenerators.
	 *
	 * @param string $cache_key Cache key.
	 * @param string $group Cache group.
	 * @return string Cache prefix.
	 *
	 * @since 10.9.0
	 */
	private static function replace_invalid_cache_prefix( $cache_key, $group ) {
		$replacement_cache_key = $cache_key . '_replacement';
		$prefix                = self::generate_cache_prefix();

		if ( ! wp_cache_add( $replacement_cache_key, $prefix, $group, 10 ) ) {
			$replacement_prefix = wp_cache_get( $replacement_cache_key, $group );

			if ( self::is_valid_cache_prefix( $replacement_prefix ) ) {
				$prefix = $replacement_prefix;
			}
		}

		wp_cache_set( $cache_key, $prefix, $group );

		return $prefix;
	}

	/**
	 * Generate a cache-safe prefix value.
	 *
	 * @return string Cache prefix.
	 *
	 * @since 10.9.0
	 */
	private static function generate_cache_prefix() {
		return str_replace( ' ', '_', microtime() ) . '_' . bin2hex( random_bytes( 8 ) );
	}

	/**
	 * Check whether a cached prefix can safely be used in cache keys.
	 *
	 * @param mixed $prefix Cached prefix value.
	 * @return bool True if the prefix is valid.
	 *
	 * @since 10.9.0
	 */
	private static function is_valid_cache_prefix( $prefix ) {
		return is_string( $prefix ) && 1 === preg_match( '/^\d+\.\d+_\d+_[a-f0-9]{16}$/', $prefix );
	}
}
