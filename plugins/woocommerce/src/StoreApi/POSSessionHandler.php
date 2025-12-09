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
	 * For POS sessions, we use the authenticated user's ID directly
	 * rather than relying on a cart token from headers.
	 */
	protected function init_session_for_pos() {
		// Use the authenticated user's ID for the session.
		$user_id = get_current_user_id();

		// Generate a unique session key for this POS user to avoid conflicts with their web cart.
		$this->_customer_id = 'pos_' . $user_id;

		// Set expiration to 48 hours from now (same as default cart token).
		$this->session_expiration = time() + ( DAY_IN_SECONDS * 2 );

		// Load existing session data if any.
		$this->_data = (array) $this->get_session( $this->get_customer_id(), array() );
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
	 * Check if this is a POS session.
	 *
	 * @return bool Always returns true for POS sessions.
	 */
	public function is_pos_session(): bool {
		return true;
	}
}
