<?php
/**
 * Allows log files to be written to for debugging purposes.
 *
 * @class 		WC_Logger
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Logger {

	/**
	 * @var array Stores open file handles.
	 * @access private
	 */
	private $handles;

	/**
	 * Constructor for the logger.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->handles = array();
	}


	/**
	 * Destructor.
	 *
	 * @access public
	 * @return void
	 */
	function __destruct() {
		foreach ($this->handles as $handle)
	       fclose( $handle );
	}


	/**
	 * Open log file for writing.
	 *
	 * @access private
	 * @param mixed $handle
	 * @return bool success
	 */
	private function open( $handle ) {
		global $woocommerce;

		if ( isset( $this->handles[$handle] ) )
			return true;

		if ( $this->handles[$handle] = @fopen( $woocommerce->plugin_path() . '/logs/' . $handle . '.txt', 'a' ) )
			return true;

		return false;
	}


	/**
	 * Add a log entry to chosen file.
	 *
	 * @access public
	 * @param mixed $handle
	 * @param mixed $message
	 * @return void
	 */
	public function add( $handle, $message ) {
		if ( $this->open( $handle ) ) {
			$time = date_i18n( 'm-d-Y @ H:i:s -' ); //Grab Time
			fwrite( $this->handles[$handle], $time . " " . $message . "\n" );
		}
	}


	/**
	 * Clear entrys from chosen file.
	 *
	 * @access public
	 * @param mixed $handle
	 * @return void
	 */
	public function clear( $handle ) {

		if ( $this->open( $handle ) )
			ftruncate( $this->handles[$handle], 0 );
	}

}

/**
 * woocommerce_logger class.
 *
 * @extends 	WC_Logger
 * @deprecated 	1.4
 * @package		WooCommerce/Classes
 */
class woocommerce_logger extends WC_Logger {
	public function __construct() {
		_deprecated_function( 'woocommerce_logger', '1.4', 'WC_Logger()' );
		parent::__construct();
	}
}