<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Logger' ) ) {
	include_once( dirname( dirname( __FILE__ ) ) . '/class-wc-logger.php' );
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
	 * @var int 0-8 minimum level severity for handling log entry.
	 * @access private
	 */
	protected $threshold;

	/**
	 * Log levels by severity.
	 *
	 * @var array
	 * @access private
	 */
	protected static $log_levels = array(
		WC_Logger::DEBUG     => 0,
		WC_Logger::INFO      => 1,
		WC_Logger::NOTICE    => 2,
		WC_Logger::WARNING   => 3,
		WC_Logger::ERROR     => 4,
		WC_Logger::CRITICAL  => 6,
		WC_Logger::ALERT     => 7,
		WC_Logger::EMERGENCY => 8,
	);

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
			'threshold' => WC_Logger::DEBUG,
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
	 * @return bool True if log entry should bubble to further loggers.
	 */
	abstract public function handle( $timestamp, $level, $message, $context );

	/**
	 * Set handler severity threshold.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 */
	public function set_threshold( $level ) {
		$level = apply_filters( 'woocommerce_log_handler_set_threshold', $level, get_class( $this ) );
		$this->threshold = $this->get_level_severity( $level );
	}

	/**
	 * Decode level string into integer.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return int 0 (debug) - 8 (emergency) or -1 if level is not valid
	 */
	public static function get_level_severity( $level ) {
		if ( array_key_exists( $level, self::$log_levels ) ) {
			$severity = self::$log_levels[ $level ];
		} else {
			$severity = -1;
		}
		return $severity;
	}

	/**
	 * Determine whether handler should handle log.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return bool True if the log should be handled.
	 */
	public function should_handle( $level ) {
		return $this->threshold <= $this->get_level_severity( $level );
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
		return "{$time_string} {$level_string} {$message}";
	}
}
