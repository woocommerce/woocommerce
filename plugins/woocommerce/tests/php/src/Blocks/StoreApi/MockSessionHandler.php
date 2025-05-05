<?php

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use WC_Customer;
use WC_Session_Handler;
use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;


/**
 * Mock Session handler class. Required because PHPUnit doesn't allow cookies to be set in the response as it runs tests
 * in a single context.
 */
class MockSessionHandler extends WC_Session_Handler {

	/**
	 * @var array Mock cache for unit tests.
	 */
	protected $mock_cache = array();

	/**
	 * Override the set function to ensure the data is added to the cache. Without this the data is not saved and
	 * changing users doesn't work.
	 *
	 * @param string $key   Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		$this->_data[ $key ]                     = $value;
		$this->_dirty                            = true;
		$this->mock_cache[ $this->_customer_id ] = $this->_data;
	}

	/**
	 * Init hooks and session data.
	 *
	 * @since 3.3.0
	 */
	public function init() {
		$this->init_session_cookie();
		$this->set_customer_session_cookie( true );

		add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'wp', array( $this, 'maybe_set_customer_session_cookie' ), 99 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'maybe_update_nonce_user_logged_out' ), 10, 2 );
		}
	}

	/**
	 * Sets the session cookie on-demand (usually after adding an item to the cart).
	 *
	 * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 *
	 * @param bool $set Should the session cookie be set.
	 */
	public function set_customer_session_cookie( $set ) {
		if ( $set ) {
			$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
				$_COOKIE[ $this->_cookie ] = $cookie_value;
			}
		}
	}

	/**
	 * Save data and delete guest session.
	 *
	 * @param int $old_session_key session ID before user logs in.
	 */
	public function save_data( $old_session_key = 0 ) {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT INTO $this->_table (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
 					ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
					$this->_customer_id,
					maybe_serialize( $this->_data ),
					$this->_session_expiration
				)
			);

			$this->mock_cache[ $this->_customer_id ] = $this->_data;
			$this->_dirty                            = false;
			// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- Loose comparison is in parent class so we need to keep it here.
			if ( get_current_user_id() != $old_session_key && ! is_object( get_user_by( 'id', $old_session_key ) ) ) {
				$this->delete_session( $old_session_key );
			}
		}
	}

	/**
	 * Forget all session data without destroying it.
	 * Overriden in this mock because the parent method sets a cookie using wc_setcookie which doesn't work with PHPUnit.
	 */
	public function forget_session() {
		unset( $_COOKIE[ $this->_cookie ] );

		if ( ! is_admin() ) {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';

			wc_empty_cart();
		}

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
		global $wpdb;

		if ( Constants::is_defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = empty( $this->mock_cache[ $customer_id ] ) ? false : $this->mock_cache[ $customer_id ];

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) ); // @codingStandardsIgnoreLine.

			if ( is_null( $value ) ) {
				$value = $default;
			}

			$cache_duration = $this->_session_expiration - time();
			if ( 0 < $cache_duration ) {
				$this->mock_cache[ $customer_id ] = $value;
			}
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id Customer ID.
	 */
	public function delete_session( $customer_id ) {
		global $wpdb;

		unset( $this->mock_cache[ $customer_id ] );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $customer_id,
			)
		);
	}
}
