<?php
/**
 * Memory Manager class.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog;

defined( 'ABSPATH' ) || exit;

/**
 * Helper class for managing memory during catalog generation.
 *
 * @package WooCommerce\ProductCatalog
 */
class MemoryManager {
	/**
	 * Get available memory as a percentage of the total memory limit.
	 *
	 * @return int Available memory as a percentage of the total memory limit.
	 */
	public static function get_available_memory(): int {
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		if ( -1 === $memory_limit ) {
			// Some systems have "unlimited" memory.
			// We should treat that as if there is none left.
			return 0;
		}
		return (int) round( 100 - ( memory_get_usage( true ) / $memory_limit ) * 100 );
	}

	/**
	 * Flush all caches.
	 */
	public static function flush_caches(): void {
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array();

		wp_cache_flush();

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		// This method is specific to certain memcached implementations.
		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset(); // important.
		}

		self::collect_garbage();
	}

	/**
	 * Collect garbage.
	 */
	public static function collect_garbage(): void {
		static $gc_threshold         = 5000;
		static $gc_too_low_in_a_row  = 0;
		static $gc_too_high_in_a_row = 0;

		$gc_threshold_step = 2500;
		$gc_status         = gc_status();

		if ( $gc_threshold > $gc_status['threshold'] ) {
			// If PHP managed to collect memory in the meantime and established threshold lower than ours, just use theirs.
			$gc_threshold = $gc_status['threshold'];
		}

		if ( $gc_status['roots'] > $gc_threshold ) {
			$collected = gc_collect_cycles();
			if ( $collected < 100 ) {
				if ( $gc_too_low_in_a_row > 0 ) {
					$gc_too_low_in_a_row = 0;
					// Raise GC threshold if we collected too little twice in a row.
					$gc_threshold += $gc_threshold_step;
					$gc_threshold  = min( $gc_threshold, 1000000000, $gc_status['threshold'] );
				} else {
					++$gc_too_low_in_a_row;
				}
				$gc_too_high_in_a_row = 0;
			} else {
				if ( $gc_too_high_in_a_row > 0 ) {
					$gc_too_high_in_a_row = 0;
					// Lower GC threshold if we collected more than enough twice in a row.
					$gc_threshold -= $gc_threshold_step;
					$gc_threshold  = max( $gc_threshold, 5000 );
				} else {
					++$gc_too_high_in_a_row;
				}
				$gc_too_low_in_a_row = 0;
			}
		}
	}
}
