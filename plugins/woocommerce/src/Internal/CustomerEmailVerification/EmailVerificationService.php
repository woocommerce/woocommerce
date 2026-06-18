<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * Service class providing the foundational primitives for customer email verification.
 *
 * This class is the single source of truth for whether a customer has proven they
 * control their account email address. It manages the verified status meta, the
 * expiring verification token used in emailed verify-links, and provides helper
 * methods consumed by the rest of the email-verification feature.
 *
 * @since 11.0.0
 */
class EmailVerificationService {

	/**
	 * User meta key that stores the verified email address (lower-cased).
	 * The customer is considered verified only while this matches their current account email.
	 */
	private const VERIFIED_META = '_wc_email_verified';

	/**
	 * User meta key that stores the verification token as "{timestamp}:{wp_fast_hash}".
	 */
	private const KEY_META = '_wc_email_verification_key';

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
	 * Stores the verified email address, clears the pending verification key, and
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
	 * The plaintext key is returned for inclusion in the verification email link.
	 * The stored value is a "{timestamp}:{wp_fast_hash}" pair so the plaintext is
	 * never persisted and the token expires after DAY_IN_SECONDS.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The plaintext verification key.
	 */
	public function create_verification_key( int $user_id ): string {
		$key = wp_generate_password( 20, false );
		Users::update_site_user_meta( $user_id, self::KEY_META, time() . ':' . wp_fast_hash( $key ) );
		return $key;
	}

	/**
	 * Build a one-time email-verification URL for the given user.
	 *
	 * Mints a fresh verification key and returns the My Account URL carrying that key and the
	 * user ID as query args, ready to drop into an email. The matching reader is
	 * {@see VerificationController::maybe_process_request()}.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string The verification URL.
	 */
	public function build_verification_url( int $user_id ): string {
		return add_query_arg(
			array(
				'wc_verify_email_key'  => $this->create_verification_key( $user_id ),
				'wc_verify_email_user' => $user_id,
			),
			wc_get_page_permalink( 'myaccount' )
		);
	}

	/**
	 * Validate a plaintext verification key against the stored hash for the given user.
	 *
	 * Returns false if no token is stored, if the token has expired, or if the key
	 * does not match the stored hash.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $key     The plaintext verification key to check.
	 * @return bool True when the key is valid and has not expired.
	 */
	public function check_verification_key( int $user_id, string $key ): bool {
		$parsed = $this->parse_stored_key( $user_id );

		if ( null === $parsed ) {
			return false;
		}

		list( $timestamp, $hash ) = $parsed;

		if ( time() - $timestamp > DAY_IN_SECONDS ) {
			return false;
		}

		return wp_verify_fast_hash( $key, $hash );
	}

	/**
	 * Parse the stored verification token into its timestamp and hash parts.
	 *
	 * The token is persisted as "{timestamp}:{wp_fast_hash}"; this is the single place
	 * that knows that format.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array{0: int, 1: string}|null The [timestamp, hash] pair, or null when no token is stored.
	 */
	private function parse_stored_key( int $user_id ): ?array {
		$stored = (string) Users::get_site_user_meta( $user_id, self::KEY_META );

		if ( ! str_contains( $stored, ':' ) ) {
			return null;
		}

		list( $timestamp, $hash ) = explode( ':', $stored, 2 );

		return array( (int) $timestamp, $hash );
	}

	/**
	 * Return the number of seconds elapsed since the last verification key was issued, or null if no key exists.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int|null Seconds since the last key was created, or null when no key is stored.
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
