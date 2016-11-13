<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Provides logging capabilities for debugging purposes.
 *
 * @class          WC_Logger
 * @version        2.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Logger {

	/**
	 * Log Levels
	 *
	 * @see @link {https://tools.ietf.org/html/rfc5424}
	 */
	const DEBUG     = 'debug';
	const INFO      = 'info';
	const NOTICE    = 'notice';
	const WARNING   = 'warning';
	const ERROR     = 'error';
	const CRITICAL  = 'critical';
	const ALERT     = 'alert';
	const EMERGENCY = 'emergency';

	/**
	 * Stores registered log handlers.
	 *
	 * @var array
	 * @access private
	 */
	private $_handlers;

	private static $_valid_levels = array(
		self::DEBUG,
		self::INFO,
		self::NOTICE,
		self::WARNING,
		self::ERROR,
		self::CRITICAL,
		self::ALERT,
		self::EMERGENCY,
	);

	/**
	 * Constructor for the logger.
	 */
	public function __construct() {
		$handlers = apply_filters( 'woocommerce_register_log_handlers', array() );
		$this->_handlers = $handlers;
	}

	/**
	 * Add a log entry.
	 *
	 * @deprecated since 2.0.0
	 *
	 * @param string $handle
	 * @param string $message
	 *
	 * @return bool
	 */
	public function add( $handle, $message ) {
		_deprecated_function( 'WC_Logger::add', '2.8', 'WC_Logger::log' );
		$message = apply_filters( 'woocommerce_logger_add_message', $message, $handle );
		$this->log( self::INFO, $message, array( 'tag' => $handle, '_legacy' => true ) );
		wc_do_deprecated_action( 'woocommerce_log_add', array( $handle, $message ), '2.8', 'This action has been deprecated with no alternative.' );
		return true;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Additional information for log handlers.
	 *
	 *     @type string $tag Optional. May be used by log handlers to sort messages.
	 * }
	 */
	public function log( $level, $message, $context = array() ) {
		if ( ! in_array( $level, self::$_valid_levels ) ) {
			$class = __CLASS__;
			$method = __FUNCTION__;
			_doing_it_wrong( "{$class}::{$method}", sprintf( __( 'WC_Logger::log was called with an invalid level "%s".', 'woocommerce' ), $level ), '2.8' );
		}

		$timestamp = current_time( 'timestamp' );

		foreach ( $this->_handlers as $handler ) {
			$continue = $handler->handle( $level, $timestamp, $message, $context );

			if ( false === $continue ) {
				break;
			}
		}

	}

	/**
	 * Adds an emergency level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Adds an alert level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function alert( $message, $context = array() ) {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Adds a critical level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function critical( $message, $context = array() ) {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Adds an error level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function error( $message, $context = array() ) {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Adds a warning level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function warning( $message, $context = array() ) {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Adds a notice level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function notice( $message, $context = array() ) {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Adds a info level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function info( $message, $context = array() ) {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Adds a debug level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function debug( $message, $context = array() ) {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @deprecated since 2.0.0
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function clear( $handle ) {
		_deprecated_function( 'WC_Logger::clear', '2.8' );
		return false;
	}

	/**
	 * Remove/delete the chosen file.
	 *
	 * @deprecated since 2.0.0
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function remove( $handle ) {
		_deprecated_function( 'WC_Logger::remove', '2.8' );
		return false;
	}
}
