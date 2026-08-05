<?php
/**
 * This file is part of the WooCommerce package.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use WC_Log_Levels;

/**
 * WooCommerce logger adapter for the email editor.
 *
 * This class adapts the WooCommerce logger to work with the email editor logging interface.
 */
class Logger implements Email_Editor_Logger_Interface {
	/**
	 * The WooCommerce logger instance.
	 *
	 * @var \WC_Logger_Interface
	 */
	private \WC_Logger_Interface $wc_logger;

	/**
	 * Constructor.
	 *
	 * @param \WC_Logger_Interface $wc_logger The WooCommerce logger instance.
	 */
	public function __construct( \WC_Logger_Interface $wc_logger ) {
		$this->wc_logger = $wc_logger;
	}

	/**
	 * Checks if the log level should be handled.
	 *
	 * @param string $level The log level.
	 * @return bool Whether the log level should be handled.
	 */
	private function should_handle( string $level ): bool {
		/**
		 * Controls the logging threshold for the email editor.
		 *
		 * @param string $threshold The log level threshold.
		 *
		 * @since 10.2.0
		 */
		$logging_threshold = apply_filters( 'woocommerce_email_editor_logging_threshold', WC_Log_Levels::WARNING );

		return WC_Log_Levels::get_level_severity( $logging_threshold ) <= WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Adds emergency level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::EMERGENCY, $message, $context );
	}

	/**
	 * Adds alert level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::ALERT, $message, $context );
	}

	/**
	 * Adds critical level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::CRITICAL, $message, $context );
	}

	/**
	 * Adds error level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::ERROR, $message, $context );
	}

	/**
	 * Adds warning level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::WARNING, $message, $context );
	}

	/**
	 * Adds notice level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::NOTICE, $message, $context );
	}

	/**
	 * Adds info level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::INFO, $message, $context );
	}

	/**
	 * Adds debug level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::DEBUG, $message, $context );
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
		if ( $this->should_handle( $level ) ) {
			$this->wc_logger->log( $level, $message, $context );
		}
	}
}
