<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Drives the customer email-verification UI on My Account and processes its verify-links.
 *
 * Verification uses a one-time link emailed to the customer. Opening it lands on a lightweight
 * interstitial that auto-submits a POST; verification completes on that POST and ONLY when the
 * request is authenticated as the link's target user.
 *
 * That login requirement is what makes a link safe here:
 *
 * - An email client or security scanner that prefetches the link is never logged in as the customer,
 *   so a prefetch (even one that executes the interstitial's JS) can never complete verification.
 * - An attacker who registered an account with someone else's email can't read the victim's inbox, so
 *   never receives the link; and the victim can only reach a logged-in-as-target state by resetting the
 *   password, which invalidates the attacker's session — locking the attacker out rather than linking
 *   the victim's orders to them.
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
	 * Nonce action used to protect the confirm-email form submission.
	 */
	private const VERIFY_NONCE_ACTION = 'woocommerce-verify-email';

	/**
	 * Query param used to trigger the send-verification request.
	 */
	private const SEND_PARAM = 'wc_send_verification';

	/**
	 * Query/form param carrying the plaintext verification key.
	 */
	private const KEY_PARAM = 'wc_verify_email_key';

	/**
	 * Query/form param carrying the target user ID.
	 */
	private const USER_PARAM = 'wc_verify_email_user';

	/**
	 * Hidden form field marking a confirm submission.
	 */
	private const SUBMIT_FIELD = 'wc_verify_email_submit';

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
	 * Route an incoming request: a send request, a confirm submission, or an opened verify-link.
	 *
	 * Opening the emailed link is a GET, which email clients and security scanners routinely prefetch.
	 * A logged-out GET (including any prefetch) only renders the My Account login; a logged-in GET renders
	 * the interstitial. Verification happens solely on the authenticated POST the interstitial submits
	 * ({@see self::handle_confirm_submission()}), so neither a prefetch nor a logged-out visit can consume
	 * the key.
	 *
	 * @since 11.0.0
	 */
	public function maybe_process_request(): void {
		if ( isset( $_GET[ self::SEND_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_send_request();
			return;
		}

		if ( $this->is_confirm_submission() ) {
			$this->handle_confirm_submission();
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ self::KEY_PARAM ], $_GET[ self::USER_PARAM ] ) ) {
			// Logged out (including a prefetcher): let the My Account login render instead of the
			// interstitial, with a notice. The verify params stay in the page URL, so signing in returns
			// the visitor here (via the login form's referer) where, now authenticated, they verify.
			if ( ! is_user_logged_in() ) {
				wc_add_notice( __( 'You need to be logged in to confirm your email address.', 'woocommerce' ), 'notice' );
				return;
			}

			$this->render_confirm_page(
				absint( wp_unslash( $_GET[ self::USER_PARAM ] ) ),
				sanitize_text_field( wp_unslash( $_GET[ self::KEY_PARAM ] ) )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether the current request is a submission of the confirm form (JS auto-submit or no-JS button).
	 *
	 * @return bool
	 */
	private function is_confirm_submission(): bool {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

		// Nonce is verified in handle_confirm_submission(); this only routes the request.
		return 'POST' === $method && isset( $_POST[ self::SUBMIT_FIELD ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Verify the address after the interstitial submits the confirm form.
	 *
	 * The login gate lives here: verification only completes when the request is authenticated as the
	 * link's target user. A logged-out submission verifies no one and bounces back to the link's login
	 * (it normally never fires — logged-out visitors get the login on the GET); a visitor logged in as a
	 * different account is refused.
	 *
	 * @since 11.0.0
	 */
	private function handle_confirm_submission(): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::VERIFY_NONCE_ACTION ) ) {
			$this->redirect_with_result( 'invalid' );
		}

		$user_id = isset( $_POST[ self::USER_PARAM ] ) ? absint( wp_unslash( $_POST[ self::USER_PARAM ] ) ) : 0;
		$key     = isset( $_POST[ self::KEY_PARAM ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::KEY_PARAM ] ) ) : '';

		$current_user_id = get_current_user_id();

		// Logged out: never verify, and never consume the key. Bounce back to the verify-link, which (now
		// without a session) renders the My Account login; the notice and post-login return are handled
		// there. Normally unreachable — a logged-out visitor is shown the login on the GET, before the
		// interstitial's POST — but covers a session that lapsed between the interstitial and its submit.
		if ( ! $current_user_id ) {
			wp_safe_redirect( $this->service->build_verification_url_for_key( $user_id, $key ) );
			exit;
		}

		// Logged in as someone else: refuse rather than silently switching accounts.
		if ( $current_user_id !== $user_id ) {
			$this->redirect_with_result( 'mismatch' );
		}

		// Second clause: a double submit (e.g. JS auto-submit racing the no-JS button) finds the key
		// already consumed but the user verified — show success, not a stale "link expired" error.
		if ( $this->process_verification( $user_id, $key ) || $this->service->is_verified( $user_id ) ) {
			$this->redirect_with_result( 'confirmed' );
		}

		$this->redirect_with_result( 'expired' );
	}

	/**
	 * Output the lightweight confirm interstitial for an opened verify-link and stop.
	 *
	 * Performs no verification — a prefetch of the link only renders this. The page auto-submits its
	 * POST form via JavaScript and shows a manual button when JS is unavailable.
	 *
	 * @param int    $user_id User ID from the link.
	 * @param string $key     Plaintext verification key from the link.
	 * @return void
	 */
	private function render_confirm_page( int $user_id, string $key ): void {
		nocache_headers();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_confirm_page_html() escapes every interpolated value.
		echo $this->get_confirm_page_html( $user_id, $key );
		exit;
	}

	/**
	 * Build the confirm-interstitial HTML. Kept separate from output/exit so it is unit-testable.
	 *
	 * The key is reflected back only into a same-origin POST form on a noindex page carrying a
	 * referrer-policy of no-referrer, so it is not handed to third-party origins or search engines.
	 *
	 * @param int    $user_id User ID from the link.
	 * @param string $key     Plaintext verification key from the link.
	 * @return string Fully escaped, self-contained HTML document.
	 */
	private function get_confirm_page_html( int $user_id, string $key ): string {
		$heading = __( 'Confirm your email address', 'woocommerce' );
		$intro   = __( 'Confirm your email address to link your past orders to your account.', 'woocommerce' );
		$button  = __( 'Confirm email address', 'woocommerce' );

		$template = '<!DOCTYPE html>
<html %1$s>
<head>
<meta charset="%2$s" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<meta name="referrer" content="no-referrer" />
<title>%3$s</title>
</head>
<body>
<main class="wc-verify-email" style="max-width:32em;margin:4em auto;text-align:center;font-family:sans-serif;">
<h1>%3$s</h1>
<p>%4$s</p>
<form id="wc-verify-email-form" method="post" action="%5$s">
%6$s
<input type="hidden" name="%7$s" value="1" />
<input type="hidden" name="%8$s" value="%9$s" />
<input type="hidden" name="%10$s" value="%11$s" />
<button type="submit" style="font-size:1em;padding:0.75em 1.5em;cursor:pointer;">%12$s</button>
</form>
<script>document.getElementById( "wc-verify-email-form" ).submit();</script>
</main>
</body>
</html>';

		return sprintf(
			$template,
			get_language_attributes(),
			esc_attr( get_bloginfo( 'charset' ) ),
			esc_html( $heading ),
			esc_html( $intro ),
			esc_url( wc_get_page_permalink( 'myaccount' ) ),
			wp_nonce_field( self::VERIFY_NONCE_ACTION, '_wpnonce', true, false ),
			esc_attr( self::SUBMIT_FIELD ),
			esc_attr( self::USER_PARAM ),
			esc_attr( (string) $user_id ),
			esc_attr( self::KEY_PARAM ),
			esc_attr( $key ),
			esc_html( $button )
		);
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
