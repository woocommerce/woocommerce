<?php
/**
 * This file is part of the WooCommerce package.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;

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
	 * Adds emergency level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->wc_logger->emergency( $message, $context );
	}

	/**
	 * Adds alert level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->wc_logger->alert( $message, $context );
	}

	/**
	 * Adds critical level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->wc_logger->critical( $message, $context );
	}

	/**
	 * Adds error level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->wc_logger->error( $message, $context );
	}

	/**
	 * Adds warning level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->wc_logger->warning( $message, $context );
	}

	/**
	 * Adds notice level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->wc_logger->notice( $message, $context );
	}

	/**
	 * Adds info level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->wc_logger->info( $message, $context );
	}

	/**
	 * Adds debug level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->wc_logger->debug( $message, $context );
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
		$this->wc_logger->log( $level, $message, $context );
	}
}
