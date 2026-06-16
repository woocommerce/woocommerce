<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for account events that should change a customer's email-verification status.
 *
 * Completing a password reset proves the customer controls their inbox, so it marks the
 * email verified. This fires for both WordPress core resets (wp-login.php) and WooCommerce
 * resets (lost-password and the new-account set-password link), all of which are email-based
 * and dispatch the core `after_password_reset` action.
 *
 * @since 11.0.0
 */
class VerificationEventListener {

	/**
	 * Verification service.
	 *
	 * @var EmailVerificationService
	 */
	private $service;

	/**
	 * Constructor. Registers hooks.
	 */
	public function __construct() {
		add_action( 'after_password_reset', array( $this, 'on_password_reset' ) );
		add_action( 'profile_update', array( $this, 'on_profile_update' ), 10, 2 );
	}

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 *
	 * @param EmailVerificationService $service Verification service.
	 */
	final public function init( EmailVerificationService $service ): void {
		$this->service = $service;
	}

	/**
	 * Mark the user's email verified after a completed password reset.
	 *
	 * @param WP_User|mixed $user The user whose password was reset.
	 */
	public function on_password_reset( $user ): void {
		if ( $user instanceof WP_User ) {
			$this->service->mark_verified( $user->ID );
		}
	}

	/**
	 * Clear the email-verification status when a user changes their account email address.
	 *
	 * WordPress fires `profile_update` from `wp_update_user()` after every profile save,
	 * covering both wp-admin profile edits and WooCommerce My Account "account details"
	 * saves (which call `wp_update_user()` internally). If the email address has changed
	 * the previously-verified status is no longer meaningful — the user must re-verify
	 * ownership of the new address — so the verified flag is cleared.
	 *
	 * @since 11.0.0
	 *
	 * @param int|mixed     $user_id      ID of the user that was just updated.
	 * @param WP_User|mixed $old_user_data WP_User object containing the data before the update.
	 */
	public function on_profile_update( $user_id, $old_user_data ): void {
		if ( ! $old_user_data instanceof WP_User ) {
			return;
		}

		$new_user_data = get_userdata( (int) $user_id );

		if ( ! $new_user_data instanceof WP_User ) {
			return;
		}

		if ( strtolower( $old_user_data->user_email ) === strtolower( $new_user_data->user_email ) ) {
			return;
		}

		$this->service->clear_verification( (int) $user_id );
	}
}
