<?php
/**
 * Stub file for the salted object cache helpers added in WordPress 6.9.
 *
 * php-stubs/wordpress-stubs is pinned to 6.8.3, which predates these functions, so PHPStan
 * reports them as undefined. WooCommerce requires WordPress 7.0, and WordPress declares both
 * unconditionally in wp-includes/cache-compat.php, so they are always available at runtime.
 *
 * Delete this file and its phpstan.neon scanFiles entry once wordpress-stubs is updated
 * past 6.9.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_cache_get_salted/
 * @see https://developer.wordpress.org/reference/functions/wp_cache_set_salted/
 */

/**
 * Retrieves cached data if valid and unchanged.
 *
 * @param string          $cache_key The cache key used for storage and retrieval.
 * @param string          $group     The cache group used for organizing data.
 * @param string|string[] $salt      Timestamp, or timestamps, of when the cache group was last updated.
 * @return mixed|false The cached data if valid, or false if the cache does not exist or is outdated.
 */
function wp_cache_get_salted( $cache_key, $group, $salt ) {
}

/**
 * Stores salted data in the cache.
 *
 * @param string          $cache_key The cache key under which to store the data.
 * @param mixed           $data      The data to be cached.
 * @param string          $group     The cache group to which the data belongs.
 * @param string|string[] $salt      Timestamp, or timestamps, of when the cache group was last updated.
 * @param int             $expire    Optional. When to expire the cache contents, in seconds. Default 0.
 * @return bool True on success, false on failure.
 */
function wp_cache_set_salted( $cache_key, $data, $group, $salt, $expire = 0 ) {
}
