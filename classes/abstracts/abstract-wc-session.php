<?php
/**
 * Handle data for the current customers session.
 *
 * @class 		WC_Session
 * @version		1.7
 * @package		WooCommerce/Classes/Abstracts
 * @author 		WooThemes
 */
abstract class WC_Session {	
	/**
	 * Constructor for the session class. Hooks in methods.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
    	// When leaving or ending page load, store data
    	add_action( 'shutdown', array( &$this, 'save_data' ), 20 );
    }
	
    /**
     * __get function.
     * 
     * @access public
     * @param mixed $property
     * @return mixed
     */
    abstract public function __get( $property );
 
    /**
     * __set function.
     * 
     * @access public
     * @param mixed $property
     * @param mixed $value
     * @return void
     */
    abstract public function __set( $property, $value );
    
     /**
     * __isset function.
     * 
     * @access public
     * @param mixed $property
     * @return bool
     */
    abstract public function __isset( $property );
    
    /**
     * __unset function.
     * 
     * @access public
     * @param mixed $property
     * @return void
     */
    abstract public function __unset( $property );
    
    /**
     * save_data function.
     * 
     * @access public
     * @return void
     */
    abstract public function save_data();
}