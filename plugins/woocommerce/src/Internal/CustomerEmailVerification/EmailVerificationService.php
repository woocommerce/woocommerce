<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * Service class providing the foundational primitives for customer email verification.
 *
 * This class is the single source of truth for whether a customer has proven they
 * control their account email address. It manages the verified status meta and the
 * short-lived, single-use numeric code (OTP) emailed to the customer, together with
 * the per-code attempt limit and the cumulative-failure lockout that protect it.
 *
 * @since 11.0.0
 */
class EmailVerificationService {

	/**
	 * Result of {@see self::verify_code()}: the code matched and the user is now verified.
	 */
	public const RESULT_OK = 'ok';

	/**
	 * Result of {@see self::verify_code()}: the code was wrong but attempts remain on it.
	 */
	public const RESULT_WRONG = 'wrong';

	/**
	 * Result of {@see self::verify_code()}: the code was wrong and has now used up its attempts.
	 */
	public const RESULT_BURNED = 'burned';

	/**
	 * Result of {@see self::verify_code()}: the code has expired (not counted as a failed guess).
	 */
	public const RESULT_EXPIRED = 'expired';

	/**
	 * Result of {@see self::verify_code()}: there is no pending code to check.
	 */
	public const RESULT_NONE = 'none';

	/**
	 * Result of {@see self::verify_code()}: too many cumulative failures; the user is locked out.
	 */
	public const RESULT_LOCKED = 'locked';

	/**
	 * How long a freshly minted code remains valid.
	 */
	private const OTP_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Wrong guesses allowed against a single code before it is burned and a new one must be requested.
	 */
	private const MAX_ATTEMPTS = 3;

	/**
	 * Cumulative wrong guesses (across all codes) before the user is permanently locked out of the
	 * code flow and must contact the store owner (who can verify them from the admin).
	 */
	private const MAX_FAILURES = 10;

	/**
	 * Seconds to wait for the per-user verification lock before failing closed. The locked section is
	 * a few meta operations (milliseconds), so this never trips for a legitimate single submission;
	 * it only bounds the wait when many guesses are submitted in parallel.
	 */
	private const LOCK_TIMEOUT_SECONDS = 5;

	/**
	 * User meta key that stores the verified email address (lower-cased).
	 * The customer is considered verified only while this matches their current account email.
	 */
	private const VERIFIED_META = '_wc_email_verified';

	/**
	 * User meta key that stores the pending code as "{timestamp}:{code_hash}:{email_hash}:{attempts}".
	 * Overwritten on every new code; deleted when the code is consumed, burned, or the user verifies.
	 */
	private const KEY_META = '_wc_email_verification_key';

	/**
	 * User meta key that stores the cumulative wrong-guess count as a plain integer.
	 * Spans codes (so requesting a new code does not reset it) and drives the permanent lockout.
	 */
	private const FAILURES_META = '_wc_email_verification_failures';

	/**
	 * Return whether the given user has verified their current account email address.
	 *
	 * A user is verified only while the stored verified email matches their current
	 * account email, so changing the account email automatically invalidates the
	 * status — no change event needs to be observed.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool True when the stored verified email matches the user's current email.
	 */
	public function is_verified( int $user_id ): bool {
		$verified_email = (string) Users::get_site_user_meta( $user_id, self::VERIFIED_META );

		if ( '' === $verified_email ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );

		return $user instanceof \WP_User && 0 === strcasecmp( $verified_email, $user->user_email );
	}

	/**
	 * Mark the given user as having verified their current account email address.
	 *
	 * Stores the verified email address, clears any pending code and failure count, and
	 * fires the {@see 'woocommerce_customer_email_verified'} action. No-ops if the
	 * user is already verified for their current email.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	public function mark_verified( int $user_id ): void {
		if ( $this->is_verified( $user_id ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		// Store the verified email (lower-cased) so the status self-invalidates if the account email later changes.
		Users::update_site_user_meta( $user_id, self::VERIFIED_META, strtolower( $user->user_email ) );
		Users::delete_site_user_meta( $user_id, self::KEY_META );
		Users::delete_site_user_meta( $user_id, self::FAILURES_META );

		/**
		 * Fires after a customer has verified their email address.
		 *
		 * @param int $user_id The WordPress user ID of the verified customer.
		 *
		 * @since 11.0.0
		 */
		do_action( 'woocommerce_customer_email_verified', $user_id );
	}

	/**
	 * Clear the email-verification status for the given user.
	 *
	 * Removes the verified-email meta, any pending code, and the cumulative failure count,
	 * effectively resetting the user to a clean unverified state (also lifting any lockout).
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	public function clear_verification( int $user_id ): void {
		Users::delete_site_user_meta( $user_id, self::VERIFIED_META );
		Users::delete_site_user_meta( $user_id, self::KEY_META );
		Users::delete_site_user_meta( $user_id, self::FAILURES_META );
	}

	/**
	 * Generate and store a one-time numeric verification code for the given user.
	 *
	 * The plaintext 6-digit code is returned for inclusion in the verification email. The stored
	 * value is a "{timestamp}:{code_hash}:{email_hash}:{attempts}" tuple so the plaintext is never
	 * persisted, the code expires after {@see self::OTP_TTL}, and the email hash binds the code to
	 * the account email in effect at issuance (a code emailed to one address can never verify a
	 * different address the account is later switched to). The attempt counter starts at zero.
	 *
	 * Minting a new code does not reset the cumulative failure count, so the lockout cannot be
	 * sidestepped by simply requesting fresh codes.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The plaintext 6-digit code.
	 */
	public function create_code( int $user_id ): string {
		$code       = $this->generate_code();
		$user       = get_user_by( 'id', $user_id );
		$email_hash = $user instanceof \WP_User ? wp_fast_hash( strtolower( $user->user_email ) ) : '';

		Users::update_site_user_meta( $user_id, self::KEY_META, time() . ':' . wp_fast_hash( $code ) . ':' . $email_hash . ':0' );

		return $code;
	}

	/**
	 * Verify a submitted code for the given user and record the outcome.
	 *
	 * The read-modify-write of the attempt and failure counters runs inside a per-user database lock
	 * so concurrent submissions can't each read the same counters before any write lands, which would
	 * otherwise let a parallel flood of guesses slip past the per-code and cumulative limits. If the
	 * lock can't be acquired the attempt fails closed without touching any state.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $code    The plaintext code submitted by the customer.
	 * @return string One of the RESULT_* constants.
	 */
	public function verify_code( int $user_id, string $code ): string {
		if ( ! $this->acquire_lock( $user_id ) ) {
			// Fail closed: never run the unserialised read-modify-write if we couldn't get the lock.
			return self::RESULT_WRONG;
		}

		try {
			return $this->do_verify_code( $user_id, $code );
		} finally {
			$this->release_lock( $user_id );
		}
	}

	/**
	 * Verify a submitted code and record the outcome. Must run under the per-user lock held by
	 * {@see self::verify_code()}.
	 *
	 * A correct code marks the user verified. A wrong guess against a live code increments both the
	 * per-code attempt counter and the cumulative failure counter; reaching {@see self::MAX_ATTEMPTS}
	 * burns the code (a new one must be requested) and reaching {@see self::MAX_FAILURES} locks the
	 * user out permanently. Expired or missing codes are not counted as guesses.
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $code    The plaintext code submitted by the customer.
	 * @return string One of the RESULT_* constants.
	 */
	private function do_verify_code( int $user_id, string $code ): string {
		if ( $this->is_locked_out( $user_id ) ) {
			return self::RESULT_LOCKED;
		}

		$parsed = $this->parse_stored_key( $user_id );

		if ( null === $parsed ) {
			return self::RESULT_NONE;
		}

		list( $timestamp, $hash, $email_hash, $attempts ) = $parsed;

		if ( time() - $timestamp > self::OTP_TTL ) {
			// Expired: a timeout, not a guess. Clear the dead code without penalising the customer.
			Users::delete_site_user_meta( $user_id, self::KEY_META );
			return self::RESULT_EXPIRED;
		}

		// The code is void if the account email no longer matches the one it was minted for.
		if ( '' !== $email_hash ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! $user instanceof \WP_User || ! wp_verify_fast_hash( strtolower( $user->user_email ), $email_hash ) ) {
				Users::delete_site_user_meta( $user_id, self::KEY_META );
				return self::RESULT_NONE;
			}
		}

		if ( '' !== $code && wp_verify_fast_hash( $code, $hash ) ) {
			$this->mark_verified( $user_id );
			return self::RESULT_OK;
		}

		return $this->register_failed_attempt( $user_id, $timestamp, $hash, $email_hash, $attempts );
	}

	/**
	 * Record a wrong guess against a live code and return the resulting status.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param int    $timestamp  Timestamp the live code was minted.
	 * @param string $hash       Stored hash of the live code.
	 * @param string $email_hash Stored email hash bound to the live code.
	 * @param int    $attempts   Per-code attempts used so far (before this one).
	 * @return string RESULT_LOCKED, RESULT_BURNED, or RESULT_WRONG.
	 */
	private function register_failed_attempt( int $user_id, int $timestamp, string $hash, string $email_hash, int $attempts ): string {
		$failures = $this->get_failure_count( $user_id ) + 1;
		Users::update_site_user_meta( $user_id, self::FAILURES_META, (string) $failures );

		if ( $failures >= self::MAX_FAILURES ) {
			// Permanent lockout: drop the live code too so nothing remains to guess against.
			Users::delete_site_user_meta( $user_id, self::KEY_META );
			return self::RESULT_LOCKED;
		}

		++$attempts;

		if ( $attempts >= self::MAX_ATTEMPTS ) {
			// Burn this code; the customer must request a fresh one.
			Users::delete_site_user_meta( $user_id, self::KEY_META );
			return self::RESULT_BURNED;
		}

		Users::update_site_user_meta(
			$user_id,
			self::KEY_META,
			$timestamp . ':' . $hash . ':' . $email_hash . ':' . $attempts
		);

		return self::RESULT_WRONG;
	}

	/**
	 * Acquire the per-user verification lock.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool True when the lock was acquired.
	 */
	protected function acquire_lock( int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- GET_LOCK is a MySQL session lock, not a cacheable read.
		return 1 === (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $this->lock_name( $user_id ), self::LOCK_TIMEOUT_SECONDS )
		);
	}

	/**
	 * Release the per-user verification lock.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	protected function release_lock( int $user_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Releasing a MySQL session lock.
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->lock_name( $user_id ) ) );
	}

	/**
	 * Build the MySQL lock name for a user, namespaced with the table prefix and capped at MySQL's
	 * 64-character lock-name limit.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string
	 */
	private function lock_name( int $user_id ): string {
		global $wpdb;

		return substr( $wpdb->prefix . 'wc_verify_email_' . $user_id, 0, 64 );
	}

	/**
	 * Whether the user currently has a pending (minted, unexpired) code awaiting entry.
	 *
	 * Used to decide whether the My Account prompt shows the code-entry form or the "send code"
	 * call to action.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function has_pending_code( int $user_id ): bool {
		$parsed = $this->parse_stored_key( $user_id );

		return null !== $parsed && time() - $parsed[0] <= self::OTP_TTL;
	}

	/**
	 * Whether the user has exhausted the cumulative failure budget and is permanently locked out.
	 *
	 * The lockout only lifts when the user is verified another way (e.g. password reset) or the
	 * store owner verifies them from the admin — both of which clear the failure count.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function is_locked_out( int $user_id ): bool {
		return $this->get_failure_count( $user_id ) >= self::MAX_FAILURES;
	}

	/**
	 * Return the cumulative wrong-guess count for the user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	private function get_failure_count( int $user_id ): int {
		return (int) Users::get_site_user_meta( $user_id, self::FAILURES_META );
	}

	/**
	 * Generate a zero-padded 6-digit numeric code.
	 *
	 * @return string
	 */
	private function generate_code(): string {
		return str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Parse the stored code tuple into its timestamp, hash, email-hash, and attempt parts.
	 *
	 * The tuple is persisted as "{timestamp}:{code_hash}:{email_hash}:{attempts}"; this is the
	 * single place that knows that format.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array{0: int, 1: string, 2: string, 3: int}|null The tuple, or null when none is stored.
	 */
	private function parse_stored_key( int $user_id ): ?array {
		$stored = (string) Users::get_site_user_meta( $user_id, self::KEY_META );

		if ( ! str_contains( $stored, ':' ) ) {
			return null;
		}

		$parts      = explode( ':', $stored, 4 );
		$timestamp  = (int) ( $parts[0] ?? 0 );
		$hash       = (string) ( $parts[1] ?? '' );
		$email_hash = (string) ( $parts[2] ?? '' );
		$attempts   = (int) ( $parts[3] ?? 0 );

		if ( '' === $hash ) {
			return null;
		}

		return array( $timestamp, $hash, $email_hash, $attempts );
	}

	/**
	 * Return the number of seconds elapsed since the last code was issued, or null if none exists.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int|null Seconds since the last code was created, or null when none is stored.
	 */
	public function seconds_since_last_key( int $user_id ): ?int {
		$parsed = $this->parse_stored_key( $user_id );

		if ( null === $parsed ) {
			return null;
		}

		// Clamp to zero so a future timestamp (clock skew, migrations) can't report negative
		// elapsed time and wedge the resend rate-limit / "recently sent" notice logic.
		return max( 0, time() - $parsed[0] );
	}
}
