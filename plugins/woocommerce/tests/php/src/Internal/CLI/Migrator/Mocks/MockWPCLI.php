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
}

// Create global WP_CLI class alias if it doesn't exist.
if ( ! class_exists( 'WP_CLI' ) ) {
	class_alias( MockWPCLI::class, 'WP_CLI' );
}
