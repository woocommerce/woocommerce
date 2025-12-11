<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi;

use Automattic\Jetpack\Constants;
use WC_Session;

defined( 'ABSPATH' ) || exit;

/**
 * POSSessionHandler class
 *
 * A simple session handler for Point of Sale (POS) requests. Uses the user ID
 * as the session key, similar to how WC_Session_Handler works for logged-in users.
 *
 * This can be identified as a POS session, allowing the checkout flow to apply
 * different validation rules (e.g., skipping address requirements) for trusted
 * POS clients.
 */
final class POSSessionHandler extends WC_Session {
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
		$this->table = $GLOBALS['wpdb']->prefix . 'woocommerce_sessions';
	}

	/**
	 * Init hooks and session data.
	 */
	public function init() {
		$this->init_session_for_pos();
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
	}

	/**
	 * Initialize session for POS.
	 *
	 * Simple approach: use user ID as session key, prefixed with 'pos_'.
	 * This keeps POS sessions separate from regular web sessions.
	 */
	protected function init_session_for_pos() {
		$user_id = get_current_user_id();

		$this->_customer_id       = 'pos_' . $user_id;
		$this->session_expiration = time() + ( DAY_IN_SECONDS * 2 );
		$this->_data              = (array) $this->get_session( $this->_customer_id, array() );
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table is set in constructor from wpdb prefix.
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_value FROM {$this->table} WHERE session_key = %s",
				$customer_id
			)
		);

		if ( is_null( $value ) ) {
			$value = $default_value;
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Save data and delete user session.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table is set in constructor from wpdb prefix.
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$this->table} (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d) ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
					$this->_customer_id,
					maybe_serialize( $this->_data ),
					$this->session_expiration
				)
			);

			$this->_dirty = false;
		}
	}

	/**
	 * Destroy the current session.
	 *
	 * This allows starting a fresh transaction for the next POS customer.
	 */
	public function destroy_session(): void {
		$this->delete_session( $this->_customer_id );

		// Reset session data and mark as not dirty.
		$this->_data  = array();
		$this->_dirty = false;

		// Remove the shutdown hook so save_data() won't recreate the session.
		remove_action( 'shutdown', array( $this, 'save_data' ), 20 );
	}

	/**
	 * Delete a session from the database.
	 *
	 * @param string $customer_id Customer ID.
	 */
	public function delete_session( $customer_id ): void {
		global $wpdb;

		if ( ! $customer_id ) {
			return;
		}

		$wpdb->delete( $this->table, array( 'session_key' => $customer_id ) );
	}

	/**
	 * Check if this is a POS session.
	 *
	 * @return bool Always returns true for POS sessions.
	 */
	public function is_pos_session(): bool {
		return true;
	}

	/**
	 * Check if we have an active session.
	 *
	 * @return bool Always returns true for POS sessions.
	 */
	public function has_session(): bool {
		return true;
	}
}
