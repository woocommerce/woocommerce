<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Drives the customer email-verification UI on My Account and processes its verify-links.
 *
 * Verification uses a one-time link emailed to the customer. Opening the link verifies the address
 * directly — but ONLY when the request is authenticated as the link's target user. The login gate is
 * the control, not the HTTP verb.
 *
 * This intentionally mirrors WordPress core's own email-change confirmation links, which likewise
 * complete a sensitive change on an authenticated GET carrying an unguessable secret — no interstitial,
 * form, or nonce — relying on the auth gate plus the secret:
 *
 * - Administration email change: wp-admin/options.php (`adminhash`) —
 *   https://github.com/WordPress/WordPress/blob/master/wp-admin/options.php
 * - Profile email change: wp-admin/user-edit.php (`newuseremail`) —
 *   https://github.com/WordPress/WordPress/blob/master/wp-admin/user-edit.php
 *
 * What makes the link safe:
 *
 * - A prefetch (email client or security scanner) is never logged in as the customer, so it can never
 *   reach the verify branch — it only ever sees the My Account login. It cannot consume the key.
 * - The key is a one-time, time-limited secret bound by hash to the account's current email, so it is
 *   inert without an authenticated session as the target: a leaked key cannot be spent by anyone who is
 *   not already that user (which is also why, like core, it is safe to carry the key in the URL).
 * - An attacker who registered an account with someone else's email can't read the victim's inbox, so
 *   never receives the link; and the victim can only reach a logged-in-as-target state by resetting the
 *   password, which invalidates the attacker's session.
 *
 * No auth cookie is ever minted by the link (that would be exploitable as login CSRF): a logged-out
 * visitor is shown the My Account login on the link itself, and signing in returns them to the link
 * (the verify params are preserved in its URL) to complete it as themselves.
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
	 * Query param carrying the plaintext verification key.
	 */
	private const KEY_PARAM = 'wc_verify_email_key';

	/**
	 * Query param carrying the target user ID.
	 */
	private const USER_PARAM = 'wc_verify_email_user';

	/**
	 * Query param carrying a one-off result code to print as a notice on the account page.
	 */
	private const NOTICE_PARAM = 'wc_verify_notice';

	/**
	 * Minimum seconds between sends (rate limit).
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
		add_action( 'woocommerce_before_account_orders', array( $this, 'print_result_notice' ), 5 );
		add_action( 'woocommerce_before_account_orders', array( $this, 'render_prompt' ) );
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
	 * Route an incoming request: a send request or an opened verify-link.
	 *
	 * Opening the emailed link is a GET, which email clients and security scanners routinely prefetch.
	 * Verification is gated on authentication ({@see self::handle_verify_link()}), so a prefetch — always
	 * logged out — only ever reaches the My Account login and can never consume the key.
	 *
	 * @since 11.0.0
	 */
	public function maybe_process_request(): void {
		if ( isset( $_GET[ self::SEND_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_send_request();
			return;
		}

		// No nonce on the verify-link: like WordPress core's email-change confirmation links, the
		// unguessable one-time key is the CSRF defence and the login gate is the authority.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ self::KEY_PARAM ], $_GET[ self::USER_PARAM ] ) ) {
			$this->handle_verify_link(
				absint( wp_unslash( $_GET[ self::USER_PARAM ] ) ),
				sanitize_text_field( wp_unslash( $_GET[ self::KEY_PARAM ] ) )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Verify the address from an opened verify-link — gated on being logged in as the link's target user.
	 *
	 * The login gate is the control: verification, and key consumption, happen ONLY on the path where the
	 * request is authenticated as $user_id. A prefetch or any logged-out visit is shown the My Account
	 * login and never touches the key; a visitor logged in as a different account is refused without
	 * consuming it. This is the same shape as WordPress core's email-change confirmation links
	 * (wp-admin/options.php `adminhash`, wp-admin/user-edit.php `newuseremail`): a sensitive change
	 * completed on an authenticated GET carrying an unguessable secret.
	 *
	 * @since 11.0.0
	 *
	 * @param int    $user_id Target user ID from the link.
	 * @param string $key     Plaintext verification key from the link.
	 * @return void
	 */
	private function handle_verify_link( int $user_id, string $key ): void {
		// The key rides in the URL, so keep this response off caches and out of third-party Referer
		// headers (the logged-out branch renders a themed front-end page that may load such assets).
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Referrer-Policy: no-referrer' );
		}

		$current_user_id = get_current_user_id();

		// Logged out (including any prefetcher): never verify, never consume the key. Render the My
		// Account login; the verify params stay in the URL so signing in returns here to complete it.
		if ( ! $current_user_id ) {
			wc_add_notice( __( 'You need to be logged in to confirm your email address.', 'woocommerce' ), 'notice' );
			return;
		}

		// Logged in as someone else: refuse rather than silently switching accounts. The key is untouched.
		if ( $current_user_id !== $user_id ) {
			$this->redirect_with_result( 'mismatch' );
		}

		// Authenticated as the target — the only path that consumes the key and verifies.
		if ( $this->process_verification( $user_id, $key ) ) {
			$this->redirect_with_result( 'confirmed' );
		}

		// Already verified (e.g. the link re-opened after the key was spent): land on Orders quietly,
		// without repeating the success notice for a confirmation that already happened.
		if ( $this->service->is_verified( $user_id ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		// Authenticated as the target, but the key is invalid or expired and they are not verified.
		$this->redirect_with_result( 'expired' );
	}

	/**
	 * Handle a request to send (or resend) the verification email, triggered by the My Account prompt.
	 *
	 * Verifies the nonce, applies a rate-limit (does not re-send within the window), dispatches the
	 * email, and redirects to the orders section, where the prompt points the customer to their inbox.
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
			$this->redirect_with_result( 'invalid' );
		}

		// Only send a fresh link once the last one is outside the rate-limit window; otherwise the
		// existing link still stands and the prompt continues to point the customer to their inbox.
		$seconds_since = $this->service->seconds_since_last_key( $user_id );
		if ( null === $seconds_since || $seconds_since >= self::SEND_RATE_LIMIT ) {
			$this->send_verification_email( $user_id );
			$this->redirect_with_result( 'sent' );
		}

		$this->redirect_with_result( 'throttled' );
	}

	/**
	 * Return whether the verification prompt should be shown for the current user.
	 *
	 * True for a logged-in, unverified customer, except one still using a temporary password (those
	 * confirm via their set-password link, so the temporary-password notice already covers it). This
	 * must not depend on whether matching guest orders exist, because that would disclose order
	 * existence before the customer proves they control the email address.
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

		return true;
	}

	/**
	 * Render the verification prompt notice on the My Account "Orders" panel.
	 *
	 * Within the rate-limit window a link was sent recently, so the prompt points the customer to their
	 * inbox and offers no immediate resend; otherwise it carries the "confirm email" call to action.
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function render_prompt(): void {
		if ( ! $this->should_show_prompt() ) {
			return;
		}

		$user_id       = get_current_user_id();
		$seconds_since = $this->service->seconds_since_last_key( $user_id );

		if ( null !== $seconds_since && $seconds_since <= self::SEND_RATE_LIMIT ) {
			// A just-sent/throttled result notice (from the redirect) already points to the inbox this
			// page load, so don't print a second "check your inbox" alongside it.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only, no state change.
			if ( ! isset( $_GET[ self::NOTICE_PARAM ] ) ) {
				wc_print_notice(
					esc_html__( 'Confirm your email address to check for past orders. A confirmation link was sent recently — please check your inbox.', 'woocommerce' ),
					'notice'
				);
			}
			return;
		}

		$send_url = wp_nonce_url(
			add_query_arg( self::SEND_PARAM, '1', wc_get_account_endpoint_url( 'orders' ) ),
			self::SEND_NONCE_ACTION
		);

		$notice = sprintf(
			'<a href="%2$s" class="button wc-forward">%3$s</a> %1$s',
			esc_html__( 'Confirm your email address to check for past orders and link them to your account.', 'woocommerce' ),
			esc_url( $send_url ),
			esc_html__( 'Confirm email address', 'woocommerce' )
		);

		wc_print_notice( $notice, 'notice' );
	}

	/**
	 * Print the one-off result notice carried by the {@see self::NOTICE_PARAM} query arg, if any.
	 *
	 * Send/confirm actions redirect here with a result code rather than queuing a session notice, so the
	 * page shows exactly the current request's outcome — re-running an action can't stack notices.
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function print_result_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only, no state change.
		$code   = isset( $_GET[ self::NOTICE_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ self::NOTICE_PARAM ] ) ) : '';
		$notice = $this->result_notice( $code );

		if ( null !== $notice ) {
			wc_print_notice( esc_html( $notice[0] ), $notice[1] );
		}
	}

	/**
	 * Map a redirect result code to its [ message, notice type ], or null for an unknown code.
	 *
	 * @param string $code Result code from a send/confirm redirect.
	 * @return array{0: string, 1: string}|null
	 */
	private function result_notice( string $code ): ?array {
		switch ( $code ) {
			case 'sent':
				return array( __( 'A confirmation link has been sent to your email address. Please check your inbox.', 'woocommerce' ), 'success' );
			case 'throttled':
				return array( __( 'A confirmation link was sent recently. Please check your inbox, or wait a moment before requesting a new one.', 'woocommerce' ), 'notice' );
			case 'confirmed':
				return array( __( 'Your email address has been confirmed.', 'woocommerce' ), 'success' );
			case 'expired':
				return array( __( 'This confirmation link is invalid or has expired. Please request a new one.', 'woocommerce' ), 'error' );
			case 'mismatch':
				return array( __( 'Unable to confirm this email while you are logged in to a different account. Please log out and open the link again.', 'woocommerce' ), 'error' );
			case 'invalid':
				return array( __( 'Invalid request. Please try again.', 'woocommerce' ), 'error' );
			default:
				return null;
		}
	}

	/**
	 * Redirect to the orders section carrying a one-off result code, then exit.
	 *
	 * @param string $code Result code understood by {@see self::result_notice()}.
	 * @return never
	 */
	private function redirect_with_result( string $code ): void {
		wp_safe_redirect( add_query_arg( self::NOTICE_PARAM, $code, wc_get_account_endpoint_url( 'orders' ) ) );
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
