<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class.
 *
 * From 2.5 this uses a custom table for session storage. Based on https://github.com/kloon/woocommerce-large-sessions.
 *
 * @class    WC_Session_Handler
 * @version  2.5.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Session_Handler extends WC_Session {

	/** @var string cookie name */
	private $_cookie;

	/** @var string session due to expire timestamp */
	private $_session_expiring;

	/** @var string session expiration timestamp */
	private $_session_expiration;

	/** $var bool Bool based on whether a cookie exists **/
	private $_has_cookie = false;

	/** @var string Custom session table name */
	private $_table;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		global $wpdb;

		$this->_cookie = 'wp_woocommerce_session_' . COOKIEHASH;
		$this->_table  = $wpdb->prefix . 'woocommerce_sessions';

		if ( $cookie = $this->get_session_cookie() ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;

			// Update session if its close to expiring
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}

		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

		// Actions
		add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'woocommerce_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	/**
	 * Sets the session cookie on-demand (usually after adding an item to the cart).
	 *
	 * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 */
	public function set_customer_session_cookie( $set ) {
		if ( $set ) {
			// Set/renew our cookie
			$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			// Set the cookie
			wc_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );
		}
	}

	/**
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @return int|string
	 */
	public function generate_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			require_once( ABSPATH . 'wp-includes/class-phpass.php');
			$hasher = new PasswordHash( 8, false );
			return md5( $hasher->get_random_bytes( 32 ) );
		}
	}

	/**
	 * Get session cookie.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		// Validate hash
		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
	}

	/**
	 * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @return string
	 */
	private function get_cache_prefix() {
		return WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP );
	}

	/**
	 * Save data.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$session_id = $wpdb->get_var( $wpdb->prepare( "SELECT session_id FROM $this->_table WHERE session_key = %s;", $this->_customer_id ) );

			if ( $session_id ) {
				$wpdb->update(
					$this->_table,
					array(
						'session_key'    => $this->_customer_id,
						'session_value'  => maybe_serialize( $this->_data ),
						'session_expiry' => $this->_session_expiration
					),
					array( 'session_id' => $session_id ),
					array(
						'%s',
						'%s',
						'%d'
					),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$this->_table,
					array(
						'session_key'    => $this->_customer_id,
						'session_value'  => maybe_serialize( $this->_data ),
						'session_expiry' => $this->_session_expiration
					),
					array(
						'%s',
						'%s',
						'%d'
					)
				);
			}

			// Set cache
			wp_cache_set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, WC_SESSION_CACHE_GROUP, $this->_session_expiration - time() );

			// Mark session clean after saving
			$this->_dirty = false;
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		// Clear cookie
		wc_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'wc_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		// Clear cart
		wc_empty_cart();

		// Clear data
		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
	 *
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	/**
	 * Cleanup sessions.
	 */
	public function cleanup_sessions() {
		global $wpdb;

		if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {

			// Delete expired sessions
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) );

			// Invalidate cache
			WC_Cache_Helper::incr_cache_prefix( WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id
	 * @param mixed $default
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try get it from the cache, it will return false if not present or if object cache not in use
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, WC_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, WC_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id
	 */
	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, WC_SESSION_CACHE_GROUP );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $customer_id
			)
		);
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $customer_id
	 * @param int $timestamp
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp
			),
			array(
				'session_key' => $customer_id
			),
			array(
				'%d'
			)
		);
	}
}
