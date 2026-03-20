<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Session;
defined( 'ABSPATH' ) || exit;

/**
 * SessionHandler class
 *
 * Token-based session handler for the Store API. Unlike WC_Session_Handler which
 * uses browser cookies, this handler uses an HTTP_CART_TOKEN header (JWT-like) to
 * identify sessions. It shares the same database table but has no cookie, cron,
 * or cache layer.
 *
 * @since 10.7.0
 */
final class SessionHandler extends WC_Session {
	/**
	 * Token from HTTP headers.
	 *
	 * @var string
	 */
	protected $token = '';

	/**
	 * Table name for session data.
	 *
	 * @var string Custom session table name
	 */
	protected $table = '';

	/**
	 * Expiration timestamp.
	 *
	 * @var int
	 */
	protected $session_expiration = 0;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		$this->token = wc_clean( wp_unslash( $_SERVER['HTTP_CART_TOKEN'] ?? '' ) );
		$this->table = $GLOBALS['wpdb']->prefix . 'woocommerce_sessions';
	}

	/**
	 * Init hooks and session data.
	 */
	public function init() {
		$this->init_session_from_token();
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
	}

	/**
	 * Process the token header to load the correct session.
	 */
	protected function init_session_from_token() {
		$payload = CartTokenUtils::get_cart_token_payload( $this->token );

		$this->_customer_id       = $payload['user_id'];
		$this->session_expiration = $payload['exp'];
		$this->_data              = (array) $this->get_session( $this->get_customer_id(), array() );
	}

	/**
	 * Return true if the current user has an active session.
	 *
	 * @return bool
	 */
	public function has_session() {
		return ! empty( $this->_customer_id );
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
	 * Get session unique ID for requests if session is initialized or user ID if logged in.
	 *
	 * @return string
	 */
	public function get_customer_unique_id() {
		if ( $this->has_session() && $this->get_customer_id() ) {
			return $this->get_customer_id();
		}
		return is_user_logged_in() ? (string) get_current_user_id() : '';
	}

	/**
	 * Get session data fresh from storage.
	 *
	 * This re-reads session data from the database rather than returning
	 * in-memory data, ensuring the latest persisted state is returned.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->get_customer_id(), array() ) : array();
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default_value Default session value.
	 *
	 * @return mixed Returns either the session data or the default value. Returns false if WP setup is in progress.
	 */
	public function get_session( $customer_id, $default_value = false ) {
		global $wpdb;

		// This mimics behaviour from default WC_Session_Handler class. There will be no sessions retrieved while WP setup is due.
		if ( Constants::is_defined( 'WP_SETUP_CONFIG' ) ) {
			return $default_value;
		}

		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT session_value FROM %i WHERE session_key = %s',
				$this->table,
				$customer_id
			)
		);

		if ( is_null( $value ) ) {
			$value = $default_value;
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Destroy all session data.
	 *
	 * @return void
	 */
	public function destroy_session() {
		$this->delete_session( $this->get_customer_id() );
		$this->forget_session();
	}

	/**
	 * Forget all session data without destroying persisted storage.
	 *
	 * @return void
	 */
	public function forget_session() {
		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = null;
	}

	/**
	 * Delete the session from the database.
	 *
	 * @param string $customer_id Customer session ID.
	 * @return void
	 */
	public function delete_session( $customer_id ) {
		if ( ! $customer_id ) {
			return;
		}
		$GLOBALS['wpdb']->delete( $this->table, array( 'session_key' => $customer_id ) );
	}

	/**
	 * Save data and delete user session.
	 *
	 * @return void
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d) ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)',
					$this->table,
					$this->get_customer_id(),
					maybe_serialize( $this->_data ),
					$this->session_expiration
				)
			);

			$this->_dirty = false;
		}
	}
}
