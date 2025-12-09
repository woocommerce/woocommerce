<?php
/**
 * Memory Manager class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for managing memory.
 *
 * @since 10.5.0
 */
class MemoryManager {
	/**
	 * Get available memory as a percentage of the total memory limit.
	 *
	 * @since 10.5.0
	 *
	 * @return int Available memory as a percentage of the total memory limit.
	 */
	public function get_available_memory(): int {
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		if ( 0 >= $memory_limit ) {
			// Some systems have "unlimited" memory.
			// We should treat that as if there is none left.
			return 0;
		}
		return (int) round( 100 - ( memory_get_usage( true ) / $memory_limit ) * 100 );
	}

	/**
	 * Flush all caches.
	 *
	 * @since 10.5.0
	 */
	public function flush_caches(): void {
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array();

		wp_cache_flush();

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		// These properties exist on various object cache implementations.
		$wp_object_cache->group_ops      = array(); // @phpstan-ignore property.notFound
		$wp_object_cache->stats          = array(); // @phpstan-ignore property.notFound
		$wp_object_cache->memcache_debug = array(); // @phpstan-ignore property.notFound
		$wp_object_cache->cache          = array(); // @phpstan-ignore property.notFound

		// This method is specific to certain memcached implementations.
		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset(); // important.
		}

		$this->collect_garbage();
	}

	/**
	 * Collect garbage.
	 */
	private function collect_garbage(): void {
		static $gc_threshold         = 5000;
		static $gc_too_low_in_a_row  = 0;
		static $gc_too_high_in_a_row = 0;

		$gc_threshold_step = 2_500;
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
					$gc_threshold  = min( $gc_threshold, 1_000_000_000, $gc_status['threshold'] );
				} else {
					++$gc_too_low_in_a_row;
				}
				$gc_too_high_in_a_row = 0;
			} else {
				if ( $gc_too_high_in_a_row > 0 ) {
					$gc_too_high_in_a_row = 0;
					// Lower GC threshold if we collected more than enough twice in a row.
					$gc_threshold -= $gc_threshold_step;
					$gc_threshold  = max( $gc_threshold, 5_000 );
				} else {
					++$gc_too_high_in_a_row;
				}
				$gc_too_low_in_a_row = 0;
			}
		}
	}
}
