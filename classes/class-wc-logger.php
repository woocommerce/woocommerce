<?php
/**
 * Allows log files to be written to for debugging purposes.
 *
 * @class 		WC_Logger
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Logger {
	
	private $handles;
	
	/** constructor */
	function __construct() {
		$this->handles = array();
	}

	/** destructor */
	function __destruct() {
	
		foreach ($this->handles as $handle) :
	       fclose( $handle );
	    endforeach;
	    
	}
	
	/**
	 * Open log file for writing
	 */
	private function open( $handle ) {
		global $woocommerce;
		
		if (isset($this->handles[$handle])) return true;
		
		if ($this->handles[$handle] = @fopen( $woocommerce->plugin_path() . '/logs/' . $handle . '.txt', 'a' )) return true;
		
		return false;
	}
	
	/**
	 * Add a log entry to chosen file
	 */
	public function add( $handle, $message ) {
		
		if ($this->open($handle)) :
		
			$time = date('m-d-Y @ H:i:s -'); //Grab Time
			fwrite($this->handles[$handle], $time . " " . $message . "\n");
		
		endif;
		
	}
	
	/**
	 * Clear entrys from chosen file
	 */
	public function clear( $handle ) {
		
		if ($this->open($handle)) :
		
			ftruncate( $this->handles[$handle], 0 );
			
		endif;
		
	}

}

/** Depreciated */
class woocommerce_logger extends WC_Logger {
	public function __construct() { 
		_deprecated_function( 'woocommerce_logger', '1.4', 'WC_Logger()' );
		parent::__construct(); 
	} 
}