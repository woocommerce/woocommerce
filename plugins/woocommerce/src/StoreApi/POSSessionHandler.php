<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi;

use Automattic\Jetpack\Constants;
use WC_Session;

defined( 'ABSPATH' ) || exit;

/**
 * POSSessionHandler class
 *
 * A session handler for Point of Sale (POS) requests. This is based on the
 * Store API SessionHandler but can be identified as a POS session, allowing
 * the checkout flow to apply different validation rules (e.g., skipping
 * address requirements) for trusted POS clients.
 *
 * Note: This duplicates SessionHandler because that class is marked final.
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
	 * POS sessions use a transaction-based approach: each transaction gets a fresh
	 * session when the cart is empty. This prevents customer data (email, addresses)
	 * from carrying over between different customers' transactions.
	 *
	 * The session key includes a transaction ID that changes when:
	 * - The cart is empty (starting a new transaction)
	 * - No existing transaction ID exists
	 */
	protected function init_session_for_pos() {
		$user_id = get_current_user_id();

		// Check if we have an existing transaction ID for this user.
		$transaction_id = $this->get_existing_transaction_id( $user_id );

		// Load the existing session to check if cart is empty.
		if ( $transaction_id ) {
			$this->_customer_id       = 'pos_' . $user_id . '_' . $transaction_id;
			$this->session_expiration = time() + ( DAY_IN_SECONDS * 2 );
			$this->_data              = (array) $this->get_session( $this->get_customer_id(), array() );

			// If cart is empty, start a fresh transaction.
			$cart = $this->_data['cart'] ?? '';
			if ( empty( $cart ) || maybe_unserialize( $cart ) === array() ) {
				// Delete the old session from database to clean up.
				$this->delete_session( $this->get_customer_id() );
				$transaction_id = null; // Will generate new ID below.
			}
		}

		// Generate a new transaction ID if needed (new transaction).
		if ( ! $transaction_id ) {
			$transaction_id           = $this->generate_transaction_id();
			$this->_customer_id       = 'pos_' . $user_id . '_' . $transaction_id;
			$this->session_expiration = time() + ( DAY_IN_SECONDS * 2 );
			$this->_data              = array(); // Fresh session data - no cart, no customer.

			// Store the transaction ID so subsequent requests can find it.
			$this->save_transaction_id( $user_id, $transaction_id );
		}
	}

	/**
	 * Get an existing transaction ID for a user, if one exists.
	 *
	 * @param int $user_id The user ID.
	 * @return string|null The transaction ID or null if none exists.
	 */
	private function get_existing_transaction_id( int $user_id ): ?string {
		return get_transient( 'wc_pos_transaction_' . $user_id ) ?: null;
	}

	/**
	 * Save a transaction ID for a user.
	 *
	 * @param int    $user_id        The user ID.
	 * @param string $transaction_id The transaction ID.
	 */
	private function save_transaction_id( int $user_id, string $transaction_id ): void {
		set_transient( 'wc_pos_transaction_' . $user_id, $transaction_id, DAY_IN_SECONDS * 2 );
	}

	/**
	 * Generate a unique transaction ID.
	 *
	 * @return string A unique transaction ID.
	 */
	private function generate_transaction_id(): string {
		return wp_generate_uuid4();
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
	 * Save data and delete user session.
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

	/**
	 * Destroy the current session and clear the transaction ID.
	 *
	 * This allows starting a fresh transaction for the next POS customer.
	 */
	public function destroy_session(): void {
		$this->delete_session( $this->get_customer_id() );

		// Clear the transaction ID so the next request gets a fresh session.
		$user_id = get_current_user_id();
		delete_transient( 'wc_pos_transaction_' . $user_id );

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
	 * POS sessions are always active - if this handler is in use, the user
	 * is authenticated and has a valid session.
	 *
	 * Note: If we implement transaction-scoped sessions in the future, we may
	 * need to reconsider this and check for an active transaction instead.
	 *
	 * @return bool Always returns true for POS sessions.
	 */
	public function has_session(): bool {
		return true;
	}
}
