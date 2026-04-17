<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Manages single-use approval tokens for POS manager override workflows.
 *
 * Tokens are stored as WordPress transients with a 5-minute TTL.
 * The raw token is returned to the caller; only the SHA-256 hash is persisted.
 *
 * @since 10.8.0
 */
class POSApprovalService {

	public const FAILURE_INVALID_OR_EXPIRED = 'invalid_or_expired';
	public const FAILURE_ACTION_MISMATCH    = 'action_mismatch';

	private const APPROVAL_PREFIX = '_wc_pos_approval_';
	private const TOKEN_LENGTH    = 32;
	private const TTL_SECONDS     = 300;

	/**
	 * Reason for the most recent validation failure, if any.
	 *
	 * @var string
	 */
	private string $last_failure_reason = '';

	/**
	 * Creates a single-use approval token for a manager override action.
	 *
	 * @since 10.8.0
	 * @param int    $approver_id The user ID of the approving manager.
	 * @param string $action      The action being approved (e.g. 'refund', 'discount').
	 * @param array  $context     Additional context data for the approval.
	 * @return string The raw (unhashed) token.
	 */
	public function create_approval( int $approver_id, string $action, array $context ): string {
		$token = wp_generate_password( self::TOKEN_LENGTH, false, false );
		$hash  = hash( 'sha256', $token );

		$data = array(
			'approver_id' => $approver_id,
			'action'      => $action,
			'context'     => $context,
			'created_at'  => time(),
		);

		set_transient( self::APPROVAL_PREFIX . $hash, $data, self::TTL_SECONDS );

		return $token;
	}

	/**
	 * Validates and consumes a single-use approval token.
	 *
	 * The token is deleted immediately after lookup to enforce single-use semantics.
	 *
	 * @since 10.8.0
	 * @param string $token  The raw token to validate.
	 * @param string $action The expected action.
	 * @return array|false The approval data on success, or false on failure.
	 */
	public function validate_and_consume( string $token, string $action ) {
		global $wpdb;

		$this->last_failure_reason = '';

		$hash       = hash( 'sha256', $token );
		$option_key = '_transient_' . self::APPROVAL_PREFIX . $hash;

		// Atomic consume via SQL DELETE with row count check.
		// This prevents TOCTOU races: only the first concurrent request
		// that deletes the row gets affected_rows = 1 and proceeds.
		$data = get_transient( self::APPROVAL_PREFIX . $hash );
		if ( false === $data ) {
			$this->last_failure_reason = self::FAILURE_INVALID_OR_EXPIRED;
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_deleted = $wpdb->delete(
			$wpdb->options,
			array( 'option_name' => $option_key )
		);

		// Also clean the timeout transient.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->options,
			array( 'option_name' => '_transient_timeout_' . self::APPROVAL_PREFIX . $hash )
		);

		wp_cache_delete( self::APPROVAL_PREFIX . $hash, 'transient' );

		if ( 0 === $rows_deleted ) {
			$this->last_failure_reason = self::FAILURE_INVALID_OR_EXPIRED;
			return false;
		}

		if ( $data['action'] !== $action ) {
			$this->last_failure_reason = self::FAILURE_ACTION_MISMATCH;
			return false;
		}

		return $data;
	}

	/**
	 * Return the reason for the most recent validation failure.
	 *
	 * Empty string when the most recent call succeeded or no call has been made.
	 *
	 * @since 10.8.0
	 * @return string
	 */
	public function get_last_failure_reason(): string {
		return $this->last_failure_reason;
	}
}
