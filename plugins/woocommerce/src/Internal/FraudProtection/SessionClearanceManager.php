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
 * @since 10.4.0
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
	 * Check if the current session is cleared (allowed).
	 *
	 * @return bool True if session is cleared/allowed, false otherwise.
	 */
	public function is_session_cleared(): bool {
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
		$this->log_clearance_event( 'allowed' );
	}

	/**
	 * Mark the current session as pending (challenge required).
	 *
	 * @return void
	 */
	public function challenge_session(): void {
		$this->set_session_status( self::STATUS_PENDING );
		$this->log_clearance_event( 'challenged' );
	}

	/**
	 * Mark the current session as blocked.
	 *
	 * @return void
	 */
	public function block_session(): void {
		$this->set_session_status( self::STATUS_BLOCKED );
		$this->log_clearance_event( 'blocked' );
		$this->empty_cart();
	}

	/**
	 * Get the current session clearance status.
	 *
	 * @return string One of: pending, allowed, blocked.
	 */
	public function get_session_status(): string {
		if ( ! $this->is_session_available() ) {
			return self::STATUS_ALLOWED;
		}

		$status = WC()->session->get( self::SESSION_KEY, self::STATUS_ALLOWED );

		// Validate status value.
		if ( ! in_array( $status, array( self::STATUS_PENDING, self::STATUS_ALLOWED, self::STATUS_BLOCKED ), true ) ) {
			return self::STATUS_PENDING;
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
		if ( WC()->session instanceof \WC_Session_Handler ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	/**
	 * Reset the session clearance status to pending.
	 *
	 * @return void
	 */
	public function reset_session(): void {
		$this->set_session_status( self::STATUS_PENDING );
	}

	/**
	 * Ensure cart and session are available.
	 *
	 * Loads cart if not already loaded, which initializes session for both
	 * traditional (cookie) and Store API (token) flows.
	 *
	 * @return void
	 */
	private function ensure_cart_loaded(): void {
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
	}

	/**
	 * Check if WooCommerce session is available.
	 *
	 * @return bool True if session is available.
	 */
	private function is_session_available(): bool {
		$this->ensure_cart_loaded();
		return isset( WC()->session ) && WC()->session instanceof \WC_Session;
	}

	/**
	 * Get a unique identifier for the current session.
	 *
	 * @return string Session identifier.
	 */
	private function get_session_id(): string {
		if ( ! $this->is_session_available() ) {
			return 'no-session';
		}

		// Use WooCommerce session customer ID.
		$customer_id = WC()->session->get_customer_id();
		return $customer_id ? $customer_id : 'guest-' . wp_get_session_token();
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
	 * Log a clearance event.
	 *
	 * @param string $action The action taken (allowed or blocked).
	 * @return void
	 */
	private function log_clearance_event( string $action ): void {
		$logger = wc_get_logger();

		$session_id = $this->get_session_id();
		$user_id    = get_current_user_id();
		$user_info  = $user_id ? "User: {$user_id}" : 'User: guest';
		$ip_address = $this->get_client_ip();
		$timestamp  = current_time( 'mysql' );

		$message = sprintf(
			'Session cleared: %s | %s | IP: %s | Action: %s | Timestamp: %s',
			$session_id,
			$user_info,
			$ip_address,
			$action,
			$timestamp
		);

		$logger->info( $message, array( 'source' => 'woo-fraud-protection' ) );
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip(): string {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip = explode( ',', $ip );
			$ip = trim( $ip[0] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} else {
			$ip = 'unknown';
		}

		return $ip;
	}

	/**
	 * Reset all session clearances (admin function).
	 * This clears the clearance data from the session table.
	 *
	 * @return int Number of sessions reset.
	 */
	public static function reset_all_sessions(): int {
		global $wpdb;

		// For WooCommerce sessions stored in database.
		$session_table = $wpdb->prefix . 'woocommerce_sessions';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $session_table ) );

		if ( ! $table_exists ) {
			return 0;
		}

		// Update all sessions to remove the clearance key.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$session_table} SET session_value = REPLACE(session_value, %s, %s) WHERE session_value LIKE %s",
				's:36:"_fraud_protection_clearance_status";s:7:"allowed"',
				'',
				'%_fraud_protection_clearance_status%'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result += $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$session_table} SET session_value = REPLACE(session_value, %s, %s) WHERE session_value LIKE %s",
				's:36:"_fraud_protection_clearance_status";s:7:"blocked"',
				'',
				'%_fraud_protection_clearance_status%'
			)
		);

		$logger = wc_get_logger();
		$logger->info(
			sprintf( 'All session clearances reset by admin. Affected sessions: %d', $result ),
			array( 'source' => 'woo-fraud-protection' )
		);

		return $result;
	}
}
