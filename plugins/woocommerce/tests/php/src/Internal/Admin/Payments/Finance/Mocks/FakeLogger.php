<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks;

use WC_Log_Levels;
use WC_Logger_Interface;

/**
 * Fake logger that records every call by level, for injection through the woocommerce_logging_class filter.
 */
class FakeLogger implements WC_Logger_Interface {

	/**
	 * Recorded calls keyed by level.
	 *
	 * @var array<string, array<int, array{message: string, context: array}>>
	 */
	public array $calls = array();

	/**
	 * Get the messages logged at a level.
	 *
	 * @param string $level The log level.
	 * @return string[]
	 */
	public function get_messages( string $level ): array {
		return array_column( $this->calls[ $level ] ?? array(), 'message' );
	}

	/**
	 * Legacy add method.
	 *
	 * @param string $handle  Log handle.
	 * @param string $message Log message.
	 * @param string $level   Log level.
	 * @return bool
	 */
	public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
		unset( $handle );
		$this->log( $level, $message );
		return true;
	}

	/**
	 * Record a log call.
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function log( $level, $message, $context = array() ) {
		$this->calls[ $level ][] = array(
			'message' => $message,
			'context' => $context,
		);
	}

	/**
	 * Record an emergency.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( WC_Log_Levels::EMERGENCY, $message, $context );
	}

	/**
	 * Record an alert.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function alert( $message, $context = array() ) {
		$this->log( WC_Log_Levels::ALERT, $message, $context );
	}

	/**
	 * Record a critical message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function critical( $message, $context = array() ) {
		$this->log( WC_Log_Levels::CRITICAL, $message, $context );
	}

	/**
	 * Record an error.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function error( $message, $context = array() ) {
		$this->log( WC_Log_Levels::ERROR, $message, $context );
	}

	/**
	 * Record a warning.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function warning( $message, $context = array() ) {
		$this->log( WC_Log_Levels::WARNING, $message, $context );
	}

	/**
	 * Record a notice.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function notice( $message, $context = array() ) {
		$this->log( WC_Log_Levels::NOTICE, $message, $context );
	}

	/**
	 * Record an info message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function info( $message, $context = array() ) {
		$this->log( WC_Log_Levels::INFO, $message, $context );
	}

	/**
	 * Record a debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Log context.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( WC_Log_Levels::DEBUG, $message, $context );
	}
}
