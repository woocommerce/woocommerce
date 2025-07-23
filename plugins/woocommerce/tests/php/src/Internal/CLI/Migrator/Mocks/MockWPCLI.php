<?php
/**
 * Mock WP_CLI class for testing.
 *
 * @package WooCommerce\Tests\Internal\CLI\Migrator\Mocks
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Mock WP_CLI class for testing purposes.
	 */
	class WP_CLI {
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
		public static function debug( $message ) {
			self::$last_debug_message = $message;
		}

		/**
		 * Mock warning method.
		 *
		 * @param string $message Warning message.
		 */
		public static function warning( $message ) {
			self::$last_warning_message = $message;
		}
	}
}