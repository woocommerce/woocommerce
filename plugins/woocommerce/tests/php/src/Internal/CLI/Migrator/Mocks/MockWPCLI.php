<?php
/**
 * Mock WP_CLI class for testing.
 *
 * @package WooCommerce\Tests\Internal\CLI\Migrator\Mocks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks;

/**
 * Mock WP_CLI class for testing purposes.
 */
class MockWPCLI {
	/**
	 * Last debug message logged.
	 *
	 * @var string
	 */
	public static $last_debug_message = '';

	/**
	 * Last warning message logged.
	 *
	 * @var string
	 */
	public static $last_warning_message = '';

	/**
	 * Last log message logged.
	 *
	 * @var string
	 */
	public static $last_log_message = '';

	/**
	 * Last error message logged.
	 *
	 * @var string
	 */
	public static $last_error_message = '';

	/**
	 * Last success message logged.
	 *
	 * @var string
	 */
	public static $last_success_message = '';

	/**
	 * All log messages collected.
	 *
	 * @var array
	 */
	public static $all_log_messages = array();

	/**
	 * All success messages collected.
	 *
	 * @var array
	 */
	public static $all_success_messages = array();

	/**
	 * Simulated user input for STDIN reading in tests.
	 *
	 * @var string
	 */
	public static $mock_stdin_input = 'y';

	/**
	 * Mock debug method.
	 *
	 * @param string $message Debug message.
	 */
	public static function debug( $message ): void {
		self::$last_debug_message = $message;
	}

	/**
	 * Mock warning method.
	 *
	 * @param string $message Warning message.
	 */
	public static function warning( $message ): void {
		self::$last_warning_message = $message;
	}

	/**
	 * Mock log method.
	 *
	 * @param string $message Log message.
	 */
	public static function log( $message ): void {
		self::$last_log_message   = $message;
		self::$all_log_messages[] = $message;
	}

	/**
	 * Mock error method.
	 *
	 * @param string $message Error message.
	 */
	public static function error( $message ): void {
		self::$last_error_message = $message;
	}

	/**
	 * Mock success method.
	 *
	 * @param string $message Success message.
	 */
	public static function success( $message ): void {
		self::$last_success_message   = $message;
		self::$all_success_messages[] = $message;
	}

	/**
	 * Mock line method.
	 *
	 * @param string $message Line message.
	 */
	public static function line( $message ): void {
		self::$last_log_message   = $message;
		self::$all_log_messages[] = $message;
	}

	/**
	 * Mock out method for prompting user input.
	 *
	 * @param string $message Output message.
	 */
	public static function out( $message ): void {
		self::$last_log_message   = $message;
		self::$all_log_messages[] = $message;
	}

	/**
	 * Mock colorize method.
	 *
	 * @param string $message Message to colorize.
	 * @return string Unmodified message (no actual colorization in tests).
	 */
	public static function colorize( $message ): string {
		// Remove colorization codes for tests.
		return preg_replace( '/%(.)/', '', $message );
	}

	/**
	 * Mock confirm method.
	 *
	 * @param string $question Question to confirm.
	 * @return bool Always returns true in tests.
	 */
	public static function confirm( $question ): bool {
		self::$last_log_message = $question;
		return true;
	}
}

// Create global WP_CLI class alias if it doesn't exist.
if ( ! class_exists( 'WP_CLI' ) ) {
	class_alias( MockWPCLI::class, 'WP_CLI' );
}
