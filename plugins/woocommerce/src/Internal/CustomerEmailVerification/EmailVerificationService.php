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
	 * User meta key that stores the email-verified timestamp.
	 * A truthy (non-empty) value means the customer is verified.
	 */
	private const VERIFIED_META = '_wc_email_verified';

	/**
	 * User meta key that stores the verification token as "{timestamp}:{wp_fast_hash}".
	 */
	private const KEY_META = '_wc_email_verification_key';

	/**
	 * Return whether the given user has verified their email address.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool True when the verification meta is non-empty.
	 */
	public function is_verified( int $user_id ): bool {
		return (bool) Users::get_site_user_meta( $user_id, self::VERIFIED_META );
	}

	/**
	 * Mark the given user as having verified their email address.
	 *
	 * Sets the verified-timestamp meta, clears the pending verification key, and
	 * fires the {@see 'woocommerce_customer_email_verified'} action. No-ops if the
	 * user is already verified.
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

		Users::update_site_user_meta( $user_id, self::VERIFIED_META, time() );
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
	 * Removes both the verified-timestamp meta and any pending verification key,
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
	 * never persisted and the token expires after {@see 'woocommerce_email_verification_expiration'}.
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
		$stored = (string) Users::get_site_user_meta( $user_id, self::KEY_META );

		if ( ! str_contains( $stored, ':' ) ) {
			return false;
		}

		list( $timestamp, $hash ) = explode( ':', $stored, 2 );

		if ( time() - (int) $timestamp > $this->get_expiration() ) {
			return false;
		}

		return wp_verify_fast_hash( $key, $hash );
	}

	/**
	 * Return whether the store currently requires email verification.
	 *
	 * Reads the {@see 'woocommerce_require_email_verification'} option. This method
	 * is intentionally read-only; writing the option is handled elsewhere.
	 *
	 * @since 11.0.0
	 *
	 * @return bool True when the option is set to 'yes'.
	 */
	public function should_require_verification(): bool {
		return 'yes' === get_option( 'woocommerce_require_email_verification', 'no' );
	}

	/**
	 * Return the number of seconds a verification token remains valid.
	 *
	 * @since 11.0.0
	 *
	 * @return int Expiration period in seconds.
	 */
	public function get_expiration(): int {
		/**
		 * Filters the number of seconds a customer email-verification token remains valid.
		 *
		 * @param int $expiration Expiration period in seconds. Default is DAY_IN_SECONDS (86400).
		 *
		 * @since 11.0.0
		 */
		return (int) apply_filters( 'woocommerce_email_verification_expiration', DAY_IN_SECONDS );
	}
}
