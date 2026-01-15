<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Logger;

/**
 * Email Editor Logger class.
 * A wrapper that sets the logger to use. If no logger is provided, it defaults to the Default_Email_Editor_Logger.
 */
class Email_Editor_Logger implements Email_Editor_Logger_Interface {
	/**
	 * Logger instance to delegate to.
	 *
	 * @var Email_Editor_Logger_Interface
	 */
	private Email_Editor_Logger_Interface $logger;

	/**
	 * Constructor.
	 *
	 * @param Email_Editor_Logger_Interface|null $logger Logger instance.
	 */
	public function __construct( ?Email_Editor_Logger_Interface $logger = null ) {
		$this->logger = $logger ?? new Default_Email_Editor_Logger();
	}

	/**
	 * Set the logger.
	 *
	 * @param Email_Editor_Logger_Interface $logger Logger instance.
	 * @return void
	 */
	public function set_logger( Email_Editor_Logger_Interface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * Adds emergency level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->logger->emergency( $message, $context );
	}

	/**
	 * Adds alert level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->logger->alert( $message, $context );
	}

	/**
	 * Adds critical level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->logger->critical( $message, $context );
	}

	/**
	 * Adds error level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->logger->error( $message, $context );
	}

	/**
	 * Adds warning level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->logger->warning( $message, $context );
	}

	/**
	 * Adds notice level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->logger->notice( $message, $context );
	}

	/**
	 * Adds info level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->logger->info( $message, $context );
	}

	/**
	 * Adds debug level log message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->logger->debug( $message, $context );
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
		$this->logger->log( $level, $message, $context );
	}
}
