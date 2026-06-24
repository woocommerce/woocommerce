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
 * the per-code attempt limit and the attempt-budget lockout that protect it.
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
	 * Result of {@see self::verify_code()}: the attempt budget is exhausted; the user is locked out.
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
	 * Total guesses allowed (across all codes) before the user is permanently locked out of the code
	 * flow and must contact the store owner (who can verify them from the admin). Stored as a
	 * countdown in self::ATTEMPTS_META.
	 */
	private const ATTEMPT_BUDGET = 10;

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
	 * User meta key for the number of guesses remaining, as a plain integer counting down from
	 * self::ATTEMPT_BUDGET to 0 (locked out). Spans codes — requesting a new code does not reset it —
	 * so the lockout can't be sidestepped by re-requesting. See {@see self::claim_attempt()} for why
	 * it counts down rather than up.
	 */
	private const ATTEMPTS_META = '_wc_email_verification_attempts';

	/**
	 * The user's account email, lower-cased, or null when the user does not exist.
	 *
	 * Lower-casing here is the single normalisation point, so the verified-status match and the
	 * code's email-binding hash stay consistent however the address was capitalised.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string|null
	 */
	private function get_account_email( int $user_id ): ?string {
		$user = get_user_by( 'id', $user_id );

		return $user instanceof \WP_User ? strtolower( $user->user_email ) : null;
	}

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

		// Both sides are lower-cased (stored that way, get_account_email() normalises), so === is exact.
		return '' !== $verified_email && $verified_email === $this->get_account_email( $user_id );
	}

	/**
	 * Mark the given user as having verified their current account email address.
	 *
	 * Stores the verified email address, clears any pending code and the attempts counter, and
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

		$account_email = $this->get_account_email( $user_id );

		if ( null === $account_email ) {
			return;
		}

		// Storing the email (not a bool) lets the status self-invalidate if the account email later changes.
		Users::update_site_user_meta( $user_id, self::VERIFIED_META, $account_email );
		Users::delete_site_user_meta( $user_id, self::KEY_META );
		Users::delete_site_user_meta( $user_id, self::ATTEMPTS_META );

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
	 * Removes the verified-email meta, any pending code, and the remaining-attempts counter,
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
		Users::delete_site_user_meta( $user_id, self::ATTEMPTS_META );
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
	 * Minting a new code does not reset the remaining-attempts counter, so the lockout cannot be
	 * sidestepped by simply requesting fresh codes.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The plaintext 6-digit code.
	 */
	public function create_code( int $user_id ): string {
		$code          = $this->generate_code();
		$account_email = $this->get_account_email( $user_id );
		$email_hash    = null !== $account_email ? wp_fast_hash( $account_email ) : '';

		// Seed the attempts counter only when absent: pre-creating the row keeps verify_code()'s
		// compare-and-swap an update (never a racy insert), and not resetting it on resend means
		// re-requesting codes can't lift a lockout in progress.
		if ( '' === (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META ) ) {
			Users::update_site_user_meta( $user_id, self::ATTEMPTS_META, (string) self::ATTEMPT_BUDGET );
		}

		Users::update_site_user_meta( $user_id, self::KEY_META, time() . ':' . wp_fast_hash( $code ) . ':' . $email_hash . ':0' );

		return $code;
	}

	/**
	 * Verify a submitted code for the given user and record the outcome.
	 *
	 * Each guess first claims a slot from the remaining-attempts budget via {@see self::claim_attempt()},
	 * so concurrent submissions can't slip past the cap. Expired, missing, or email-mismatched codes
	 * return before a guess is claimed, so they never count against the customer. A correct code marks
	 * the user verified; reaching {@see self::MAX_ATTEMPTS} on one code burns it (a fresh one must be
	 * requested) and exhausting the budget locks the user out permanently.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $code    The plaintext code submitted by the customer.
	 * @return string One of the RESULT_* constants.
	 */
	public function verify_code( int $user_id, string $code ): string {
		$remaining = $this->attempts_remaining( $user_id );

		if ( null !== $remaining && $remaining <= 0 ) {
			return self::RESULT_LOCKED;
		}

		$parsed = $this->parse_stored_key( $user_id );

		if ( null === $parsed ) {
			return self::RESULT_NONE;
		}

		$account_email = $this->get_account_email( $user_id );

		if ( null === $account_email ) {
			return self::RESULT_NONE;
		}

		list( $timestamp, $hash, $email_hash, $attempts ) = $parsed;

		if ( time() - $timestamp > self::OTP_TTL ) {
			// Expired: a timeout, not a guess. Clear the dead code without penalising the customer.
			Users::delete_site_user_meta( $user_id, self::KEY_META );
			return self::RESULT_EXPIRED;
		}

		// The code is void if the account email no longer matches the one it was minted for.
		if ( ! wp_verify_fast_hash( $account_email, $email_hash ) ) {
			Users::delete_site_user_meta( $user_id, self::KEY_META );
			return self::RESULT_NONE;
		}

		// A live code exists, so create_code() must have created the counter row. If it is somehow
		// missing, fail closed rather than letting the compare-and-swap re-insert it with a fresh budget.
		if ( null === $remaining ) {
			return self::RESULT_LOCKED;
		}

		// Claim this guess by decrementing the remaining budget before comparing. If another request
		// moved the counter first, we lose the swap and turn away without a guess (no double-counting).
		if ( ! $this->claim_attempt( $user_id, $remaining ) ) {
			return self::RESULT_WRONG;
		}

		if ( '' !== $code && wp_verify_fast_hash( $code, $hash ) ) {
			$this->mark_verified( $user_id );
			return self::RESULT_OK;
		}

		// Wrong guess. The budget has already dropped to $remaining - 1.
		if ( $remaining - 1 <= 0 ) {
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
	 * Atomically claim a guess by decrementing the remaining-attempts budget via a compare-and-swap.
	 *
	 * Moves the counter from $remaining to $remaining - 1 only while it still equals $remaining, so
	 * concurrent submissions are serialised into distinct slots and at most ATTEMPT_BUDGET ever pass.
	 * $remaining is always 1..ATTEMPT_BUDGET here, so the previous value is never "0" — which
	 * update_user_meta() treats as empty and would ignore, making the swap non-conditional. The row is
	 * pre-created by {@see self::create_code()} so this only ever updates, never inserts (which would
	 * race into duplicate rows). Returns false when another request moved the counter first.
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $remaining The remaining count this request observed (>= 1).
	 * @return bool True when this request claimed the slot.
	 */
	private function claim_attempt( int $user_id, int $remaining ): bool {
		return (bool) Users::update_site_user_meta( $user_id, self::ATTEMPTS_META, (string) ( $remaining - 1 ), (string) $remaining );
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
	 * Whether the user has used up their attempt budget and is permanently locked out.
	 *
	 * The lockout only lifts when the user is verified another way (e.g. password reset) or the
	 * store owner verifies them from the admin — both of which clear the counter.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function is_locked_out( int $user_id ): bool {
		$remaining = $this->attempts_remaining( $user_id );

		return null !== $remaining && $remaining <= 0;
	}

	/**
	 * Return the number of guesses the user has left, or null if the flow has not started (no counter
	 * row yet).
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int|null
	 */
	private function attempts_remaining( int $user_id ): ?int {
		$raw = (string) Users::get_site_user_meta( $user_id, self::ATTEMPTS_META );

		return '' === $raw ? null : (int) $raw;
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

		if ( '' === $hash || '' === $email_hash || 0 === $timestamp ) {
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
