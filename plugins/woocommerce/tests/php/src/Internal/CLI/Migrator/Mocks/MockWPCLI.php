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
	 * All log messages accumulated.
	 *
	 * @var array
	 */
	public static $all_log_messages = array();

	/**
	 * Last success message logged.
	 *
	 * @var string
	 */
	public static $last_success_message = '';

	/**
	 * Last error message logged.
	 *
	 * @var string
	 */
	public static $last_error_message = '';

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
	 * Mock success method.
	 *
	 * @param string $message Success message.
	 */
	public static function success( $message ): void {
		self::$last_success_message = $message;
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
	 * Mock line method.
	 *
	 * @param string $message Line message.
	 */
	public static function line( $message ): void {
		// For testing, we can just log it like a regular message.
		self::$last_log_message = $message;
	}

	/**
	 * Mock readline method.
	 *
	 * @param string $prompt Prompt message.
	 * @return string
	 */
	public static function readline( $prompt ): string {
		// For testing, return a mock input.
		return 'test_input';
	}

	/**
	 * Mock add_command method.
	 *
	 * @param string $name Command name.
	 * @param mixed  $callable Command callable.
	 * @param array  $args Command arguments.
	 */
	public static function add_command( $name, $callable, $args = array() ): void {
		// Mock implementation - do nothing for tests.
	}
}

// Create global WP_CLI class alias if it doesn't exist.
if ( ! class_exists( 'WP_CLI' ) ) {
	class_alias( MockWPCLI::class, 'WP_CLI' );
}
