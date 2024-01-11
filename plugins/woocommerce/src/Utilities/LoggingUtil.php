<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Internal\Admin\Logging\{ PageController, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;

/**
 * A class of utilities for dealing with logging.
 */
final class LoggingUtil {
	/**
	 * Get the canonical URL for the Logs tab of the Status admin page.
	 *
	 * @return string
	 */
	public static function get_logs_tab_url(): string {
		return wc_get_container()->get( PageController::class )->get_logs_tab_url();
	}

	/**
	 * Determine the current value of the logging_enabled setting.
	 *
	 * @return bool
	 */
	public static function logging_is_enabled(): bool {
		return wc_get_container()->get( Settings::class )->logging_is_enabled();
	}

	/**
	 * Determine the current value of the default_handler setting.
	 *
	 * @return string
	 */
	public static function get_default_handler(): string {
		return wc_get_container()->get( Settings::class )->get_default_handler();
	}

	/**
	 * Determine the current value of the retention_period_days setting.
	 *
	 * @return int
	 */
	public static function get_retention_period(): int {
		return wc_get_container()->get( Settings::class )->get_retention_period();
	}

	/**
	 * Determine the current value of the level_threshold setting.
	 *
	 * @return string
	 */
	public static function get_level_threshold(): string {
		return wc_get_container()->get( Settings::class )->get_level_threshold();
	}

	/**
	 * Generate a public ID for a log file based on its properties.
	 *
	 * The file ID is the basename of the file without the hash part. It allows us to identify a file without revealing
	 * its full name in the filesystem, so that it's difficult to access the file directly with an HTTP request.
	 *
	 * @param string   $source   The source of the log entries contained in the file.
	 * @param int|null $rotation Optional. The 0-based incremental rotation marker, if the file has been rotated.
	 *                           Should only be a single digit.
	 * @param int      $created  Optional. The date the file was created, as a Unix timestamp.
	 *
	 * @return string
	 */
	public static function generate_log_file_id( string $source, ?int $rotation = null, int $created = 0 ): string {
		return File::generate_file_id( $source, $rotation, $created );
	}

	/**
	 * Generate a hash to use as the suffix on a log filename.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return string
	 */
	public static function generate_log_file_hash( string $file_id ): string {
		return File::generate_hash( $file_id );
	}
}
