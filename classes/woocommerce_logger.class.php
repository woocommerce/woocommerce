<?php
/**
 * Allows log files to be written to for debugging purposes.
 *
 * @class 		woocommerce
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_logger {
	
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
		
		if ($this->handles[$handle] = fopen( $woocommerce->plugin_path() . '/logs/' . $handle . '.txt', 'a' )) return true;
		
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