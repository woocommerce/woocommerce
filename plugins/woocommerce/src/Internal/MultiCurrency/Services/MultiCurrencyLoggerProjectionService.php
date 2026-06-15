<?php
/**
 * MultiCurrencyLoggerProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency logger calls without writing logs.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyLoggerProjectionService {

	private const LOG_SOURCE       = 'woopayments-multi-currency';
	private const SUPPORTED_LEVELS = array( 'debug', 'error', 'notice' );

	/**
	 * Project the multi-currency log source.
	 *
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_log_source(): string {
		return self::LOG_SOURCE;
	}

	/**
	 * Project supported public log levels.
	 *
	 * @return array<int,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_supported_levels(): array {
		return self::SUPPORTED_LEVELS;
	}

	/**
	 * Project runtime blockers for logger availability.
	 *
	 * @param bool $wc_logger_available Whether the WooCommerce logger is available.
	 * @return array<int,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_runtime_blockers( bool $wc_logger_available ): array {
		return $wc_logger_available ? array() : array( 'wc_logger_unavailable' );
	}

	/**
	 * Project a logger call manifest.
	 *
	 * @param string $level               Log level.
	 * @param string $message             Log message.
	 * @param bool   $wc_logger_available Whether the WooCommerce logger is available.
	 * @return array{should_log: bool, level: string, message: string, context: array{source: string}, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_log_manifest( string $level, string $message, bool $wc_logger_available ): array {
		$blockers = self::get_runtime_blockers( $wc_logger_available );
		if ( ! in_array( $level, self::SUPPORTED_LEVELS, true ) ) {
			$blockers[] = 'unsupported_level';
		}

		return array(
			'should_log' => array() === $blockers,
			'level'      => $level,
			'message'    => $message,
			'context'    => array(
				'source' => self::LOG_SOURCE,
			),
			'blockers'   => $blockers,
		);
	}

	/**
	 * Project a debug log call.
	 *
	 * @param string $message             Log message.
	 * @param bool   $wc_logger_available Whether the WooCommerce logger is available.
	 * @return array{should_log: bool, level: string, message: string, context: array{source: string}, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_debug_manifest( string $message, bool $wc_logger_available ): array {
		return self::get_log_manifest( 'debug', $message, $wc_logger_available );
	}

	/**
	 * Project an error log call.
	 *
	 * @param string $message             Log message.
	 * @param bool   $wc_logger_available Whether the WooCommerce logger is available.
	 * @return array{should_log: bool, level: string, message: string, context: array{source: string}, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_error_manifest( string $message, bool $wc_logger_available ): array {
		return self::get_log_manifest( 'error', $message, $wc_logger_available );
	}

	/**
	 * Project a notice log call.
	 *
	 * @param string $message             Log message.
	 * @param bool   $wc_logger_available Whether the WooCommerce logger is available.
	 * @return array{should_log: bool, level: string, message: string, context: array{source: string}, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_notice_manifest( string $message, bool $wc_logger_available ): array {
		return self::get_log_manifest( 'notice', $message, $wc_logger_available );
	}
}
