<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Manages single-use approval tokens for POS manager override workflows.
 *
 * Tokens are stored as non-autoloaded rows in wp_options with a manual
 * expiry. This bypasses the WordPress transient API, which on managed
 * hosts (with wp_using_ext_object_cache() = true) can route reads and
 * writes to an external object cache that is not guaranteed to be
 * consistent across PHP workers or survive evictions. The raw token is
 * returned to the caller; only the SHA-256 hash is persisted.
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
		global $wpdb;

		$token = wp_generate_password( self::TOKEN_LENGTH, false, false );
		$hash  = hash( 'sha256', $token );
		$key   = self::APPROVAL_PREFIX . $hash;

		$data = array(
			'approver_id' => $approver_id,
			'action'      => $action,
			'context'     => $context,
			'created_at'  => time(),
			'expires_at'  => time() + self::TTL_SECONDS,
		);

		// Direct insert into wp_options, autoload=no. Bypasses the transient
		// API so the value is guaranteed durable and visible to every PHP
		// worker regardless of object-cache state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => $key,
				'option_value' => maybe_serialize( $data ),
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		// Invalidate any cached notoptions entry so future get_option lookups
		// on this key hit the DB rather than the "does not exist" memo.
		wp_cache_delete( 'notoptions', 'options' );

		return $token;
	}

	/**
	 * Validates and consumes a single-use approval token.
	 *
	 * The token row is deleted immediately after a successful read to
	 * enforce single-use semantics. Uses a direct SQL SELECT + atomic
	 * DELETE so the result is consistent across workers irrespective of
	 * object-cache availability.
	 *
	 * @since 10.8.0
	 * @param string $token  The raw token to validate.
	 * @param string $action The expected action.
	 * @return array|false The approval data on success, or false on failure.
	 */
	public function validate_and_consume( string $token, string $action ) {
		global $wpdb;

		$this->last_failure_reason = '';

		$hash = hash( 'sha256', $token );
		$key  = self::APPROVAL_PREFIX . $hash;

		// Direct read - bypass wp_cache entirely so a stale/evicted cache
		// does not produce false-negative lookups on the refund path.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$key
			)
		);

		if ( null === $raw ) {
			$this->last_failure_reason = self::FAILURE_INVALID_OR_EXPIRED;
			return false;
		}

		$data = maybe_unserialize( $raw );
		if ( ! is_array( $data ) || empty( $data['expires_at'] ) || (int) $data['expires_at'] < time() ) {
			// Expired or malformed; clean up and reject.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $wpdb->options, array( 'option_name' => $key ) );
			wp_cache_delete( $key, 'options' );
			$this->last_failure_reason = self::FAILURE_INVALID_OR_EXPIRED;
			return false;
		}

		// Atomic consume. Concurrent callers race to delete the row; only
		// the one that reports rows_deleted = 1 is considered the consumer.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows_deleted = $wpdb->delete(
			$wpdb->options,
			array( 'option_name' => $key )
		);

		wp_cache_delete( $key, 'options' );

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

	/**
	 * Delete approval rows whose expires_at is in the past.
	 *
	 * Tokens created but never consumed would otherwise accumulate since
	 * approval storage no longer relies on transient auto-expiry. Called
	 * from the recurring POSController cleanup action.
	 *
	 * @since 10.8.0
	 * @return int Number of expired rows removed.
	 */
	public function cleanup_expired_approvals(): int {
		global $wpdb;

		$now        = time();
		$prefix_key = self::APPROVAL_PREFIX . '%';

		// Pull candidate rows by prefix, decode option_value, and delete the
		// ones past expiry. Pattern prefix lookups on option_name benefit from
		// the unique index.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix_key
			)
		);

		if ( empty( $rows ) ) {
			return 0;
		}

		$deleted = 0;
		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->option_value );
			if ( ! is_array( $data ) ) {
				// Malformed entry - remove it.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->options, array( 'option_name' => $row->option_name ) );
				wp_cache_delete( $row->option_name, 'options' );
				++$deleted;
				continue;
			}

			$expires_at = isset( $data['expires_at'] ) ? (int) $data['expires_at'] : 0;
			if ( $expires_at > 0 && $expires_at >= $now ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $wpdb->options, array( 'option_name' => $row->option_name ) );
			wp_cache_delete( $row->option_name, 'options' );
			++$deleted;
		}

		return $deleted;
	}
}
