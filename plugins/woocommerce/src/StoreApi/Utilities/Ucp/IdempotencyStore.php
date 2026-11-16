<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

/**
 * Persists UCP `Idempotency-Key` records.
 */
class IdempotencyStore {
	/**
	 * A request owning the key is still running.
	 */
	const STATE_IN_PROGRESS = 'in_progress';

	/**
	 * The response is stored and may be replayed.
	 */
	const STATE_COMPLETED = 'completed';

	/**
	 * Action Scheduler hook purging expired records.
	 */
	const CLEANUP_HOOK = 'woocommerce_cleanup_ucp_idempotency_keys';

	/**
	 * How long a claim may sit unanswered before another request may take it over.
	 *
	 * Bounds a claim orphaned by a process that died mid-request, which would otherwise answer
	 * `409` until the next sweep. Far above any PHP execution limit, so live work is never taken.
	 */
	const STALE_CLAIM_SECONDS = 300;

	/**
	 * Rows the cleanup sweep deletes per statement.
	 */
	const CLEANUP_BATCH_SIZE = 100;

	/**
	 * Claim a key for processing.
	 *
	 * Three outcomes: an `INSERT IGNORE` that inserts nothing cannot say by itself whether the
	 * key was already held or the write never landed, and a caller has to tell those apart.
	 *
	 * @param string $key Idempotency key.
	 * @param string $fingerprint Request fingerprint.
	 * @return bool|null True when this caller owns processing, false when the key is already
	 *         held, null when the record could not be written at all.
	 */
	public static function try_begin( string $key, string $fingerprint ) {
		global $wpdb;

		$written = self::write(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}wc_ucp_idempotency_keys
					(idempotency_key, request_fingerprint, state, created_at)
					VALUES (%s, %s, %s, %s)",
				$key,
				$fingerprint,
				self::STATE_IN_PROGRESS,
				gmdate( 'Y-m-d H:i:s' )
			)
		);

		if ( ! $written ) {
			return null;
		}

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Run a write against the record table, reporting a failure instead of swallowing it.
	 *
	 * @param string $sql Prepared statement to run.
	 * @return bool False when the query itself failed.
	 */
	private static function write( string $sql ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared by the caller; these writes must reach the database.
		if ( false !== $wpdb->query( $sql ) ) {
			return true;
		}

		wc_get_logger()->error(
			'UCP idempotency record could not be written: ' . $wpdb->last_error,
			array( 'source' => 'ucp-idempotency' )
		);

		return false;
	}

	/**
	 * Take over a claim whose owner has gone away.
	 *
	 * @param string $key Idempotency key.
	 * @param int    $stale_after_seconds Age past which an unanswered claim may be taken over.
	 * @return bool True when this caller now owns the claim.
	 */
	public static function try_take_over_stale( string $key, int $stale_after_seconds ): bool {
		global $wpdb;

		$taken = self::write(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys
					SET created_at = %s
					WHERE idempotency_key = %s AND state = %s AND created_at < %s",
				gmdate( 'Y-m-d H:i:s' ),
				$key,
				self::STATE_IN_PROGRESS,
				gmdate( 'Y-m-d H:i:s', time() - max( 1, $stale_after_seconds ) )
			)
		);

		return $taken && 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Reclaim a completed record past retention for a new request.
	 *
	 * @param string $key Idempotency key.
	 * @param string $fingerprint Fingerprint of the request now claiming the key.
	 * @return bool True when this caller now owns the claim.
	 */
	public static function try_reclaim_expired( string $key, string $fingerprint ): bool {
		global $wpdb;

		$taken = self::write(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys
					SET request_fingerprint = %s, state = %s, created_at = %s,
						response_status = NULL, response_body = NULL, completed_at = NULL
					WHERE idempotency_key = %s AND state = %s AND completed_at < %s",
				$fingerprint,
				self::STATE_IN_PROGRESS,
				gmdate( 'Y-m-d H:i:s' ),
				$key,
				self::STATE_COMPLETED,
				gmdate( 'Y-m-d H:i:s', time() - self::retention_seconds() )
			)
		);

		return $taken && 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Read a record back.
	 *
	 * @param string $key Idempotency key.
	 * @return array<string, mixed>|null The row, or null when the key is unknown.
	 */
	public static function find( string $key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading a claim another request may have just written; a cache would answer stale.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT request_fingerprint, state, response_status, response_body, completed_at
					FROM {$wpdb->prefix}wc_ucp_idempotency_keys
					WHERE idempotency_key = %s",
				$key
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Store the response a completed request produced.
	 *
	 * @param string $key Idempotency key.
	 * @param int    $status HTTP status of the response.
	 * @param mixed  $data Response body, JSON encoded on the way in.
	 * @return void
	 */
	public static function finalize( string $key, int $status, $data ): void {
		global $wpdb;

		self::write(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_ucp_idempotency_keys
					SET state = %s, response_status = %d, response_body = %s, completed_at = %s
					WHERE idempotency_key = %s",
				self::STATE_COMPLETED,
				$status,
				(string) wp_json_encode( $data ),
				gmdate( 'Y-m-d H:i:s' ),
				$key
			)
		);
	}

	/**
	 * Drop a record, releasing the key.
	 *
	 * @param string $key Idempotency key.
	 * @return void
	 */
	public static function delete( string $key ): void {
		global $wpdb;

		self::write(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_ucp_idempotency_keys WHERE idempotency_key = %s",
				$key
			)
		);
	}

	/**
	 * How long a completed record answers a retry, in seconds.
	 *
	 * @return int
	 */
	public static function retention_seconds(): int {
		/**
		 * Filters how long a completed UCP idempotency record is kept, in seconds.
		 * The UCP spec requires at least 24 hours.
		 *
		 * @since 11.4.0
		 *
		 * @param int $seconds Retention window. Default one day.
		 */
		$retention = (int) apply_filters( 'woocommerce_ucp_idempotency_retention_seconds', DAY_IN_SECONDS );

		return $retention > 0 ? $retention : DAY_IN_SECONDS;
	}

	/**
	 * Whether a completed record is past retention and no longer answers for its key.
	 *
	 * @param array<string, mixed> $record Row as `find()` returns it.
	 * @return bool
	 */
	public static function has_expired( array $record ): bool {
		if ( self::STATE_COMPLETED !== (string) ( $record['state'] ?? '' ) || empty( $record['completed_at'] ) ) {
			return false;
		}

		return strtotime( (string) $record['completed_at'] . ' UTC' ) < time() - self::retention_seconds();
	}

	/**
	 * Delete expired records.
	 *
	 * @return void
	 */
	public static function cleanup(): void {
		global $wpdb;

		$completed_cutoff = gmdate( 'Y-m-d H:i:s', time() - self::retention_seconds() );
		$claim_cutoff     = gmdate( 'Y-m-d H:i:s', time() - self::STALE_CLAIM_SECONDS );

		// Deleted in batches, after WC_Session_Handler::cleanup_sessions(): a row here holds a
		// whole stored response, so a single unbounded DELETE over a busy day's records would
		// hold locks and generate replication lag far longer than a sweep needs to.
		do {
			$written = self::write(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}wc_ucp_idempotency_keys
						WHERE ( state = %s AND completed_at < %s )
							OR ( state = %s AND created_at < %s )
						ORDER BY created_at
						LIMIT %d",
					self::STATE_COMPLETED,
					$completed_cutoff,
					self::STATE_IN_PROGRESS,
					$claim_cutoff,
					self::CLEANUP_BATCH_SIZE
				)
			);

			if ( ! $written ) {
				return;
			}

			$deleted = (int) $wpdb->rows_affected;
		} while ( self::CLEANUP_BATCH_SIZE === $deleted );
	}
}
