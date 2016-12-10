<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract WC Log Handler Class
 *
 * @version        1.0.0
 * @package        WooCommerce/Abstracts
 * @category       Abstract Class
 * @author         WooThemes
 */
abstract class WC_Log_Handler {

	/**
	 * Minimum log level this handler will process.
	 *
	 * @var int 0-7 minimum level severity for handling log entry.
	 * @access private
	 */
	protected $threshold;

	/**
	 * Constructor for log handler.
	 *
	 * @param array $args {
	 *     @type string $threshold Optional. Default 'emergency'. Sets the log severity threshold.
	 *         emergency|alert|critical|error|warning|notice|info|debug
	 * }
	 */
	public function __construct( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'threshold' => WC_Log_Levels::DEBUG,
		) );

		$this->set_threshold( $args['threshold'] );
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Additional information for log handlers.
	 *
	 * @return bool True on success.
	 */
	abstract public function handle( $timestamp, $level, $message, $context );

	/**
	 * Set handler severity threshold.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 */
	public function set_threshold( $level ) {
		$level = apply_filters( 'woocommerce_log_handler_set_threshold', $level, get_class( $this ) );
		$this->threshold = WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Determine whether handler should handle log.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return bool True if the log should be handled.
	 */
	public function should_handle( $level ) {
		return $this->threshold <= WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Formats a timestamp for use in log messages.
	 *
	 * @param int $timestamp Log timestamp.
	 * @return string Formatted time for use in log entry.
	 */
	public static function format_time( $timestamp ) {
		return date( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	public function format_entry( $timestamp, $level, $message, $context ) {
		$time_string = $this->format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry = "{$time_string} {$level_string} {$message}";

		return apply_filters( 'woocommerce_format_log_entry', $entry, array(
			'timestamp' => $timestamp,
			'level' => $level,
			'message' => $message,
			'context' => $context,
		) );
	}
}
