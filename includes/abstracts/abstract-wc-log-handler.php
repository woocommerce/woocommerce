<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract WC Log Handler Class
 *
 * @version        2.8.0
 * @package        WooCommerce/Abstracts
 * @category       Abstract Class
 * @author         WooThemes
 */
abstract class WC_Log_Handler {

	/**
	 * Handle a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param int $timestamp
	 * @param string $message
	 * @param array $context {
	 *     Optional. Array with additional information.
	 * }
	 *
	 * @return bool log entry should bubble to further loggers.
	 */
	public abstract function handle( $level, $timestamp, $message, $context );

	/**
	 * Formats a timestamp for use in log messages.
	 *
	 * @param int $timestamp
	 * @return string formatted time
	 */
	public static function format_time( $timestamp ) {
		return date( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param int $timestamp
	 * @param string $message
	 *
	 * @return string Formatted log entry
	 */
	public function format_entry( $level, $timestamp, $message, $context ) {
		$time_string = $this->format_time( $timestamp );
		$level_string = to_uppercase( $level );
		return "[{$time}] {$level_string}: {$message}";
	}
}
