<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Logger;

/**
 * Interface for email editor loggers.
 */
interface Email_Editor_Logger_Interface {
	/**
	 * System is unusable.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void;

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void;

	/**
	 * Critical conditions.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void;

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void;

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void;

	/**
	 * Normal but significant events.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void;

	/**
	 * Interesting events.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void;

	/**
	 * Detailed debug information.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void;

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level   The log level.
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = array() ): void;
}
