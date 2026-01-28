<?php
/**
 * SessionClearanceManager class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Manages session clearance state for fraud protection.
 *
 * This class handles the session status tracking for fraud protection decisions,
 * managing three possible states: pending, allowed, and blocked. It integrates
 * with WooCommerce sessions and uses the FraudProtectionController logging helper
 * to maintain consistent audit logs.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class SessionClearanceManager {

	/**
	 * Session key for storing clearance status.
	 */
	private const SESSION_KEY = '_fraud_protection_clearance_status';

	/**
	 * Session status: pending clearance.
	 */
	public const STATUS_PENDING = 'pending';

	/**
	 * Session status: allowed.
	 */
	public const STATUS_ALLOWED = 'allowed';

	/**
	 * Session status: blocked.
	 */
	public const STATUS_BLOCKED = 'blocked';

	/**
	 * Default session status.
	 * PENDING means payment methods are hidden until verified.
	 */
	public const DEFAULT_STATUS = self::STATUS_PENDING;

	/**
	 * Session key for storing Blackbox session ID.
	 */
	private const BLACKBOX_SESSION_KEY = '_fraud_protection_blackbox_session_id';

	/**
	 * Session key for storing verification timestamp.
	 */
	private const VERIFICATION_TIMESTAMP_KEY = '_fraud_protection_verification_timestamp';

	/**
	 * Session key for storing the event queue.
	 */
	private const EVENT_QUEUE_KEY = '_fraud_protection_event_queue';

	/**
	 * Maximum number of events to store in the queue to prevent session bloat.
	 */
	private const MAX_EVENT_QUEUE_SIZE = 50;

	/**
	 * Check if the current session is allowed.
	 *
	 * @return bool True if session is allowed, false otherwise.
	 */
	public function is_session_allowed(): bool {
		$status = $this->get_session_status();
		return self::STATUS_ALLOWED === $status;
	}

	/**
	 * Check if the current session is blocked.
	 *
	 * @return bool True if session is blocked, false otherwise.
	 */
	public function is_session_blocked(): bool {
		$status = $this->get_session_status();
		return self::STATUS_BLOCKED === $status;
	}

	/**
	 * Check if the current session is pending verification.
	 *
	 * @return bool True if session is pending, false otherwise.
	 */
	public function is_session_pending(): bool {
		$status = $this->get_session_status();
		return self::STATUS_PENDING === $status;
	}

	/**
	 * Check if payment methods should be rendered.
	 *
	 * Payment methods should only be shown when the session is explicitly
	 * ALLOWED. PENDING and BLOCKED sessions should not see payment methods.
	 *
	 * @return bool True if payment methods should be rendered, false otherwise.
	 */
	public function should_render_payment_methods(): bool {
		$status = $this->get_session_status();
		return self::STATUS_ALLOWED === $status;
	}

	/**
	 * Mark the current session as allowed.
	 *
	 * @return void
	 */
	public function allow_session(): void {
		$this->set_session_status( self::STATUS_ALLOWED );
		$this->log_session_update_event( 'allowed' );
	}

	/**
	 * Mark the current session as pending (challenge required).
	 *
	 * @return void
	 */
	public function challenge_session(): void {
		$this->set_session_status( self::STATUS_PENDING );
		$this->log_session_update_event( 'challenged' );
	}

	/**
	 * Mark the current session as blocked and empty the cart.
	 *
	 * Emptying the cart prevents express payment methods (e.g., PayPal) from
	 * rendering on cart pages, as they are loaded via third-party SDKs that
	 * don't respect WooCommerce's payment method filtering.
	 *
	 * @return void
	 */
	public function block_session(): void {
		$this->set_session_status( self::STATUS_BLOCKED );
		$this->log_session_update_event( 'blocked' );
		$this->empty_cart();
	}

	/**
	 * Get the current session clearance status.
	 *
	 * @return string One of: pending, allowed, blocked.
	 */
	public function get_session_status(): string {
		if ( ! $this->is_session_available() ) {
			return self::DEFAULT_STATUS;
		}

		$status = WC()->session->get( self::SESSION_KEY, self::DEFAULT_STATUS );

		// Validate status value - return default for invalid values.
		if ( ! in_array( $status, array( self::STATUS_PENDING, self::STATUS_ALLOWED, self::STATUS_BLOCKED ), true ) ) {
			return self::DEFAULT_STATUS;
		}

		return $status;
	}

	/**
	 * Set the session clearance status.
	 *
	 * @param string $status One of: pending, allowed, blocked.
	 * @return void
	 */
	private function set_session_status( string $status ): void {
		if ( ! $this->is_session_available() ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, $status );

		// Ensure session cookie is set so the session persists across page loads.
		// This is important because fraud protection may set session status before
		// any cart action triggers the cookie to be set.
		// Skip cookie setting if headers have already been sent (e.g., in test environment).
		if ( WC()->session instanceof \WC_Session_Handler ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	/**
	 * Reset the session clearance status to default (allowed).
	 *
	 * @return void
	 */
	public function reset_session(): void {
		$this->set_session_status( self::DEFAULT_STATUS );
	}

	/**
	 * Store the Blackbox session ID.
	 *
	 * @param string $session_id The Blackbox session ID.
	 * @return void
	 */
	public function set_blackbox_session_id( string $session_id ): void {
		if ( ! $this->is_session_available() ) {
			return;
		}
		WC()->session->set( self::BLACKBOX_SESSION_KEY, $session_id );
		WC()->session->set( self::VERIFICATION_TIMESTAMP_KEY, time() );
	}

	/**
	 * Check if session was verified recently.
	 *
	 * Used to prevent infinite reload loops on add-payment-method page.
	 * If verification happened within the given threshold, we skip re-verification.
	 *
	 * @param int $seconds The time threshold in seconds.
	 * @return bool True if verified within the threshold, false otherwise.
	 */
	public function was_verified_recently( int $seconds = 10 ): bool {
		if ( ! $this->is_session_available() ) {
			return false;
		}
		$timestamp = WC()->session->get( self::VERIFICATION_TIMESTAMP_KEY );
		if ( ! $timestamp ) {
			return false;
		}
		return ( time() - (int) $timestamp ) < $seconds;
	}

	/**
	 * Get the stored Blackbox session ID.
	 *
	 * @return string|null The Blackbox session ID, or null if not set.
	 */
	public function get_blackbox_session_id(): ?string {
		if ( ! $this->is_session_available() ) {
			return null;
		}
		return WC()->session->get( self::BLACKBOX_SESSION_KEY );
	}

	/**
	 * Add an event to the queue.
	 *
	 * Events are stored in the session until checkout, when they are all sent
	 * together with the checkout event. Each event is timestamped when queued.
	 *
	 * @param string $event_type The type of event being queued.
	 * @param array  $event_data The collected event data.
	 * @return void
	 */
	public function queue_event( string $event_type, array $event_data ): void {
		if ( ! $this->is_session_available() ) {
			return;
		}

		$queue   = $this->get_event_queue();
		$queue[] = array(
			'event_type' => $event_type,
			'timestamp'  => gmdate( 'c' ),
			'event_data' => $event_data,
		);

		// Limit queue size to prevent session bloat - keep most recent events.
		if ( count( $queue ) > self::MAX_EVENT_QUEUE_SIZE ) {
			$queue = array_slice( $queue, -self::MAX_EVENT_QUEUE_SIZE );
		}

		WC()->session->set( self::EVENT_QUEUE_KEY, $queue );
	}

	/**
	 * Get all queued events.
	 *
	 * @return array Array of queued events, each with event_type, timestamp, and event_data.
	 */
	public function get_event_queue(): array {
		if ( ! $this->is_session_available() ) {
			return array();
		}
		return WC()->session->get( self::EVENT_QUEUE_KEY, array() );
	}

	/**
	 * Clear the event queue.
	 *
	 * Should be called after successfully sending events to the API.
	 *
	 * @return void
	 */
	public function clear_event_queue(): void {
		if ( ! $this->is_session_available() ) {
			return;
		}
		WC()->session->set( self::EVENT_QUEUE_KEY, array() );
	}

	/**
	 * Ensure cart and session are available.
	 *
	 * Loads cart if not already loaded, which initializes session for both
	 * traditional (cookie) and Store API (token) flows.
	 *
	 * @return void
	 */
	public function ensure_cart_loaded(): void {
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			WC()->call_function( 'wc_load_cart' );
		}
	}

	/**
	 * Check if WooCommerce session is available.
	 *
	 * @return bool True if session is available.
	 */
	private function is_session_available(): bool {
		$this->ensure_cart_loaded();
		return WC()->session instanceof \WC_Session;
	}

	/**
	 * Get a unique identifier for the current session.
	 *
	 * @return string Session identifier.
	 */
	public function get_session_id(): string {
		if ( ! $this->is_session_available() ) {
			return 'no-session';
		}

		// Use or generate a stable session ID for tracking consistency.
		$fraud_customer_session_id = WC()->session->get( '_fraud_protection_customer_session_id' );
		if ( ! $fraud_customer_session_id ) {
			$fraud_customer_session_id = WC()->call_function( 'wc_rand_hash', 'customer_', 30 );
			WC()->session->set( '_fraud_protection_customer_session_id', $fraud_customer_session_id );
		}
		return $fraud_customer_session_id;
	}

	/**
	 * Empty the cart.
	 *
	 * @return void
	 */
	private function empty_cart(): void {
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}
	}

	/**
	 * Log a session update event using FraudProtectionController's logging helper.
	 *
	 * @param string $action The action taken (allowed, challenged, or blocked).
	 * @return void
	 */
	private function log_session_update_event( string $action ): void {
		$session_id = $this->get_session_id();
		$user_id    = get_current_user_id();
		$user_info  = $user_id ? "User: {$user_id}" : 'User: guest';
		$timestamp  = current_time( 'mysql' );

		$message = sprintf(
			'Session updated: %s | %s | Action: %s | Timestamp: %s',
			$session_id,
			$user_info,
			$action,
			$timestamp
		);

		FraudProtectionController::log( 'info', $message );
	}
}
