<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Api\Infrastructure\Main;
use Automattic\WooCommerce\Api\Infrastructure\ResolverHelpers;

/**
 * Deletes expired OPcache cache files via Action Scheduler.
 */
class OpcacheFileExpiry {

	/**
	 * Action Scheduler hook name for the cleanup job.
	 */
	public const ACTION_HOOK = 'woocommerce_graphql_opcache_cleanup';

	/**
	 * Action Scheduler group for the cleanup job.
	 */
	public const ACTION_GROUP = 'woocommerce-graphql';

	/**
	 * Object-cache key used to short-circuit {@see self::ensure_scheduled()}.
	 */
	private const SCHEDULED_CACHE_KEY = 'graphql_opcache_cleanup_scheduled';

	/**
	 * Delete OPcache cache files older than {@see QueryCache::get_cache_ttl()}.
	 *
	 * AST contents are a pure function of the query, so this is a disk-usage
	 * bound, not a correctness concern. Returns the count.
	 */
	public static function delete_expired_files(): int {
		$dir = QueryCache::get_opcache_cache_dir();
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return 0;
		}

		$fs = ResolverHelpers::wp_filesystem();
		if ( ! $fs ) {
			return 0;
		}

		$files = glob( $dir . '/*.php' );
		if ( false === $files ) {
			return 0;
		}

		$cutoff = time() - QueryCache::get_cache_ttl();
		$count  = 0;
		foreach ( $files as $path ) {
			$mtime = $fs->mtime( $path );
			if ( false !== $mtime && $mtime < $cutoff && $fs->delete( $path ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Action Scheduler callback: delete expired files and reschedule.
	 *
	 * Immediate reschedule when files were deleted (drain the backlog), 24h
	 * otherwise. Skipped when the feature is disabled.
	 *
	 * @internal
	 */
	public static function handle_cleanup_action(): void {
		$interval = self::delete_expired_files() > 0 ? 1 : DAY_IN_SECONDS;

		if ( ! Main::is_enabled() ) {
			return;
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $interval, self::ACTION_HOOK, array(), self::ACTION_GROUP );
		}
	}

	/**
	 * Schedule the cleanup if it isn't already scheduled.
	 *
	 * Called from {@see QueryCache::write_to_opcache()} and
	 * {@see QueryCache::read_from_opcache()} so the cleanup is rescheduled
	 * after a feature-disable/re-enable cycle even when every request hits a
	 * cached file (no writes).
	 */
	public static function ensure_scheduled(): void {
		if ( wp_cache_get( self::SCHEDULED_CACHE_KEY, QueryCache::CACHE_GROUP ) ) {
			return;
		}

		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		if ( ! as_has_scheduled_action( self::ACTION_HOOK, array(), self::ACTION_GROUP ) ) {
			as_schedule_single_action( time() + DAY_IN_SECONDS, self::ACTION_HOOK, array(), self::ACTION_GROUP );
		}

		wp_cache_set( self::SCHEDULED_CACHE_KEY, true, QueryCache::CACHE_GROUP, HOUR_IN_SECONDS );
	}
}
