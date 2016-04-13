<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Allows log files to be written to for debugging purposes
 *
 * @class          WC_Logger
 * @version        1.6.4
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Logger {

	/**
	 * Stores open file _handles.
	 *
	 * @var array
	 * @access private
	 */
	private $_handles;

	/**
	 * Constructor for the logger.
	 */
	public function __construct() {
		$this->_handles = array();
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach ( $this->_handles as $handle ) {
			@fclose( $handle );
		}
	}

	/**
	 * Open log file for writing.
	 *
	 * @access private
	 *
	 * @param mixed $handle
	 *
	 * @return bool success
	 */
	private function open( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		if ( $this->_handles[ $handle ] = @fopen( wc_get_log_file_path( $handle ), 'a' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle
	 * @param string $message
	 *
	 * @return bool
	 */
	public function add( $handle, $message ) {
		$result = false;

		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			$time   = date_i18n( 'm-d-Y @ H:i:s -' ); // Grab Time
			$result = fwrite( $this->_handles[ $handle ], $time . " " . $message . "\n" );
		}

		do_action( 'woocommerce_log_add', $handle, $message );

		return $result === false ? false : true;
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle
	 *
	 * @return bool
	 */
	public function clear( $handle ) {
		$result = false;

		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			$result = ftruncate( $this->_handles[ $handle ], 0 );
		}

		do_action( 'woocommerce_log_clear', $handle );

		return $result === false ? false : true;
	}

}