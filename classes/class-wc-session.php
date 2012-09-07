<?php
/**
 * Handle data for the current customers session.
 *
 * @class 		WC_Session
 * @version		1.7
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Session {
	
	/** _data  */
	protected $_data;
	
	/** customer_id */
	private $_customer_id;
	
	/** cookie name */
	private $_cookie;
	
	/**
	 * Constructor for the session class. Hooks in methods.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		
		$this->_cookie		= 'wc_session_cookie_' . COOKIEHASH;
		$this->_customer_id = $this->get_customer_id();
		$this->_data 		= get_transient( 'wc_session_' . $this->_customer_id );
    	
    	if ( false === $this->_data ) 
    		$this->_data = array();
    	
    	// When leaving or ending page load, store data
    	add_filter( 'wp_redirect', array( &$this, 'save_data' ), 1, 2 );
    	add_filter( 'shutdown', array( &$this, 'save_data' ) );
	}
	
	/**
	 * get_customer_id function.
	 * 
	 * @access public
	 * @return mixed
	 */
	function get_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} elseif ( $customer_id = $this->get_session_cookie() ) {
			return $customer_id;
		} else {
			return $this->create_customer_id();
		}
	}
	
	/**
	 * get_session_cookie function.
	 * 
	 * @access public
	 * @return mixed
	 */
	function get_session_cookie() {
		if ( ! isset( $_COOKIE[ $this->_cookie ] ) ) 
			return false;
		
		list( $customer_id, $expires, $hash ) = explode( '|', $_COOKIE[ $this->_cookie ] );
		
		// Validate hash
		$data 	= $customer_id . $expires;
		$rehash = hash_hmac( 'md5', $data, wp_hash( $data ) );

		if ( $hash != $rehash )
			return false;
			
		return $customer_id;
	}
	
	/**
	 * Create a unqiue customer ID and store it in a cookie, along with its hashed value and expirey date. Stored for 48hours.
	 * 
	 * @access public
	 * @return void
	 */
	function create_customer_id() {
		$customer_id 	= uniqid( 'wc_' );
		$expires 		= time() + 172800;
		$data 			= $customer_id . $expires;
		$hash 			= hash_hmac( 'md5', $data, wp_hash( $data ) );
		$value 			= $customer_id . '|' . $expires . '|' . $hash;

		setcookie( $this->_cookie, $value, $expires, COOKIEPATH, COOKIE_DOMAIN, false, true );

		return $customer_id;
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
     * save_data function.
     * 
     * @access public
     * @return void
     */
    public function save_data() {
	    // Set cart data for 48 hours
	    set_transient( 'wc_session_' . $this->_customer_id, $this->_data, 172800 );
    }
}