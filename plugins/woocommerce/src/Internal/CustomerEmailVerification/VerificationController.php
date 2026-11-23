<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Drives the customer email-verification UI on My Account and processes its confirmation links.
 *
 * The verification link (from the My Account prompt) confirms an address. Opening the link never confirms anything on
 * its own, because the browser's login session cannot prove who opened it: a cross-site login form can sign a browser in
 * to someone else's account.
 * Instead, the link shows one form on its own, without the account navigation. The link's key and the account password
 * must arrive in the same request:
 *
 * - Logged in as the link's account: the form asks for the password.
 * - Logged out: the form asks for the password, and logs the customer in. The page does not show the email address.
 * - Logged in as another account: refused.
 *
 * Prefetchers and mail scanners only ever open links, so they cannot confirm one. The form only shows for a valid key,
 * password attempts are rate limited, and the key stays in the URL only (never in a Referer header).
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
	 * Nonce action used to protect the password confirmation form.
	 */
	private const CONFIRM_NONCE_ACTION = 'woocommerce-confirm-email';

	/**
	 * Password attempts allowed on the confirmation form before the delay applies.
	 */
	private const CONFIRM_FREE_ATTEMPTS = 3;

	/**
	 * Seconds after the last attempt before the attempt count resets.
	 */
	private const CONFIRM_ATTEMPT_WINDOW = 15 * MINUTE_IN_SECONDS;

	/**
	 * Seconds a customer waits between password attempts once the free attempts are used.
	 */
	private const CONFIRM_RETRY_DELAY = 5;

	/**
	 * The account whose confirmation form this request shows, or null when there is no form to show.
	 *
	 * @var \WP_User|null
	 */
	private $confirm_form_user = null;

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
		// After the session notices (priority 5), before the account content (priority 10).
		add_action( 'woocommerce_account_content', array( $this, 'print_result_notice' ), 6 );
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
	 * Route an incoming request: a send request, a submitted password form, or an opened confirmation link.
	 *
	 * @since 11.0.0
	 */
	public function maybe_process_request(): void {
		if ( isset( $_GET[ self::SEND_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_send_request();
			return;
		}

		$link = $this->get_link_from_request();

		if ( null === $link ) {
			return;
		}

		// The key rides in the URL, so keep this response off caches and out of third-party Referer headers.
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Referrer-Policy: no-referrer' );
		}

		$this->handle_link( $link );
	}

	/**
	 * Read the confirmation link from the request URL.
	 *
	 * @return array{user_id: int, key: string}|null The link, or null when the URL has none.
	 */
	private function get_link_from_request(): ?array {
		// No nonce: the unguessable one-time key in the URL is what ties the request to the email.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::KEY_PARAM ], $_GET[ self::USER_PARAM ] ) || ! is_string( $_GET[ self::KEY_PARAM ] ) || ! is_string( $_GET[ self::USER_PARAM ] ) ) {
			return null;
		}

		return array(
			'user_id' => absint( wp_unslash( $_GET[ self::USER_PARAM ] ) ),
			'key'     => sanitize_text_field( wp_unslash( $_GET[ self::KEY_PARAM ] ) ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle an opened confirmation link. Opening a link never confirms it.
	 *
	 * A valid link shows the confirmation form, for logged out visitors and for the link's account. Anyone else is
	 * refused. The key is checked first, so nobody without it can reach the password check.
	 *
	 * @param array{user_id: int, key: string} $link Confirmation link.
	 * @return void
	 */
	private function handle_link( array $link ): void {
		$current_user_id = get_current_user_id();

		if ( $current_user_id && $current_user_id !== $link['user_id'] ) {
			$this->redirect_with_result( 'mismatch' );
		}

		$user         = get_user_by( 'id', $link['user_id'] );
		$key_is_valid = $user instanceof \WP_User && $this->service->check_verification_key( $link['user_id'], $link['key'] );

		// Already verified (e.g. the link was opened again): land on the dashboard quietly.
		if ( ( $current_user_id || $key_is_valid ) && $this->service->is_verified( $link['user_id'] ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}

		if ( ! $user instanceof \WP_User || ! $key_is_valid ) {
			if ( $current_user_id ) {
				$this->redirect_with_result( 'expired' );
			}

			wc_add_notice( __( 'This confirmation link is invalid or has expired. Log in to request a new one.', 'woocommerce' ), 'error' );
			return;
		}

		if ( isset( $_POST['wc_confirm_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_confirm_form().
			$this->handle_confirm_form( $link, $user );
		}

		$this->confirm_form_user = $user;
	}

	/**
	 * Check the submitted confirmation form, then confirm the link.
	 *
	 * Returns without redirecting when the form cannot be accepted, so the form shows again with an error.
	 *
	 * @param array{user_id: int, key: string} $link Confirmation link.
	 * @param \WP_User                         $user The link's account.
	 * @return void
	 */
	private function handle_confirm_form( array $link, \WP_User $user ): void {
		$nonce = isset( $_POST['woocommerce-confirm-email-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-confirm-email-nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::CONFIRM_NONCE_ACTION ) ) {
			return;
		}

		$rate_limit_id = 'confirm_email_' . $user->ID;

		if ( \WC_Rate_Limiter::retried_too_soon( $rate_limit_id ) ) {
			wc_add_notice( __( 'Please wait a few seconds before trying again.', 'woocommerce' ), 'error' );
			return;
		}

		// Count the attempt before checking it, so the delay also applies to the attempt that uses up the free ones.
		$attempts_id = 'wc_confirm_email_attempts_' . $user->ID;
		$attempts    = (int) get_transient( $attempts_id ) + 1;
		set_transient( $attempts_id, $attempts, self::CONFIRM_ATTEMPT_WINDOW );

		if ( $attempts >= self::CONFIRM_FREE_ATTEMPTS ) {
			\WC_Rate_Limiter::set_rate_limit( $rate_limit_id, self::CONFIRM_RETRY_DELAY );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Passwords are compared as entered.
		$password = isset( $_POST['wc_confirm_password'] ) && is_string( $_POST['wc_confirm_password'] ) ? $_POST['wc_confirm_password'] : '';

		if ( ! $this->check_credentials( $user, $password ) ) {
			wc_add_notice( __( 'The password you entered is incorrect. Please try again.', 'woocommerce' ), 'error' );
			return;
		}

		delete_transient( $attempts_id );

		if ( ! $this->process_verification( $link['user_id'], $link['key'] ) ) {
			$this->redirect_with_result( 'expired' );
		}

		// Log in last: verifying ends the account's other sessions, and this new one must survive. It also means
		// wp_signon() runs for the login itself, so two-factor plugins still apply.
		if ( ! is_user_logged_in() ) {
			wp_signon(
				array(
					'user_login'    => $user->user_login,
					'user_password' => $password,
				),
				is_ssl()
			);
		}

		wp_safe_redirect( add_query_arg( self::NOTICE_PARAM, 'confirmed', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Check the credentials posted with the confirmation form.
	 *
	 * Logged out customers go through wp_authenticate(), so that lockout plugins count their attempts.
	 *
	 * @param \WP_User $user     The link's account.
	 * @param string   $password Password as entered.
	 * @return bool
	 */
	private function check_credentials( \WP_User $user, string $password ): bool {
		if ( '' === $password ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			return wp_check_password( $password, $user->user_pass, $user->ID );
		}

		// Core also matches a login against email addresses, so check this is the link's account.
		$authenticated = wp_authenticate( $user->user_login, $password );

		return $authenticated instanceof \WP_User && $authenticated->ID === $user->ID;
	}

	/**
	 * Render the confirmation form in place of the My Account page, when this request has a form to show.
	 *
	 * @internal
	 *
	 * @return bool Whether the form was rendered.
	 */
	public function render_confirm_form(): bool {
		if ( null === $this->confirm_form_user ) {
			return false;
		}

		wc_print_notices();
		wc_get_template(
			'myaccount/form-confirm-email.php',
			array(
				'email'             => is_user_logged_in() ? $this->confirm_form_user->user_email : '',
				'lost_password_url' => wc_lostpassword_url(),
			)
		);

		return true;
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
	 * This cannot depend on whether matching guest orders exist, since that would disclose order
	 * existence before the customer proves they control the email address.
	 *
	 * @since 11.0.0
	 *
	 * @return bool
	 */
	public function should_show_prompt(): bool {
		$user_id = get_current_user_id();

		if ( ! $user_id || $this->service->is_verified( $user_id ) ) {
			return false;
		}

		$email_setting = get_option( 'woocommerce_customer_verify_email_settings', array() );
		$email_enabled = 'yes' === ( $email_setting['enabled'] ?? 'yes' );

		$should_show = $email_enabled && wc_string_to_bool( get_option( 'woocommerce_enable_guest_checkout' ) );

		// Ask temporary-password accounts to set their password first. Once they do, the
		// temporary-password notice clears and this separate email-confirmation prompt can appear.
		$should_show = $should_show && ! get_user_option( 'default_password_nag', $user_id );

		/**
		 * Filter whether to show the verification prompt for an unverified user.
		 *
		 * @since 11.1.0
		 *
		 * @param bool $should_show Whether to show the prompt, before this filter runs.
		 * @param int  $user_id     The WordPress user ID of the customer.
		 */
		return (bool) apply_filters( 'woocommerce_customer_email_verification_should_show_prompt', $should_show, $user_id );
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
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof \WP_User ) {
			return false;
		}
		$expected_email = $user->user_email;
		if ( ! $this->service->check_verification_key( $user_id, $key ) ) {
			return false;
		}
		$this->service->mark_verified( $user_id, $expected_email );
		return $this->service->is_verified( $user_id );
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
