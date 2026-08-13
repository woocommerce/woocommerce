<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Logger;

/**
 * Default implementation of the email editor logger that writes to WordPress debug log.
 */
class Default_Email_Editor_Logger implements Email_Editor_Logger_Interface {
	/**
	 * Log levels.
	 */
	public const EMERGENCY = 'emergency';
	public const ALERT     = 'alert';
	public const CRITICAL  = 'critical';
	public const ERROR     = 'error';
	public const WARNING   = 'warning';
	public const NOTICE    = 'notice';
	public const INFO      = 'info';
	public const DEBUG     = 'debug';

	/**
	 * Path to the log file.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			if ( true === WP_DEBUG_LOG ) {
				$this->log_file = WP_CONTENT_DIR . '/debug.log';
			} elseif ( is_string( WP_DEBUG_LOG ) && ! empty( WP_DEBUG_LOG ) ) {
				$this->log_file = WP_DEBUG_LOG;
			} else {
				$this->log_file = '';
			}
		} else {
			$this->log_file = '';
		}
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level   The log level.
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = array() ): void {
		if ( ! $this->log_file ) {
			return;
		}

		$entry = sprintf(
			'[%s] %s: %s %s',
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $level ),
			$message,
			! empty( $context ) ? wp_json_encode( $context ) : ''
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is a logging class, error_log is the intended functionality.
		error_log( $entry . PHP_EOL, 3, $this->log_file );
	}
}
