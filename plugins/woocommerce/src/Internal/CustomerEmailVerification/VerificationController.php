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
	 * Nonce action used to protect the send-verification request.
	 */
	private const SEND_NONCE_ACTION = 'woocommerce-send-verification-email';

	/**
	 * Query param used to trigger the send-verification request.
	 */
	private const SEND_PARAM = 'wc_send_verification';

	/**
	 * Minimum seconds between resend attempts (rate limit).
	 */
	private const SEND_RATE_LIMIT = 60;

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
		add_action( 'woocommerce_account_dashboard', array( $this, 'maybe_render_prompt' ) );
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
		if ( isset( $_GET[ self::SEND_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_send_request();
			return;
		}

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
	 * Handle a request to send (or resend) the verification email, triggered by the My Account prompt.
	 *
	 * Verifies the nonce and user, applies a rate-limit, then dispatches the email. Redirects back
	 * to My Account with a success or rate-limit notice added.
	 *
	 * @since 11.0.0
	 */
	public function handle_send_request(): void {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::SEND_NONCE_ACTION ) ) {
			wc_add_notice( __( 'Invalid request. Please try again.', 'woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}

		$seconds_since = $this->service->seconds_since_last_key( $user_id );
		if ( null !== $seconds_since && $seconds_since < self::SEND_RATE_LIMIT ) {
			wc_add_notice( __( 'A verification link was just sent — please check your inbox.', 'woocommerce' ) );
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}

		$this->send_verification_email( $user_id );

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: customer email address */
					__( "We've emailed a verification link to %s.", 'woocommerce' ),
					$user->user_email
				)
			);
		}

		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}

	/**
	 * Return whether the email-verification prompt should be shown to the current visitor.
	 *
	 * Returns true only for logged-in customers whose email address is not yet verified.
	 *
	 * @since 11.0.0
	 *
	 * @return bool
	 */
	public function should_show_prompt(): bool {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		return ! $this->service->is_verified( $user_id );
	}

	/**
	 * Render the email-verification prompt on the My Account dashboard for unverified customers.
	 *
	 * Hooked to {@see 'woocommerce_account_dashboard'}.
	 *
	 * @since 11.0.0
	 */
	public function maybe_render_prompt(): void {
		if ( ! $this->should_show_prompt() ) {
			return;
		}

		$send_url = wp_nonce_url(
			add_query_arg( self::SEND_PARAM, '1', wc_get_page_permalink( 'myaccount' ) ),
			self::SEND_NONCE_ACTION
		);

		?>
		<div class="woocommerce-info woocommerce-verify-email">
			<p><?php esc_html_e( 'Confirm your email address to access past orders placed with it.', 'woocommerce' ); ?></p>
			<a href="<?php echo esc_url( $send_url ); ?>" class="button woocommerce-button">
				<?php esc_html_e( 'Verify your email address', 'woocommerce' ); ?>
			</a>
		</div>
		<?php
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
	 * Build the one-time email-verification URL for the given user.
	 *
	 * Creates a fresh verification key via the service and returns the full URL
	 * containing both the key and the user ID as query parameters.
	 *
	 * @since 11.0.0
	 *
	 * @param int $user_id User ID.
	 * @return string The verification URL.
	 */
	public function build_verification_url( int $user_id ): string {
		$key = $this->service->create_verification_key( $user_id );
		return add_query_arg(
			array(
				'wc_verify_email_key'  => $key,
				'wc_verify_email_user' => $user_id,
			),
			wc_get_page_permalink( 'myaccount' )
		);
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
		$verify_url = $this->build_verification_url( $user_id );

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
