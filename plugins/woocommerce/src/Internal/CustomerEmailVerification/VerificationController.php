<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Processes email-verification links and sends/resends verification emails.
 *
 * @since 11.0.0
 */
class VerificationController {

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
		add_action( 'template_redirect', array( $this, 'maybe_process_request' ) );
	}

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 * @param EmailVerificationService $service Verification service.
	 */
	final public function init( EmailVerificationService $service ): void {
		$this->service = $service;
	}

	/**
	 * Read the verify-link query args from the current request and process them.
	 */
	public function maybe_process_request(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wc_verify_email_key'], $_GET['wc_verify_email_user'] ) ) {
			return;
		}
		$key     = sanitize_text_field( wp_unslash( $_GET['wc_verify_email_key'] ) );
		$user_id = absint( wp_unslash( $_GET['wc_verify_email_user'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $this->process_verification( $user_id, $key ) ) {
			if ( get_current_user_id() !== $user_id ) {
				wc_set_customer_auth_cookie( $user_id );
			}
			wc_add_notice( __( 'Your email address has been verified.', 'woocommerce' ) );
		} else {
			wc_add_notice( __( 'This verification link is invalid or has expired. Please request a new one.', 'woocommerce' ), 'error' );
		}

		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}

	/**
	 * Validate a key and verify the user.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Plaintext verification key.
	 * @return bool True when verification succeeded.
	 */
	public function process_verification( int $user_id, string $key ): bool {
		if ( ! $user_id || '' === $key ) {
			return false;
		}
		if ( ! $this->service->check_verification_key( $user_id, $key ) ) {
			return false;
		}
		$this->service->mark_verified( $user_id );
		return true;
	}

	/**
	 * Send (or resend) a verification email to a user.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id User ID.
	 */
	public function send_verification_email( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$key        = $this->service->create_verification_key( $user_id );
		$verify_url = add_query_arg(
			array(
				'wc_verify_email_key'  => $key,
				'wc_verify_email_user' => $user_id,
			),
			wc_get_page_permalink( 'myaccount' )
		);

		WC()->mailer();

		/**
		 * Triggers sending of the customer email-verification email.
		 *
		 * @param int    $user_id    The WordPress user ID of the customer.
		 * @param string $verify_url The one-time verification URL to include in the email.
		 *
		 * @since 11.0.0
		 */
		do_action( 'woocommerce_customer_verify_email_notification', $user_id, $verify_url );
	}
}
