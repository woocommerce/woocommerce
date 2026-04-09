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

	private const APPROVAL_PREFIX    = '_wc_pos_approval_';
	private const IDEMPOTENCY_PREFIX = '_wc_pos_idem_';
	private const TOKEN_LENGTH       = 32;
	private const TTL_SECONDS        = 300;

	/**
	 * Creates a single-use approval token for a manager override action.
	 *
	 * @since 10.8.0
	 * @param int         $approver_id     The user ID of the approving manager.
	 * @param string      $action          The action being approved (e.g. 'refund', 'discount').
	 * @param array       $context         Additional context data for the approval.
	 * @param string|null $idempotency_key Optional key to prevent duplicate approvals.
	 * @return string The raw (unhashed) token.
	 */
	public function create_approval( int $approver_id, string $action, array $context, ?string $idempotency_key = null ): string {
		if ( null !== $idempotency_key ) {
			$existing_token = get_transient( self::IDEMPOTENCY_PREFIX . $idempotency_key );
			if ( false !== $existing_token ) {
				return $existing_token;
			}
		}

		$token = wp_generate_password( self::TOKEN_LENGTH, false, false );
		$hash  = hash( 'sha256', $token );

		$data = array(
			'approver_id' => $approver_id,
			'action'      => $action,
			'context'     => $context,
			'created_at'  => time(),
		);

		set_transient( self::APPROVAL_PREFIX . $hash, $data, self::TTL_SECONDS );

		if ( null !== $idempotency_key ) {
			set_transient( self::IDEMPOTENCY_PREFIX . $idempotency_key, $token, self::TTL_SECONDS );
		}

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
		$hash = hash( 'sha256', $token );
		$data = get_transient( self::APPROVAL_PREFIX . $hash );

		if ( false === $data ) {
			return false;
		}

		delete_transient( self::APPROVAL_PREFIX . $hash );

		if ( $data['action'] !== $action ) {
			return false;
		}

		return $data;
	}
}
