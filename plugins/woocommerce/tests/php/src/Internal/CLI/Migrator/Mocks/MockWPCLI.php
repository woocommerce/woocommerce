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
	 * Rows passed to the last `WP_CLI\Utils\format_items()` call.
	 *
	 * @var array
	 */
	public static $last_table = array();

	/**
	 * Exit code passed to the last `halt()` call, or null when it was never called.
	 *
	 * @var int|null
	 */
	public static $last_halt_code = null;

	/**
	 * Questions that reached a prompt, i.e. were asked without `--yes`.
	 *
	 * @var array
	 */
	public static $prompted_confirmations = array();

	/**
	 * Simulated user input for STDIN reading in tests.
	 *
	 * @var string
	 */
	public static $mock_stdin_input = 'y';

	/**
	 * Mock debug method.
	 *
	 * @param string      $message Debug message.
	 * @param string|bool $group   Debug group.
	 */
	public static function debug( $message, $group = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
	 * Mock halt method. Records the exit code instead of terminating the process.
	 *
	 * @param int $code Exit code.
	 */
	public static function halt( $code ): void {
		self::$last_halt_code = $code;
	}

	/**
	 * Clear every recorded message so one test cannot read another's output.
	 */
	public static function reset(): void {
		self::$last_debug_message     = '';
		self::$last_warning_message   = '';
		self::$last_log_message       = '';
		self::$last_error_message     = '';
		self::$last_success_message   = '';
		self::$all_log_messages       = array();
		self::$all_success_messages   = array();
		self::$last_halt_code         = null;
		self::$last_table             = array();
		self::$prompted_confirmations = array();
	}

	/**
	 * Mock confirm method. Honours `--yes` the way WP-CLI does, so a command that forgets to
	 * forward its $assoc_args shows up as a prompt that should never have been reached.
	 *
	 * @param string $question   Question to confirm.
	 * @param array  $assoc_args Associative arguments the command was invoked with.
	 * @return bool Always returns true in tests.
	 */
	public static function confirm( $question, $assoc_args = array() ): bool {
		self::$last_log_message = $question;

		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'yes' ) ) {
			self::$prompted_confirmations[] = $question;
		}

		return true;
	}
}

// Create global WP_CLI class alias if it doesn't exist.
if ( ! class_exists( 'WP_CLI' ) ) {
	class_alias( MockWPCLI::class, 'WP_CLI' );
}

require_once __DIR__ . '/wp-cli-utils.php';
