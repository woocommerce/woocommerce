<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

/**
 * A self-healing mutex used to serialize order creation for a single shopper during checkout.
 *
 * Concurrent checkout submissions for the same shopper (double-clicks, load-balancer/proxy retries on timeout,
 * duplicate AJAX requests) can each pass validation before any of them has persisted an order, so each one creates
 * its own order. The lock is claimed for the duration of that window only (order creation plus the pending-order
 * session reference being made durable), never for the payment gateway call that follows, so a slow or off-site
 * gateway can never hold the lock.
 *
 * The lock lives in the options table (rather than a MySQL named lock such as GET_LOCK()) so it is naturally
 * scoped to this install's wp_options and routes to the primary database like any other write, avoiding the
 * replica-routing and server-global-namespace pitfalls of GET_LOCK. Modeled on WC_Install::create_lock() and
 * BatchProcessingController's own option-row mutex.
 *
 * @since 11.2.0
 */
class CheckoutOrderLock {

	/**
	 * Option name prefix for a held lock. The full option name is this prefix plus a hash of the lock key, so an
	 * arbitrary (and potentially long) key such as a guest session ID stays within the options table's column
	 * limit.
	 *
	 * @since 11.2.0
	 */
	const LOCK_OPTION_PREFIX = 'wc_checkout_order_lock_';

	/**
	 * Total time (in seconds, fractional) a contending request retries acquiring a held lock before giving up and
	 * letting the shopper know their order is already being processed.
	 *
	 * This bounds how long a losing request keeps the shopper waiting, so it is deliberately short. It is NOT how
	 * long a lock is allowed to be held for - see STALE_LOCK_THRESHOLD - a contender that gives up here has not
	 * necessarily decided the holder is dead, only that it is not worth waiting on synchronously any longer.
	 *
	 * @since 11.2.0
	 */
	const ACQUIRE_WAIT_BUDGET = 5.0;

	/**
	 * Number of polling attempts within ACQUIRE_WAIT_BUDGET.
	 *
	 * @since 11.2.0
	 */
	const ACQUIRE_RETRY_ATTEMPTS = 20;

	/**
	 * How long (in seconds) a held lock is allowed to go without being released before it is presumed abandoned
	 * (the holding request crashed or otherwise never reached release()) and can be taken over.
	 *
	 * This is deliberately much longer than ACQUIRE_WAIT_BUDGET. Order creation runs third-party hooks
	 * (`woocommerce_checkout_create_order`, `woocommerce_checkout_update_order_meta`) that can call out to external
	 * services (tax, inventory, CRM sync) and legitimately take longer than a contender is willing to wait
	 * synchronously. Using the same short value for both would let a contender steal the lock from a holder that
	 * is merely slow, not dead, reintroducing the exact duplicate-order race this class exists to close; a
	 * genuinely crashed holder's lock is still reclaimed, just not necessarily by the first request that notices
	 * it is held.
	 *
	 * @since 11.2.0
	 */
	const STALE_LOCK_THRESHOLD = 30.0;

	/**
	 * Attempt to acquire the lock for the given key.
	 *
	 * Retries with a short delay for up to ACQUIRE_WAIT_BUDGET seconds in total before giving up, so a request
	 * that loses a tight race gets a real chance to proceed once the winner releases. A held lock is only taken
	 * over if it is already older than STALE_LOCK_THRESHOLD; otherwise this simply gives up once
	 * ACQUIRE_WAIT_BUDGET elapses, leaving a possibly-still-active holder's lock alone.
	 *
	 * @since 11.2.0
	 *
	 * @param string $key Arbitrary identifier to scope the lock to (e.g. a customer/session ID). Two callers using
	 *                    the same key contend for the same lock; different keys never contend with each other.
	 * @return string|null The token to pass to release(), or null if the lock could not be acquired.
	 */
	public function acquire( string $key ): ?string {
		global $wpdb;

		$option_name = $this->get_option_name( $key );
		$attempts    = max( 1, self::ACQUIRE_RETRY_ATTEMPTS );
		$delay_micro = (int) ( ( self::ACQUIRE_WAIT_BUDGET / $attempts ) * 1_000_000 );

		// A contended INSERT fails on the duplicate key by design, so silence wpdb error reporting to keep that
		// expected-failure path from spamming the log in debug mode; restore the prior setting when done. Any
		// unexpected failure (not the duplicate-key case) is still surfaced via wc_get_logger() below.
		$suppress = $wpdb->suppress_errors( true );
		try {
			for ( $attempt = 0; $attempt < $attempts; $attempt++ ) {
				$now   = microtime( true );
				$token = number_format( $now, 6, '.', '' );

				// The unique option_name key makes this INSERT a mutex: it fails when the lock row already exists.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$acquired = $wpdb->insert(
					$wpdb->options,
					array(
						'option_name'  => $option_name,
						'option_value' => $token,
						'autoload'     => 'no',
					),
					array( '%s', '%s', '%s' )
				);

				if ( ! $acquired ) {
					$this->maybe_log_unexpected_db_error( $wpdb, 'acquire (insert)', $key );

					// The lock row exists; take it over only if it was acquired long enough ago to be presumed
					// abandoned, rather than merely still in use by an active (if slow) holder.
					$stale_before = number_format( $now - self::STALE_LOCK_THRESHOLD, 6, '.', '' );
					$takeover     = $wpdb->prepare(
						"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$token,
						$option_name,
						$stale_before
					);
					if ( is_string( $takeover ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
						$acquired = $wpdb->query( $takeover );
					}
				}

				if ( $acquired ) {
					return $token;
				}

				if ( $attempt < $attempts - 1 && $delay_micro > 0 ) {
					usleep( $delay_micro );
				}
			}

			return null;
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	/**
	 * Release a lock previously acquired with acquire().
	 *
	 * The delete is conditional on the stored value still matching the token returned by acquire(), so a lock that
	 * went stale and was taken over by another request is never released out from under it.
	 *
	 * @since 11.2.0
	 *
	 * @param string $key   The same key passed to acquire().
	 * @param string $token The token returned by acquire().
	 */
	public function release( string $key, string $token ): void {
		global $wpdb;

		$suppress = $wpdb->suppress_errors( true );
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->options,
				array(
					'option_name'  => $this->get_option_name( $key ),
					'option_value' => $token,
				),
				array( '%s', '%s' )
			);
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	/**
	 * Build the options-table row name for a lock key.
	 *
	 * @param string $key Arbitrary lock key.
	 * @return string
	 */
	private function get_option_name( string $key ): string {
		return self::LOCK_OPTION_PREFIX . md5( $key );
	}

	/**
	 * Log a failed insert if it does not look like the expected duplicate-key contention case.
	 *
	 * The suppress_errors() call above silences wpdb's own error reporting for the (expected, frequent) case of losing the race
	 * on the unique key, but that means a genuine database problem (connection loss, disk full, a missing table)
	 * would otherwise fail exactly the same way and be indistinguishable from ordinary lock contention - visible
	 * to the shopper only as "please wait and try again", with nothing in the logs to explain why every attempt
	 * is failing. This keeps the fail-safe behaviour (the lock is still denied either way) while surfacing the
	 * unexpected case for diagnosis.
	 *
	 * @param \wpdb  $wpdb Database access object.
	 * @param string $step Where the failure occurred, for the log message.
	 * @param string $key  The lock key involved, for the log message.
	 */
	private function maybe_log_unexpected_db_error( \wpdb $wpdb, string $step, string $key ): void {
		if ( '' === $wpdb->last_error || false !== stripos( $wpdb->last_error, 'Duplicate entry' ) ) {
			return;
		}

		wc_get_logger()->error(
			sprintf( 'CheckoutOrderLock: unexpected database error during %s: %s', $step, $wpdb->last_error ),
			array(
				'source'   => 'checkout-order-lock',
				'lock_key' => $key,
			)
		);
	}
}
