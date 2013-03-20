<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class
 *
 * Long term plan will be, if https://github.com/ericmann/wp-session-manager/ gains traction
 * in WP core, this will be switched out to use it and maintain backwards compatibility :)
 *
 * Partly based on WP SESSION by Eric Mann.
 *
 * @class 		WC_Session_Handler
 * @version		2.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Session_Handler extends WC_Session {

	/** cookie name */
	private $_cookie;

	/** session due to expire timestamp */
	private $_session_expiring;

	/** session expiration timestamp */
	private $_session_expiration;

	/**
	 * Constructor for the session class.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->_cookie = 'wc_session_cookie_' . COOKIEHASH;

		if ( $cookie = $this->get_session_cookie() ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];

			// Update session if its close to expiring
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				update_option( '_wc_session_expires_' . $this->_customer_id, $this->_session_expiration );
			}

		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

    	// Set/renew our cookie
    	$to_hash      = $this->_customer_id . $this->_session_expiration;
    	$cookie_hash  = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
    	$cookie_value = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;

    	setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, COOKIEPATH, COOKIE_DOMAIN, false, true );

    	// Actions
    	add_action( 'woocommerce_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
    	add_action( 'shutdown', array( $this, 'save_data' ), 20 );
    }

    /**
     * set_session_expiration function.
     *
     * @access private
     * @return void
     */
    private function set_session_expiration() {
	    $this->_session_expiring    = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) ); // 47 Hours
		$this->_session_expiration  = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) ); // 48 Hours
    }

	/**
	 * generate_customer_id function.
	 *
	 * @access private
	 * @return mixed
	 */
	private function generate_customer_id() {
		if ( is_user_logged_in() )
			return get_current_user_id();
		else
			return wp_generate_password( 32 );
	}

	/**
	 * get_session_cookie function.
	 *
	 * @access private
	 * @return mixed
	 */
	private function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) )
			return false;

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		// Validate hash
		$to_hash = $customer_id . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( $hash != $cookie_hash )
			return false;

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * get_session_data function.
	 *
	 * @access private
	 * @return array
	 */
	private function get_session_data() {
		return get_option( '_wc_session_' . $this->_customer_id, array() );
	}

    /**
     * save_data function.
     *
     * @access public
     * @return void
     */
    public function save_data() {
    	// Dirty if something changed - prevents saving nothing new
    	if ( $this->_dirty ) {

    		$session_option = '_wc_session_' . $this->_customer_id;
    		$session_expiry_option = '_wc_session_expires_' . $this->_customer_id;

	    	if ( false === get_option( $session_option ) ) {
		    	add_option( $session_option, $this->_data, '', 'no' );
		    	add_option( $session_expiry_option, $this->_session_expiration, '', 'no' );
	    	} else {
		    	update_option( $session_option, $this->_data );
	    	}
	    }
    }

    /**
	 * cleanup_sessions function.
	 *
	 * @access public
	 * @return void
	 */
	public function cleanup_sessions() {
		global $wpdb;

		$now = time();

		$session_names = $wpdb->get_col( $wpdb->prepare( "
			SELECT
				a.option_name
			FROM
				{$wpdb->options} a
			WHERE
				a.option_name LIKE '_wc_session_expires_%%'
				AND a.option_value < %s
		", $now ) );

		// Clear cache
		foreach ( $session_names as $session_name )
			wp_cache_delete( substr( $session_name, 10 ), 'options' );

		// Delete rows
		$wpdb->query( $wpdb->prepare( "
			DELETE
				a, b
			FROM
				{$wpdb->options} a, {$wpdb->options} b
			WHERE
				a.option_name LIKE '_wc_session_%%' AND
				b.option_name = CONCAT(
					'_wc_session_expires_',
					SUBSTRING(
						a.option_name,
						CHAR_LENGTH('_wc_session_') + 1
					)
				)
				AND b.option_value < %s
		", $now ) );
	}
}