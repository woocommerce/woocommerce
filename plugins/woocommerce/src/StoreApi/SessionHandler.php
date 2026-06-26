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

		$this->maybe_merge_guest_cart_on_login();
	}

	/**
	 * Merge a guest cart token into the logged-in user's session on first authenticated load.
	 *
	 * Token-based logins (JWT, OAuth, etc.) never fire the `wp_login` hook, so the
	 * `_woocommerce_load_saved_cart_after_login` flag the cookie session flow relies on to
	 * merge carts is never set. When an authenticated request arrives carrying a guest cart
	 * token, fold the guest cart into the user's session once and switch the session to the
	 * user, so the response returns a user-scoped cart token (see
	 * AbstractCartRoute::get_cart_token()).
	 *
	 * The guest session is consumed (deleted) as part of the merge. A repeated, stale guest
	 * token therefore loads an empty cart and cannot merge again, keeping the operation
	 * one-shot and preventing removed items from reappearing.
	 *
	 * @since 11.0.0
	 */
	protected function maybe_merge_guest_cart_on_login(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$guest_id = (string) $this->get_customer_id();

		// Only merge a genuine guest session token (t_...). A user-scoped token — the current
		// user's own, or another user's — must never be treated as a guest cart. This matches
		// WC_Session_Handler::is_customer_guest() and also covers an empty/absent token.
		if ( 't_' !== substr( $guest_id, 0, 2 ) ) {
			return;
		}

		$user_id    = (string) get_current_user_id();
		$guest_cart = (array) $this->get( 'cart', array() );

		// Switch this request to the user's own session.
		$this->_customer_id = $user_id;
		$this->_data        = (array) $this->get_session( $user_id, array() );

		// Nothing to merge when the guest session has no cart (it may not even exist), so leave
		// it untouched. get_cart_from_session() loads the saved cart on its own when the user's
		// session is empty.
		if ( empty( $guest_cart ) ) {
			return;
		}

		// Consume the guest session so a repeated, stale token cannot merge again.
		$this->delete_session( $guest_id );

		// Fold the saved cart and the active guest cart into the user's session. Later entries
		// win on key collision, so the active guest cart takes precedence over saved items.
		$saved_cart = $this->get_persistent_cart_contents( (int) $user_id );
		$user_cart  = (array) $this->get( 'cart', array() );
		$this->set( 'cart', array_merge( $saved_cart, $user_cart, $guest_cart ) );
	}

	/**
	 * Read the user's saved (persistent) cart contents.
	 *
	 * Mirrors the private WC_Cart_Session::get_saved_cart() so the merge can fold the
	 * persistent cart in directly, without depending on the wp_login-era
	 * `_woocommerce_load_saved_cart_after_login` flag being consumed elsewhere.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_persistent_cart_contents( int $user_id ): array {
		/**
		 * Filters whether the persistent cart is enabled.
		 *
		 * @since 3.4.0
		 * @param bool $enabled Whether the persistent cart is enabled. Default true.
		 */
		if ( ! apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) {
			return array();
		}

		$saved_cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

		if ( is_array( $saved_cart_meta ) && isset( $saved_cart_meta['cart'] ) ) {
			return array_filter( (array) $saved_cart_meta['cart'] );
		}

		return array();
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
