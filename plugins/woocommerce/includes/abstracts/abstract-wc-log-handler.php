<?php
/**
 * Log handling functionality.
 *
 * @class WC_Log_Handler
 * @package WooCommerce\Abstracts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract WC Log Handler Class
 *
 * @version        1.0.0
 * @package        WooCommerce\Abstracts
 */
abstract class WC_Log_Handler implements WC_Log_Handler_Interface {

	/**
	 * Formats a timestamp for use in log messages.
	 *
	 * @param int $timestamp Log timestamp.
	 * @return string Formatted time for use in log entry.
	 */
	protected static function format_time( $timestamp ) {
		return gmdate( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( $timestamp, $level, $message, $context ) {
		$time_string  = self::format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";

		/**
		 * Filter the formatted log entry before it is written.
		 *
		 * @since 3.0.0
		 *
		 * @param string $entry The formatted entry.
		 * @param array  $args  The raw data that gets assembled into a log entry.
		 */
		return apply_filters(
			'woocommerce_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			)
		);
	}

	/**
	 * Get a backtrace that shows where the logging function was called.
	 *
	 * @return array
	 */
	protected static function get_backtrace() {
		// Get the filenames of the logging-related classes so we can ignore them.
		$ignore_files = array_map(
			function( $class ) {
				try {
					$reflector = new \ReflectionClass( $class );
					return $reflector->getFileName();
				} catch ( Exception $exception ) {
					return null;
				}
			},
			array( wc_get_logger(), self::class, static::class )
		);

		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		$filtered_backtrace = array_filter(
			$backtrace,
			function( $frame ) use ( $ignore_files ) {
				$ignore = isset( $frame['file'] ) && in_array( $frame['file'], $ignore_files, true );

				return ! $ignore;
			}
		);

		return array_values( $filtered_backtrace );
	}
}
