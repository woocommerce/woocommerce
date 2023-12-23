<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Internal\Admin\Logging\{ PageController, Settings };

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
}
