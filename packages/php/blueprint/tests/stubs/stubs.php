<?php
/**
 * Stubs for WooCommerce classes and interfaces.
 */

// This file should only be loaded during test runs to prevent conflicts in development environments.
// During local development, files are copied to the WooCommerce vendor directory where the autoloader might attempt to load this file.
if ( defined( 'WOO_BLUEPRINT_TESTS' ) ) {

	if ( ! class_exists( 'WC_Log_Levels', false ) ) {
		/**
		 * WC Log Levels Class
		 */
		class WC_Log_Levels {
			const EMERGENCY = 'emergency';
			const ALERT     = 'alert';
			const CRITICAL  = 'critical';
			const ERROR     = 'error';
			const WARNING   = 'warning';
			const NOTICE    = 'notice';
			const INFO      = 'info';
			const DEBUG     = 'debug';
		}
	}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
	if ( ! interface_exists( 'WC_Logger_Interface' ) ) {
		/**
		 * WC Logger Interface
		 */
		interface WC_Logger_Interface {
			/**
			 * Log message with level.
			 *
			 * @param string $level Log level.
			 * @param string $message Log message.
			 * @param array  $context Optional. Additional information for log handlers.
			 */
			public function log( $level, $message, $context = array() );

			/**
			 * Add a log entry.
			 *
			 * @param string $handle Log handle.
			 * @param string $message Log message.
			 * @param string $level Log level.
			 */
			public function add( $handle, $message, $level = 'notice' );

			/**
			 * Add an emergency level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function emergency( $message, $context = array() );

			/**
			 * Add an alert level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function alert( $message, $context = array() );

			/**
			 * Add a critical level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function critical( $message, $context = array() );

			/**
			 * Add an error level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function error( $message, $context = array() );

			/**
			 * Add a warning level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function warning( $message, $context = array() );

			/**
			 * Add a notice level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function notice( $message, $context = array() );

			/**
			 * Add an info level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function info( $message, $context = array() );

			/**
			 * Add a debug level message.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function debug( $message, $context = array() );
		}
	}

	if ( ! function_exists( 'wc_get_logger' ) ) {
		/**
		 * Mock wc_get_logger function.
		 *
		 * @return WC_Logger_Interface
		 */
		function wc_get_logger() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
			return Mockery::mock( 'WC_Logger_Interface' )->shouldReceive( 'log' )->getMock();
		}
	}
}
