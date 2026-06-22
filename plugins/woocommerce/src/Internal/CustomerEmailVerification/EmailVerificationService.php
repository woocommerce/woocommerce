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

		// Ensure the failure-counter row exists (at zero) so the compare-and-swap in verify_code() only
		// ever updates, never inserts — concurrent first guesses would otherwise race into duplicate
		// rows. Initialise only when absent, so resending a code never resets the count or the lockout.
		if ( '' === (string) Users::get_site_user_meta( $user_id, self::FAILURES_META ) ) {
			Users::update_site_user_meta( $user_id, self::FAILURES_META, '0' );
		}

		Users::update_site_user_meta( $user_id, self::KEY_META, time() . ':' . wp_fast_hash( $code ) . ':' . $email_hash . ':0' );

		return $code;
	}

	/**
	 * Verify a submitted code for the given user and record the outcome.
	 *
	 * Before comparing the code, this claims a guess against the cumulative budget with a
	 * compare-and-swap on the failure counter ({@see self::claim_attempt()}). That serialises
	 * concurrent submissions into distinct slots, so a parallel flood can't each read the same counter
	 * and slip past the cap — at most MAX_FAILURES codes are ever compared, and a loser under
	 * contention is turned away ({@see self::RESULT_WRONG}) without a guess. Expired, missing, or
	 * email-mismatched codes return before the counter moves, so they never count against the customer.
	 *
	 * A correct code marks the user verified; reaching {@see self::MAX_ATTEMPTS} on one code burns it
	 * (a new one must be requested) and reaching {@see self::MAX_FAILURES} locks the user out permanently.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $code    The plaintext code submitted by the customer.
	 * @return string One of the RESULT_* constants.
	 */
	public function verify_code( int $user_id, string $code ): string {
		$failures = $this->get_failure_count( $user_id );

		if ( $failures >= self::MAX_FAILURES ) {
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

		// Claim this guess against the cumulative budget before comparing. If another request moved the
		// counter first, we lose the swap and turn away without a guess (and without double-counting).
		if ( ! $this->claim_attempt( $user_id, $failures ) ) {
			return self::RESULT_WRONG;
		}

		if ( '' !== $code && wp_verify_fast_hash( $code, $hash ) ) {
			$this->mark_verified( $user_id );
			return self::RESULT_OK;
		}

		// Wrong guess. The cumulative counter has already moved to $failures + 1.
		if ( $failures + 1 >= self::MAX_FAILURES ) {
			// That was the final allowed guess: lock out and drop the live code.
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
	 * Atomically claim a guess against the cumulative failure budget via a compare-and-swap.
	 *
	 * Moves the counter from $failures to $failures + 1 only while it still equals $failures, so
	 * concurrent submissions are serialised into distinct slots and at most MAX_FAILURES ever pass.
	 * The counter row is pre-created at zero by {@see self::create_code()} so this only ever updates,
	 * never inserts (which would race into duplicate rows). Returns false when another request moved
	 * the counter first.
	 *
	 * @param int $user_id  WordPress user ID.
	 * @param int $failures The failure count this request observed.
	 * @return bool True when this request claimed the slot.
	 */
	private function claim_attempt( int $user_id, int $failures ): bool {
		return (bool) Users::update_site_user_meta( $user_id, self::FAILURES_META, (string) ( $failures + 1 ), (string) $failures );
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
