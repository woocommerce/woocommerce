<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Email;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Appends a verify-email link to the new-account email for customers who set
 * their own password.
 *
 * When a password is auto-generated, the new-account email already contains a
 * set-password magic link that routes through {@see after_password_reset} and
 * marks the address as verified. When the customer set their own password there
 * is no such link, so we inject a dedicated verify link to ensure there is
 * always exactly one verifying path in the email.
 *
 * @since 11.0.0
 */
class NewAccountEmailVerificationLink {

	/**
	 * Verification controller used to build the one-time verify URL.
	 *
	 * @var VerificationController
	 */
	private VerificationController $controller;

	/**
	 * Whether register() has already been called.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 *
	 * @param VerificationController $controller Verification controller.
	 */
	final public function init( VerificationController $controller ): void {
		$this->controller = $controller;
	}

	/**
	 * Register hooks (idempotent — safe to call multiple times).
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;
		add_filter( 'woocommerce_email_additional_content_customer_new_account', array( $this, 'append_verify_link' ), 10, 3 );
	}

	/**
	 * Append a verify-email link to the additional content of the new-account email.
	 *
	 * Hooked to {@see 'woocommerce_email_additional_content_customer_new_account'}.
	 *
	 * No link is added when the password was auto-generated because the set-password
	 * magic link in the same email already serves as the verification step.
	 *
	 * @since 11.0.0
	 *
	 * @param string   $content      Additional content already set for the email.
	 * @param mixed    $email_object The WP_User object for the new account.
	 * @param WC_Email $email        The email instance.
	 * @return string The (possibly augmented) additional content.
	 */
	public function append_verify_link( string $content, $email_object, WC_Email $email ): string {
		if ( ! $email_object instanceof WP_User ) {
			return $content;
		}

		// When the password was generated, the set-password link in the email
		// already verifies the address via after_password_reset. Skip.
		if ( ! empty( $email->password_generated ) ) {
			return $content;
		}

		$verify_url = $this->controller->build_verification_url( $email_object->ID );

		$verify_paragraph = sprintf(
			/* translators: %s: one-time email-verification URL */
			__( 'To verify your email address, <a href="%s">click here</a>.', 'woocommerce' ),
			esc_url( $verify_url )
		);

		if ( '' !== $content ) {
			return $content . "\n\n" . $verify_paragraph;
		}

		return $verify_paragraph;
	}
}
