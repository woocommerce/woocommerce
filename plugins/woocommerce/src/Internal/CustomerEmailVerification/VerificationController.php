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
	 * Order linker, used to detect whether there are guest orders worth verifying for.
	 *
	 * @var OrderLinker
	 */
	private $order_linker;

	/**
	 * Constructor. Registers hooks.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_process_request' ) );
		// Priority 1 so the notice is queued before woocommerce_output_all_notices() prints it (priority 5).
		add_action( 'woocommerce_account_content', array( $this, 'maybe_add_orders_notice' ), 1 );
	}

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 * @param EmailVerificationService $service      Verification service.
	 * @param OrderLinker              $order_linker Order linker.
	 */
	final public function init( EmailVerificationService $service, OrderLinker $order_linker ): void {
		$this->service      = $service;
		$this->order_linker = $order_linker;
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

		$current_user_id = get_current_user_id();

		// Refuse verification if the email key was for a different user than the one logged in.
		if ( $current_user_id && $current_user_id !== $user_id ) {
			wc_add_notice( __( 'Unable to confirm this email while you are logged in to a different account. Please log out and open the link again.', 'woocommerce' ), 'error' );
		} elseif ( $this->process_verification( $user_id, $key ) ) {
			if ( 0 === $current_user_id ) {
				wc_set_customer_auth_cookie( $user_id );
			}
			wc_add_notice( __( 'Your email address has been confirmed.', 'woocommerce' ) );
		} else {
			wc_add_notice( __( 'This confirmation link is invalid or has expired. Please request a new one.', 'woocommerce' ), 'error' );
		}

		// Land on the Orders endpoint where the linked orders and the verification prompt live.
		wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
		exit;
	}

	/**
	 * Handle a request to send (or resend) the verification email, triggered by the My Account prompt.
	 *
	 * Verifies the nonce, applies a rate-limit (does not re-send within the window), dispatches the
	 * email, and redirects to the orders section. The orders notice points the customer to their
	 * inbox, so no extra confirmation notice is added here.
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
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		// Only send a fresh link if the last one is outside the rate-limit window; otherwise the
		// existing link still stands. Either way the orders notice points the customer to their inbox.
		$seconds_since = $this->service->seconds_since_last_key( $user_id );
		if ( null === $seconds_since || $seconds_since >= self::SEND_RATE_LIMIT ) {
			$this->send_verification_email( $user_id );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
		exit;
	}

	/**
	 * Return whether the email-confirmation notice should be shown on the current page.
	 *
	 * True only for a logged-in customer who is not yet confirmed, does not have a temporary
	 * password (those confirm via their set-password link), and has at least one guest order that
	 * could be linked (existence only; no details exposed). Whether the notice carries a call to
	 * action or a "check your inbox" message is decided in {@see self::maybe_add_orders_notice()}.
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

		if ( $this->service->is_verified( $user_id ) ) {
			return false;
		}

		// A temporary-password account already has a set-password link (which also verifies on use),
		// surfaced by the temporary-password notice — don't show a second prompt alongside it.
		if ( get_user_option( 'default_password_nag', $user_id ) ) {
			return false;
		}

		return $this->order_linker->has_linkable_orders( $user_id );
	}

	/**
	 * Add the email-verification prompt as a standard notice on the My Account "Orders" section.
	 *
	 * Hooked to {@see 'woocommerce_account_content'} at priority 1, so the notice is queued before
	 * woocommerce_output_all_notices() prints it (priority 5). Only shown on the orders endpoint,
	 * and only when there are guest orders worth verifying for.
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function maybe_add_orders_notice(): void {
		if ( ! is_wc_endpoint_url( 'orders' ) || ! $this->should_show_prompt() ) {
			return;
		}

		// Within the rate-limit window a confirmation link was sent recently: point the customer to
		// their inbox without a resend call to action.
		$seconds_since = $this->service->seconds_since_last_key( get_current_user_id() );
		if ( null !== $seconds_since && $seconds_since < self::SEND_RATE_LIMIT ) {
			wc_add_notice( __( 'Confirm your email address to view past orders. We emailed you a link to confirm your email.', 'woocommerce' ), 'notice' );
			return;
		}

		$send_url = wp_nonce_url(
			add_query_arg( self::SEND_PARAM, '1', wc_get_account_endpoint_url( 'orders' ) ),
			self::SEND_NONCE_ACTION
		);

		$notice = sprintf(
			'%1$s <a href="%2$s" class="button wc-forward">%3$s</a>',
			esc_html__( 'Confirm your email address to view past orders.', 'woocommerce' ),
			esc_url( $send_url ),
			esc_html__( 'Confirm email address', 'woocommerce' )
		);

		wc_add_notice( $notice, 'notice' );
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
		$verify_url = $this->service->build_verification_url( $user_id );

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
