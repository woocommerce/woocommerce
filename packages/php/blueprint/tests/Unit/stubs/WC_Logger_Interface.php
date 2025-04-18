<?php

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
