<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Persists UCP `Idempotency-Key` records.
 *
 * Concurrency lives at the storage layer: `try_begin()` is an atomic `INSERT IGNORE`
 * against the table's UNIQUE key column, so exactly one of several concurrent requests
 * carrying the same key owns processing. The rest read the row back and either replay the
 * stored response or report a conflict.
 */
class UcpIdempotencyStore {
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
	 * Claim a key for processing.
	 *
	 * @param string $key Idempotency key.
	 * @param string $fingerprint Request fingerprint.
	 * @return bool True when this caller created the row and so owns processing.
	 */
	public static function try_begin( string $key, string $fingerprint ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Claiming a key is a write that must reach the database.
		$wpdb->query(
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

		return 1 === (int) $wpdb->rows_affected;
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
				"SELECT request_fingerprint, state, response_status, response_body
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
	 * The status is kept beside the body because a replay has to repeat the original one,
	 * which is not always 200.
	 *
	 * @param string $key Idempotency key.
	 * @param int    $status HTTP status of the response.
	 * @param mixed  $data Response body, JSON encoded on the way in.
	 * @return void
	 */
	public static function finalize( string $key, int $status, $data ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Storing the replayable response.
		$wpdb->query(
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
	 * Used when the response is not replayable, so that a retry reprocesses instead of
	 * being answered from storage.
	 *
	 * @param string $key Idempotency key.
	 * @return void
	 */
	public static function delete( string $key ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Releasing the claim.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_ucp_idempotency_keys WHERE idempotency_key = %s",
				$key
			)
		);
	}

	/**
	 * Delete expired records.
	 *
	 * Each kind of row has its own clock: a `completed` record follows the retention window,
	 * an `in_progress` claim the shorter orphan window. The claim deliberately ignores the
	 * filter, so shortening retention cannot free a claim whose request is still running.
	 *
	 * @return void
	 */
	public static function cleanup(): void {
		global $wpdb;

		/**
		 * Filters how long a completed UCP idempotency record is kept, in seconds.
		 *
		 * How long a retry carrying the same key is answered from storage. One hour by default,
		 * since an agent retries within seconds and a longer window would keep a shopper's basket
		 * on disk after anything could replay it. Raise it for a client that retries more slowly.
		 *
		 * @since 11.3.0
		 *
		 * @param int $seconds Retention window. Default one day.
		 */
		$retention = (int) apply_filters( 'woocommerce_ucp_idempotency_retention_seconds', DAY_IN_SECONDS );

		if ( $retention <= 0 ) {
			$retention = DAY_IN_SECONDS;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Scheduled cleanup of an internal table.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_ucp_idempotency_keys WHERE created_at < %s",
				gmdate( 'Y-m-d H:i:s', time() - $retention )
			)
		);
	}
}
