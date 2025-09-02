<?php
/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class.
 *
 * From 2.5 this uses a custom table for session storage. Based on https://github.com/kloon/woocommerce-large-sessions.
 *
 * @class    WC_Session_Handler
 * @package  WooCommerce\Classes
 */

declare(strict_types=1);

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Session handler class.
 */
class WC_Session_Handler extends WC_Session {

	/**
	 * Cookie name used for the session.
	 *
	 * @var string cookie name
	 */
	protected $_cookie = ''; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Stores session expiry.
	 *
	 * @var int session due to expire timestamp
	 */
	protected $_session_expiring = 0; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Stores session due to expire timestamp.
	 *
	 * @var int session expiration timestamp
	 */
	protected $_session_expiration = 0; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * True when the cookie exists.
	 *
	 * @var bool Based on whether a cookie exists.
	 */
	protected $_has_cookie = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Table name for session data.
	 *
	 * @var string Custom session table name
	 */
	protected $_table = ''; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		/**
		 * Filter the cookie name.
		 *
		 * @since 3.6.0
		 *
		 * @param string $cookie Cookie name.
		 */
		$this->_cookie = (string) apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );
		$this->_table  = $GLOBALS['wpdb']->prefix . 'woocommerce_sessions';
		$this->set_session_expiration();
	}

	/**
	 * Init hooks and session data.
	 *
	 * @since 3.3.0
	 */
	public function init() {
		$this->init_hooks();
		$this->init_session();
	}

	/**
	 * Initialize the hooks.
	 */
	protected function init_hooks() {
		add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'wp', array( $this, 'maybe_set_customer_session_cookie' ), 99 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'maybe_update_nonce_user_logged_out' ), 10, 2 );
		}
	}

	/**
	 * Initialize the session from either the request or the cookie.
	 */
	private function init_session() {
		if ( ! $this->init_session_from_request() ) {
			$this->init_session_cookie();
		}
	}

	/**
	 * Initialize the session from the query string parameter.
	 *
	 * If the current user is logged in, the token session will replace the current user's session.
	 * If the current user is logged out, the token session will be cloned to a new session.
	 *
	 * Only guest sessions are restored, hence the check for the t_ prefix on the customer ID.
	 *
	 * @return bool
	 */
	private function init_session_from_request() {
		$session_token = wc_clean( wp_unslash( $_GET['session'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $session_token ) || ! CartTokenUtils::validate_cart_token( $session_token ) ) {
			return false;
		}

		$payload = CartTokenUtils::get_cart_token_payload( $session_token );

		if ( ! $this->is_customer_guest( $payload['user_id'] ) || ! $this->session_exists( $payload['user_id'] ) ) {
			return false;
		}

		// Check to see if the current user has a session before proceeding with token handling.
		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			// User owns this token. Return and use cookie session.
			if ( $cookie[0] === $payload['user_id'] ) {
				return false;
			}

			$cookie_session_data = (array) $this->get_session( $cookie[0], array() );

			// Cookie session was originally created via this token. Return and use cookie session to prevent creating a new clone.
			if ( isset( $cookie_session_data['previous_customer_id'] ) && $cookie_session_data['previous_customer_id'] === $payload['user_id'] ) {
				return false;
			}
		}

		// Generate new customer ID for the new session before cloning the data.
		$this->_customer_id = $this->generate_customer_id();
		$this->set_customer_session_cookie( true );
		$this->clone_session_data( $payload['user_id'] );

		return true;
	}

	/**
	 * Setup cookie and customer ID.
	 *
	 * @since 3.6.0
	 */
	public function init_session_cookie() {
		$cookie = $this->get_session_cookie();

		if ( ! $cookie ) {
			// If there is no cookie, generate a new session/customer ID.
			$this->_customer_id = $this->generate_customer_id();
			$this->_data        = $this->get_session_data();
			return;
		}

		// Customer ID will be an MD5 hash id this is a guest session.
		$this->_customer_id        = $cookie[0];
		$this->_session_expiration = (int) $cookie[1];
		$this->_session_expiring   = (int) $cookie[2];
		$this->_has_cookie         = true;

		$this->restore_session_data();

		/**
		 * This clears the session if the cookie is invalid.
		 *
		 * Previously this also cleared the session when $this->_data was empty, and the cart was not yet initialised,
		 * however this caused a conflict with WooCommerce Payments session handler which overrides this class.
		 *
		 * Ref: https://github.com/woocommerce/woocommerce/pull/57652
		 * See also: https://github.com/woocommerce/woocommerce/pull/59530
		 */
		if ( ! $this->is_session_cookie_valid() ) {
			$this->destroy_session();
		}

		// If the user logs in, update session.
		if ( is_user_logged_in() && (string) get_current_user_id() !== $this->get_customer_id() ) {
			$this->migrate_guest_session_to_user_session();
		}

		// Update session if its close to expiring.
		if ( $this->is_session_expiring() ) {
			$this->set_session_expiration();
			$this->update_session_timestamp( $this->get_customer_id(), $this->_session_expiration );
		}
	}

	/**
	 * Clones a session to the current session. Exclude customer details for privacy reasons.
	 *
	 * @param string $clone_from_customer_id The customer ID to clone from.
	 */
	private function clone_session_data( string $clone_from_customer_id ) {
		$session_data                         = (array) $this->get_session( $clone_from_customer_id, array() );
		$session_data['previous_customer_id'] = $clone_from_customer_id;
		$session_data                         = array_diff_key( $session_data, array( 'customer' => true ) );
		$this->_data                          = $session_data;
		$this->_dirty                         = true;
		$this->save_data();
	}

	/**
	 * Migrates a guest session to the current user session.
	 */
	private function migrate_guest_session_to_user_session() {
		$guest_session_id = $this->_customer_id;
		$user_session_id  = (string) get_current_user_id();

		$this->_data        = $this->get_session( $guest_session_id, array() );
		$this->_dirty       = true;
		$this->_customer_id = $user_session_id;
		$this->save_data( $guest_session_id );

		/**
		 * Fires after a customer has logged in, and their guest session id has been
		 * deleted with its data migrated to a customer id.
		 *
		 * This hook gives extensions the chance to connect the old session id to the
		 * customer id, if the key is being used externally.
		 *
		 * @since 8.8.0
		 *
		 * @param string $guest_session_id The former session ID, as generated by `::generate_customer_id()`.
		 * @param string $user_session_id The Customer ID that the former session was converted to.
		 */
		do_action( 'woocommerce_guest_session_to_user_id', $guest_session_id, $this->_customer_id );
	}

	/**
	 * Restore the session data from the database.
	 *
	 * @since 10.0.0
	 */
	private function restore_session_data() {
		$session_data = $this->get_session_data();

		/**
		 * Filters the session data when restoring from storage during initialization.
		 *
		 * This filter allows you to:
		 * 1. Modify the session data before it's loaded, including adding or removing specific session data entries
		 * 2. Clear the entire session by returning an empty array
		 *
		 * Note: If the filtered data is empty, the session will be destroyed and the
		 * guest's session cookie will be removed. This can be useful for high-traffic
		 * sites that prioritize page caching over maintaining all session data.
		 *
		 * @since 9.9.0
		 *
		 * @param array $session_data The session data loaded from storage.
		 * @return array Modified session data to be used for initialization.
		 */
		$this->_data = (array) apply_filters( 'woocommerce_restored_session_data', $session_data );
	}

	/**
	 * Checks if session cookie is expired, or belongs to a logged out user.
	 *
	 * @return bool Whether session cookie is valid.
	 */
	private function is_session_cookie_valid() {
		// If session is expired, session cookie is invalid.
		if ( time() > $this->_session_expiration ) {
			return false;
		}

		// If user has logged out, session cookie is invalid.
		if ( ! is_user_logged_in() && ! $this->is_customer_guest( $this->get_customer_id() ) ) {
			return false;
		}

		// Session from a different user is not valid. (Although from a guest user will be valid).
		if ( is_user_logged_in() && ! $this->is_customer_guest( $this->get_customer_id() ) && (string) get_current_user_id() !== $this->get_customer_id() ) {
			return false;
		}

		return true;
	}

	/**
	 * Hooks into the wp action to maybe set the session cookie if the user is on a certain page e.g. a checkout endpoint.
	 *
	 * Certain gateways may rely on sessions and this ensures a session is present even if the customer does not have a
	 * cart.
	 */
	public function maybe_set_customer_session_cookie() {
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$this->set_customer_session_cookie( true );
		}
	}

	/**
	 * Hash a value using wp_fast_hash (from WP 6.8 onwards).
	 *
	 * This method can be removed when the minimum version supported is 6.8.
	 *
	 * @param string $message Value to hash.
	 * @return string Hashed value.
	 */
	private function hash( string $message ) {
		if ( function_exists( 'wp_fast_hash' ) ) {
			return wp_fast_hash( $message );
		}
		return hash_hmac( 'md5', $message, wp_hash( $message ) );
	}

	/**
	 * Verify a hash using wp_verify_fast_hash (from WP 6.8 onwards).
	 *
	 * This method can be removed when the minimum version supported is 6.8.
	 *
	 * @param string $message Message to verify.
	 * @param string $hash Hash to verify.
	 * @return bool Whether the hash is valid.
	 */
	private function verify_hash( string $message, string $hash ) {
		if ( function_exists( 'wp_verify_fast_hash' ) ) {
			return wp_verify_fast_hash( $message, $hash );
		}
		return hash_equals( hash_hmac( 'md5', $message, wp_hash( $message ) ), $hash );
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
			$cookie_hash  = $this->hash( $this->get_customer_id() . '|' . (string) $this->_session_expiration );
			$cookie_value = $this->get_customer_id() . '|' . (string) $this->_session_expiration . '|' . (string) $this->_session_expiring . '|' . $cookie_hash;

			if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
				wc_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, $this->use_secure_cookie(), true );
			}

			$this->_has_cookie = true;
		}
	}

	/**
	 * Should the session cookie be secure?
	 *
	 * @since 3.6.0
	 * @return bool
	 */
	protected function use_secure_cookie() {
		/**
		 * Filter whether to use a secure cookie.
		 *
		 * @since 3.6.0
		 *
		 * @param bool $use_secure_cookie Whether to use a secure cookie.
		 */
		return (bool) apply_filters( 'wc_session_use_secure_cookie', wc_site_is_https() && is_ssl() );
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
	 * Checks if the session is expiring.
	 *
	 * @return bool Whether session is expiring.
	 */
	private function is_session_expiring() {
		return time() > $this->_session_expiring;
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$default_expiring_seconds   = DAY_IN_SECONDS;
		$default_expiration_seconds = is_user_logged_in() ? WEEK_IN_SECONDS : 2 * DAY_IN_SECONDS;
		$max_expiration_seconds     = MONTH_IN_SECONDS;
		$max_expiring_seconds       = $max_expiration_seconds - DAY_IN_SECONDS;
		$session_limit_exceeded     = false;

		/**
		 * Filters the session expiration.
		 *
		 * @since 5.0.0
		 * @param int $expiration_seconds The expiration time in seconds.
		 */
		$expiring_seconds = intval( apply_filters( 'wc_session_expiring', $default_expiring_seconds ) ) ?: $default_expiring_seconds; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		if ( $expiring_seconds > $max_expiring_seconds ) {
			$expiring_seconds       = $max_expiring_seconds;
			$session_limit_exceeded = true;
		}
		/**
		 * Filters the session expiration.
		 *
		 * @since 5.0.0
		 * @param int $expiration_seconds The expiration time in seconds.
		 */
		$expiration_seconds = intval( apply_filters( 'wc_session_expiration', $default_expiration_seconds ) ) ?: $default_expiration_seconds; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		// We limit the expiration time to 30 days to avoid performance issues and the session table growing too large.
		if ( $expiration_seconds > $max_expiration_seconds ) {
			$expiration_seconds     = $max_expiration_seconds;
			$session_limit_exceeded = true;
		}

		if ( $session_limit_exceeded ) {
			$transient_key = 'wc_session_handler_warning';
			if ( false === get_transient( $transient_key ) ) {
				wc_get_logger()->warning( sprintf( 'Keeping sessions for longer than %d days results in performance isues, expiry has been capped.', $max_expiration_seconds / DAY_IN_SECONDS ), array( 'source' => 'wc_session_handler' ) );
				set_transient( $transient_key, true, $max_expiration_seconds );
			}
		}

		// If the expiring time is greater than the expiration time, set the expiring time to 90% of the expiration time.
		if ( $expiring_seconds > $expiration_seconds ) {
			$expiring_seconds = $expiration_seconds * 0.9;
		}

		$this->_session_expiring   = time() + $expiring_seconds;
		$this->_session_expiration = time() + $expiration_seconds;
	}

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		return is_user_logged_in() ? (string) get_current_user_id() : wc_rand_hash( 't_', 30 );
	}

	/**
	 * Checks if this is an auto-generated customer ID.
	 *
	 * @param string $customer_id Customer ID to check.
	 * @return bool Whether customer ID is randomly generated.
	 */
	private function is_customer_guest( $customer_id ) {
		return empty( $customer_id ) || 't_' === substr( $customer_id, 0, 2 );
	}

	/**
	 * Get session unique ID for requests if session is initialized or user ID if logged in.
	 * Introduced to help with unit tests.
	 *
	 * @since 5.3.0
	 * @return string
	 */
	public function get_customer_unique_id() {
		$customer_id = '';

		if ( $this->has_session() && $this->get_customer_id() ) {
			$customer_id = $this->get_customer_id();
		} elseif ( is_user_logged_in() ) {
			$customer_id = (string) get_current_user_id();
		}

		return $customer_id;
	}

	/**
	 * Get the session cookie, if set. Otherwise return false.
	 *
	 * Session cookies without a customer ID are invalid.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wc_clean( wp_unslash( (string) $_COOKIE[ $this->_cookie ] ) ) : '';

		if ( empty( $cookie_value ) ) {
			return false;
		}

		// Check if the cookie value contains '||' instead of '|' to support older versions of the cookie. This can be removed in WC 11.0.0.
		if ( strpos( $cookie_value, '||' ) !== false ) {
			$parsed_cookie = explode( '||', $cookie_value );
		} else {
			$parsed_cookie = explode( '|', $cookie_value );
		}

		if ( count( $parsed_cookie ) !== 4 ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = $parsed_cookie;

		if ( empty( $customer_id ) ) {
			return false;
		}

		$verify_hash = $this->verify_hash( $customer_id . '|' . $session_expiration, $cookie_hash );

		if ( ! $verify_hash ) {
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
		return $this->has_session() ? (array) $this->get_session( $this->get_customer_id(), array() ) : array();
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
	 * Save data and delete guest session.
	 *
	 * @param string|mixed $old_session_key Optional session ID prior to user log-in.  If $old_session_key is not tied
	 *                                      to a user, the session will be deleted with the assumption that it was migrated
	 *                                      to the current session being saved.
	 */
	public function save_data( $old_session_key = '' ) {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
 					ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)',
					$this->_table,
					$this->get_customer_id(),
					maybe_serialize( $this->_data ),
					$this->_session_expiration
				)
			);
			wp_cache_set( $this->get_cache_prefix() . $this->get_customer_id(), $this->_data, WC_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
			$this->_dirty = false;

			/**
			 * Ideally, the removal of guest session data migrated to a logged-in user would occur within
			 * self::init_session_cookie() upon user login detection initially occurs. However, since some third-party
			 * extensions override this method, relocating this logic could break backward compatibility.
			 */
			if ( ! empty( $old_session_key ) && $this->get_customer_id() !== $old_session_key && ! is_object( get_user_by( 'id', $old_session_key ) ) ) {
				$this->delete_session( $old_session_key );
			}
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		$this->delete_session( $this->get_customer_id() );
		$this->forget_session();
		$this->set_session_expiration();
	}

	/**
	 * Forget all session data without destroying it.
	 */
	public function forget_session() {
		wc_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, $this->use_secure_cookie(), true );

		if ( ! is_admin() ) {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			wc_empty_cart();
		}

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
		$this->_has_cookie  = false;
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce to manage cart and more using the customer/session ID.
	 * This filter runs everything `wp_verify_nonce()` and `wp_create_nonce()` gets called.
	 *
	 * @since 5.3.0
	 * @param int        $uid    User ID.
	 * @param int|string $action The nonce action.
	 * @return int|string
	 */
	public function maybe_update_nonce_user_logged_out( $uid, $action ) {
		if ( is_string( $action ) && StringUtil::starts_with( $action, 'woocommerce' ) ) {
			return $this->has_session() && $this->get_customer_id() ? $this->get_customer_id() : $uid;
		}
		return $uid;
	}

	/**
	 * Cleanup session data from the database and clear caches.
	 */
	public function cleanup_sessions() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE session_expiry < %d', $this->_table, time() ) );

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( WC_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default_value Default session value.
	 * @return mixed Returns either the session data or the default value. Returns false if WP setup is in progress.
	 */
	public function get_session( $customer_id, $default_value = false ) {
		global $wpdb;

		if ( Constants::is_defined( 'WP_SETUP_CONFIG' ) ) {
			return $default_value;
		}

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, WC_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT session_value FROM %i WHERE session_key = %s', $this->_table, $customer_id ) );

			if ( is_null( $value ) ) {
				$value = $default_value;
			}

			$cache_duration = $this->_session_expiration - time();
			if ( 0 < $cache_duration ) {
				wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, WC_SESSION_CACHE_GROUP, $cache_duration );
			}
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param string $customer_id Customer session ID.
	 */
	public function delete_session( $customer_id ) {
		if ( ! $customer_id ) {
			return;
		}
		$GLOBALS['wpdb']->delete( $this->_table, array( 'session_key' => $customer_id ) );
		wp_cache_delete( $this->get_cache_prefix() . $customer_id, WC_SESSION_CACHE_GROUP );
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $customer_id Customer ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {
		if ( ! $customer_id ) {
			return;
		}
		$GLOBALS['wpdb']->update( $this->_table, array( 'session_expiry' => $timestamp ), array( 'session_key' => $customer_id ), array( '%d' ) );
	}

	/**
	 * Check if a session exists in the database.
	 *
	 * @param string $customer_id Customer ID.
	 * @return bool
	 */
	private function session_exists( $customer_id ) {
		return $customer_id && null !== $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( 'SELECT session_key FROM %i WHERE session_key = %s', $this->_table, $customer_id ) );
	}
}
