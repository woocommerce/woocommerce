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
}
