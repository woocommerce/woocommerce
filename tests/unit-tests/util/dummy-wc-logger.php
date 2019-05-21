<?php

/**
 * Dummy Logger implements WC_Logger_Interface.
 */
class Dummy_WC_Logger implements WC_Logger_Interface {


	/**
	 * Do nothing.
	 *
	 * @param string $handle
	 * @param string $message
	 * @param string $level
	 *
	 * @return bool|void
	 */
	public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $level
	 * @param string $message
	 * @param array  $context
	 */
	public function log( $level, $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function emergency( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function alert( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function critical( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function error( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function warning( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function notice( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function info( $message, $context = array() ) {
	}

	/**
	 * Do nothing.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function debug( $message, $context = array() ) {
	}

}
