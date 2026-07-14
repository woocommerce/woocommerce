<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * Service class providing the foundational primitives for customer email verification.
 *
 * This class is the single source of truth for whether a customer has proven they
 * control their account email address. It manages the verified-status meta and the
 * short-lived, single-use verification key carried by the emailed verify-link, together
 * with the helpers consumed by the rest of the email-verification feature.
 *
 * The verify-link is only ever *completed* by a request authenticated as the link's target
 * user (see {@see VerificationController::handle_confirm_submission()}), so the key needs no
 * brute-force protection: it is high-entropy ({@see self::KEY_LENGTH} chars), single-use, and
 * expires after {@see self::KEY_TTL}.
 *
 * @since 11.0.0
 */
class EmailVerificationService {

	/**
	 * Length of the generated verification key. A 20-char alphanumeric key is high-entropy enough that
	 * it needs no attempt limiting (unlike a 6-digit code), so this flow carries no lockout machinery.
	 */
	private const KEY_LENGTH = 20;

	/**
	 * How long a freshly minted verification key remains valid.
	 */
	private const KEY_TTL = DAY_IN_SECONDS;

	/**
	 * User meta key that stores the verified email address (lower-cased).
	 * The customer is considered verified only while this matches their current account email.
	 */
	private const VERIFIED_META = '_wc_email_verified';

	/**
	 * User meta key that stores the verification token as "{timestamp}:{key_hash}:{email_hash}".
	 * Overwritten on every new key; deleted when the key is consumed or the user verifies.
	 */
	private const KEY_META = '_wc_email_verification_key';

	/**
	 * The user's account email, lower-cased, or null when the user does not exist.
	 *
	 * Lower-casing here is the single normalisation point, so the verified-status match and the
	 * key's email-binding hash stay consistent however the address was capitalised.
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
	 * Stores the verified email address, clears any pending key, and fires the
	 * {@see 'woocommerce_customer_email_verified'} action. No-ops if the user is already
	 * verified for their current email.
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
	 * Removes both the verified-email meta and any pending verification key,
	 * effectively resetting the user to an unverified state.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	public function clear_verification( int $user_id ): void {
		Users::delete_site_user_meta( $user_id, self::VERIFIED_META );
		Users::delete_site_user_meta( $user_id, self::KEY_META );
	}

	/**
	 * Generate and store a one-time email-verification key for the given user.
	 *
	 * The plaintext key is returned for inclusion in the verification email link. The stored value is
	 * a "{timestamp}:{key_hash}:{email_hash}" triplet so the plaintext is never persisted, the key
	 * expires after {@see self::KEY_TTL}, and the email hash binds the key to the account email in
	 * effect at issuance (a key emailed to one address can never verify a different address the account
	 * is later switched to).
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The plaintext verification key.
	 */
	public function create_verification_key( int $user_id ): string {
		$key           = wp_generate_password( self::KEY_LENGTH, false );
		$account_email = $this->get_account_email( $user_id );
		$email_hash    = null !== $account_email ? wp_fast_hash( $account_email ) : '';

		Users::update_site_user_meta( $user_id, self::KEY_META, time() . ':' . wp_fast_hash( $key ) . ':' . $email_hash );

		return $key;
	}

	/**
	 * Build a one-time email-verification URL for the given user.
	 *
	 * Mints a fresh verification key and returns the My Account URL carrying that key and the user ID
	 * as query args, ready to drop into an email. The matching reader is
	 * {@see VerificationController::maybe_process_request()}.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The verification URL.
	 */
	public function build_verification_url( int $user_id ): string {
		return $this->build_verification_url_for_key( $user_id, $this->create_verification_key( $user_id ) );
	}

	/**
	 * Build the My Account verify-link URL carrying a specific (already-issued) key.
	 *
	 * Unlike {@see self::build_verification_url()} this mints nothing — it re-emits an existing key, e.g.
	 * to bounce a logged-out visitor back to the link they opened so they can finish after signing in.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $key     Plaintext verification key to embed.
	 * @return string The verification URL.
	 */
	public function build_verification_url_for_key( int $user_id, string $key ): string {
		return add_query_arg(
			array(
				'wc_verify_email_key'  => $key,
				'wc_verify_email_user' => $user_id,
			),
			wc_get_page_permalink( 'myaccount' )
		);
	}

	/**
	 * Validate a plaintext verification key against the stored hash for the given user.
	 *
	 * Returns false if no key is stored, if the key has expired, if the account email has changed since
	 * the key was issued, or if the key does not match the stored hash.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $key     The plaintext verification key to check.
	 * @return bool True when the key is valid and has not expired.
	 */
	public function check_verification_key( int $user_id, string $key ): bool {
		if ( '' === $key ) {
			return false;
		}

		$parsed = $this->parse_stored_key( $user_id );

		if ( null === $parsed ) {
			return false;
		}

		list( $timestamp, $hash, $email_hash ) = $parsed;

		if ( time() - $timestamp > self::KEY_TTL ) {
			return false;
		}

		// The key is void if the account email no longer matches the one it was minted for.
		$account_email = $this->get_account_email( $user_id );

		if ( null === $account_email || '' === $email_hash || ! wp_verify_fast_hash( $account_email, $email_hash ) ) {
			return false;
		}

		return wp_verify_fast_hash( $key, $hash );
	}

	/**
	 * Whether the user currently has a pending (minted, unexpired) verification key awaiting use.
	 *
	 * Used to decide whether the My Account prompt shows the "check your inbox" notice or the "send a
	 * confirmation link" call to action.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function has_pending_key( int $user_id ): bool {
		$parsed = $this->parse_stored_key( $user_id );

		return null !== $parsed && time() - $parsed[0] <= self::KEY_TTL;
	}

	/**
	 * Parse the stored verification token into its timestamp, key-hash, and email-hash parts.
	 *
	 * The token is persisted as "{timestamp}:{key_hash}:{email_hash}"; this is the single place that
	 * knows that format.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array{0: int, 1: string, 2: string}|null The triplet, or null when none is stored.
	 */
	private function parse_stored_key( int $user_id ): ?array {
		$stored = (string) Users::get_site_user_meta( $user_id, self::KEY_META );

		if ( ! str_contains( $stored, ':' ) ) {
			return null;
		}

		$parts      = explode( ':', $stored, 3 );
		$timestamp  = (int) ( $parts[0] ?? 0 );
		$hash       = (string) ( $parts[1] ?? '' );
		$email_hash = (string) ( $parts[2] ?? '' );

		if ( '' === $hash || '' === $email_hash || 0 === $timestamp ) {
			return null;
		}

		return array( $timestamp, $hash, $email_hash );
	}

	/**
	 * Return the number of seconds elapsed since the last verification key was issued, or null if none
	 * exists.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int|null Seconds since the last key was created, or null when none is stored.
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
