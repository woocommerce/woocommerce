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
    /** _data  */
    protected $_data;

    /**
     * save_data function to be implemented
     * 
     * @access public
     * @return void
     */
    abstract public function save_data();

	/**
	 * Constructor for the session classes. Hooks in methods.
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
    public function __get( $property ) {
        return isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : null;
    }
 
    /**
     * __set function.
     * 
     * @access public
     * @param mixed $property
     * @param mixed $value
     * @return void
     */
    public function __set( $property, $value ) {
        $this->_data[ $property ] = $value;
    }
    
     /**
     * __isset function.
     * 
     * @access public
     * @param mixed $property
     * @return bool
     */
    public function __isset( $property ) {
        return isset( $this->_data[ $property ] );
    }
    
    /**
     * __unset function.
     * 
     * @access public
     * @param mixed $property
     * @return void
     */
    public function __unset( $property ) {
        unset( $this->_data[ $property ] );
    }
}