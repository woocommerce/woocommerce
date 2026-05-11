<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

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
	 * TTL (in seconds) for OPcache cache files.
	 */
	public const FILE_TTL = 7 * DAY_IN_SECONDS;

	/**
	 * Delete OPcache cache files older than {@see self::FILE_TTL}.
	 *
	 * AST contents are a pure function of the query, so this is a disk-usage
	 * bound, not a correctness concern. Returns the count.
	 */
	public static function delete_expired_files(): int {
		$dir = QueryCache::get_opcache_cache_dir();
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return 0;
		}

		$fs = self::wp_filesystem();
		if ( ! $fs ) {
			return 0;
		}

		$files = glob( $dir . '/*.php' );
		if ( false === $files ) {
			return 0;
		}

		$cutoff = time() - self::FILE_TTL;
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
	 * otherwise.
	 *
	 * @internal
	 */
	public static function handle_cleanup_action(): void {
		$interval = self::delete_expired_files() > 0 ? 1 : DAY_IN_SECONDS;

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $interval, self::ACTION_HOOK, array(), self::ACTION_GROUP );
		}
	}

	/**
	 * Schedule the cleanup if it isn't already scheduled.
	 *
	 * Called from {@see QueryCache::write_to_opcache()} so the first run is
	 * triggered by the first write — no separate bootstrap step.
	 */
	public static function ensure_scheduled(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}
		if ( as_has_scheduled_action( self::ACTION_HOOK, array(), self::ACTION_GROUP ) ) {
			return;
		}
		as_schedule_single_action( time() + DAY_IN_SECONDS, self::ACTION_HOOK, array(), self::ACTION_GROUP );
	}

	/**
	 * Lazy-initialize WP_Filesystem; null if the direct method isn't available.
	 */
	private static function wp_filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! WP_Filesystem() ) {
				return null;
			}
		}
		return $wp_filesystem;
	}
}
