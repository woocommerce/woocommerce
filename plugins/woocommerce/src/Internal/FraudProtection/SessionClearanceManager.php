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
	 */
	public const DEFAULT_STATUS = self::STATUS_ALLOWED;

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
	 * Mark the current session as blocked.
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
