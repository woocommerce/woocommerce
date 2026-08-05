<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * WP-CLI test double.
	 */
	class WP_CLI {
		/**
		 * Recorded calls.
		 *
		 * @var array
		 */
		public static $calls = array();

		/**
		 * Record a warning.
		 *
		 * @param string $message Warning message.
		 */
		public static function warning( $message ) {
			self::$calls[] = array( 'warning', $message );
		}

		/**
		 * Record a confirmation prompt.
		 *
		 * @param string $message Confirmation message.
		 * @param array  $assoc_args Command arguments.
		 */
		public static function confirm( $message, $assoc_args = array() ) {
			self::$calls[] = array( 'confirm', $message, $assoc_args );
		}

		/**
		 * Record a success message.
		 *
		 * @param string $message Success message.
		 */
		public static function success( $message ) {
			self::$calls[] = array( 'success', $message );
		}

		/**
		 * Record an error message.
		 *
		 * @param string $message Error message.
		 */
		public static function error( $message ) {
			self::$calls[] = array( 'error', $message );
		}
	}
}
