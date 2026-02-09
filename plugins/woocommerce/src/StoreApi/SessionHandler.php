<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Session;

defined( 'ABSPATH' ) || exit;

/**
 * SessionHandler class
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
	 * Returns the session.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default_value Default session value.

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
}
