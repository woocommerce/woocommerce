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
 * @version        2.8.0
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
	protected $_threshold;

	/**
	 * Log levels by severity.
	 *
	 * @var array
	 * @access private
	 */
	protected static $_log_levels = array(
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
	 * @param arr $args {
	 *     @type string $threshold Optional. Sets the log severity threshold.
	 *         emergency|alert|critical|error|warning|notice|info|debug
	 * }
	 */
	public function __construct( $args = array() ) {
		if ( isset( $args['threshold'] ) ) {
			$this->set_threshold( $args['threshold'] );
		} else {
			$this->set_threshold( WC_Logger::EMERGENCY );
		}
	}

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
	 * Set handler severity threshold.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 */
	public function set_threshold( $level ) {
		$level = apply_filters( 'woocommerce_log_handler_set_threshold', $level, __CLASS__ );
		$this->_threshold = $this->get_level_severity( $level );
	}

	/**
	 * Decode level string into integer.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return int 0 (debug) - 8 (emergency) or -1 if level is not valid
	 */
	public static function get_level_severity( $level ) {
		if ( array_key_exists( $level, self::$_log_levels ) ) {
			$severity = self::$_log_levels[ $level ];
		} else {
			$severity = -1;
		}
		return $severity;
	}

	/**
	 * Should this handler process this log?
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return bool true if the log should be handled.
	 */
	public function should_handle( $level ) {
		return $this->_threshold <= $this->get_level_severity( $level );
	}

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
