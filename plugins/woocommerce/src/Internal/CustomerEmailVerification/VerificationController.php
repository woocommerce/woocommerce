<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Drives the customer email-verification UI on My Account and processes its requests.
 *
 * Verification uses a short-lived 6-digit code (OTP) the customer types into a form on a My Account
 * sub-page (/orders/verify/) — the same logged-in session that requested it. There is no
 * verification link, so an email client or security scanner that prefetches URLs cannot complete
 * verification.
 *
 * @since 11.0.0
 */
class VerificationController {

	/**
	 * Nonce action used to protect the send-code request.
	 */
	private const SEND_NONCE_ACTION = 'woocommerce-send-verification-email';

	/**
	 * Nonce action used to protect the code submission.
	 */
	private const VERIFY_NONCE_ACTION = 'woocommerce-verify-email';

	/**
	 * Query param used to trigger the send-code request.
	 */
	private const SEND_PARAM = 'wc_send_verification';

	/**
	 * Form field carrying the submitted code.
	 */
	private const CODE_FIELD = 'wc_verify_email_code';

	/**
	 * Hidden form field marking a code submission.
	 */
	private const SUBMIT_FIELD = 'wc_verify_email_submit';

	/**
	 * Minimum seconds between code sends (rate limit).
	 */
	private const SEND_RATE_LIMIT = 60;

	/**
	 * Reserved value of the orders endpoint that renders the code-entry sub-page (/orders/verify/).
	 * Reusing the orders endpoint's existing value segment avoids registering a new rewrite endpoint.
	 */
	private const VERIFY_VALUE = 'verify';

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
		add_action( 'woocommerce_before_account_orders', array( $this, 'render_prompt' ) );
		// Render the form on the /orders/verify/ sub-page (priority 1, before the default orders list).
		add_action( 'woocommerce_account_orders_endpoint', array( $this, 'maybe_render_on_orders_endpoint' ), 1 );
		add_filter( 'woocommerce_endpoint_orders_title', array( $this, 'maybe_filter_orders_title' ) );
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
	 * Route an incoming request: either a send-code request or a code submission.
	 *
	 * @since 11.0.0
	 */
	public function maybe_process_request(): void {
		if ( isset( $_GET[ self::SEND_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_send_request();
			return;
		}

		if ( $this->is_code_submission() ) {
			$this->handle_code_submission();
			return;
		}

		// On the /orders/verify/ sub-page, bounce anyone with nothing to verify back to orders. Done
		// here, before output, because the rendering callback can't safely redirect.
		if ( self::VERIFY_VALUE === get_query_var( 'orders' ) && ! $this->should_show_prompt() ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}
	}

	/**
	 * Whether the current request is a submission of the code form.
	 *
	 * @return bool
	 */
	private function is_code_submission(): bool {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

		// Nonce is verified in handle_code_submission(); this only routes the request.
		return 'POST' === $method && isset( $_POST[ self::SUBMIT_FIELD ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Verify the submitted code and record the outcome.
	 *
	 * @since 11.0.0
	 */
	private function handle_code_submission(): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::VERIFY_NONCE_ACTION ) ) {
			wc_add_notice( __( 'Invalid request. Please try again.', 'woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		// Keep digits only so spaces or stray characters in the pasted code don't cause a false mismatch.
		$code = preg_replace( '/\D/', '', isset( $_POST[ self::CODE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CODE_FIELD ] ) ) : '' );

		$result = $this->service->verify_code( $user_id, (string) $code );

		switch ( $result ) {
			case EmailVerificationService::RESULT_OK:
				wc_add_notice( __( 'Your email address has been confirmed.', 'woocommerce' ) );
				break;
			case EmailVerificationService::RESULT_WRONG:
				wc_add_notice( __( 'That code is incorrect. Please try again.', 'woocommerce' ), 'error' );
				break;
			case EmailVerificationService::RESULT_BURNED:
				wc_add_notice( __( 'That code is no longer valid. Please request a new one.', 'woocommerce' ), 'error' );
				break;
			case EmailVerificationService::RESULT_EXPIRED:
				wc_add_notice( __( 'That code has expired. Please request a new one.', 'woocommerce' ), 'error' );
				break;
			case EmailVerificationService::RESULT_LOCKED:
				wc_add_notice( $this->locked_message(), 'error' );
				break;
			default:
				// RESULT_NONE: no pending code. If a prior submission (e.g. a double click) already
				// verified the address, show success rather than a stale "request a new code" error.
				if ( $this->service->is_verified( $user_id ) ) {
					wc_add_notice( __( 'Your email address has been confirmed.', 'woocommerce' ) );
				} else {
					wc_add_notice( __( 'Please request a new code to confirm your email address.', 'woocommerce' ), 'error' );
				}
				break;
		}

		// Verified customers go to their orders; everyone else stays on the form to try again.
		$verified = EmailVerificationService::RESULT_OK === $result || $this->service->is_verified( $user_id );
		wp_safe_redirect( $verified ? wc_get_account_endpoint_url( 'orders' ) : $this->verify_url() );
		exit;
	}

	/**
	 * Handle a request to send (or resend) a verification code, triggered by the My Account prompt.
	 *
	 * Verifies the nonce, refuses to mint for a locked-out user, applies a resend rate-limit, then
	 * dispatches the code and redirects back to the orders panel (where the entry form is shown).
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

		// A locked-out customer can only be verified another way (e.g. by the store owner); never mint.
		if ( $this->service->is_locked_out( $user_id ) ) {
			wp_safe_redirect( $this->verify_url() );
			exit;
		}

		// Only send a fresh code once the last one is outside the rate-limit window; otherwise the
		// existing code still stands and the entry form continues to point the customer to their inbox.
		$seconds_since = $this->service->seconds_since_last_key( $user_id );
		if ( null === $seconds_since || $seconds_since >= self::SEND_RATE_LIMIT ) {
			$this->send_verification_email( $user_id );
			wc_add_notice( __( 'A code has been sent to your email address. Please check your inbox.', 'woocommerce' ), 'success' );
		} else {
			wc_add_notice( __( 'A code was sent recently. Please check your inbox, or wait a moment before requesting a new one.', 'woocommerce' ), 'notice' );
		}

		// Land on the /orders/verify/ sub-page, where the entry form is shown.
		wp_safe_redirect( $this->verify_url() );
		exit;
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
	 * The notice initiates the flow but does not host the form: a permanent-lockout message, a
	 * pointer to the /orders/verify/ sub-page when a code is already pending, or the "send code" call
	 * to action (which sends a code and redirects to the /orders/verify/ sub-page).
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function render_prompt(): void {
		if ( ! $this->should_show_prompt() ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $this->service->is_locked_out( $user_id ) ) {
			$html = $this->get_locked_html();
		} elseif ( $this->service->has_pending_code( $user_id ) ) {
			$html = $this->get_pending_notice_html();
		} else {
			$html = $this->get_send_cta_html();
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each helper escapes every interpolated value.
		echo $html;
	}

	/**
	 * Render the verification sub-page content: the code-entry form (or the lockout / send states).
	 *
	 * Reached from the orders prompt via {@see self::maybe_render_on_orders_endpoint()}. Access is
	 * gated in {@see self::maybe_process_request()}, which redirects anyone with nothing to verify
	 * back to orders before output.
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function render_endpoint_content(): void {
		if ( ! $this->should_show_prompt() ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $this->service->is_locked_out( $user_id ) ) {
			$html = $this->get_locked_html();
		} elseif ( $this->service->has_pending_code( $user_id ) ) {
			$this->enqueue_form_assets();
			$html = $this->get_code_form_html();
		} else {
			$html = $this->get_send_cta_html();
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each helper escapes every interpolated value.
		echo $html;
	}

	/**
	 * Render the verification UI on the /orders/verify/ sub-page.
	 *
	 * Hooked early on the orders endpoint. When the orders value is the reserved "verify" token, this
	 * renders the verification UI and removes the default orders-list output so only the form shows;
	 * for any other value (a page number, or none) it is a no-op and the orders list renders normally.
	 *
	 * @internal
	 * @param string $value The orders endpoint value (a page number, or the verify token).
	 * @return void
	 */
	public function maybe_render_on_orders_endpoint( $value ): void {
		if ( self::VERIFY_VALUE !== $value ) {
			return;
		}

		remove_action( 'woocommerce_account_orders_endpoint', 'woocommerce_account_orders' );
		$this->render_endpoint_content();
	}

	/**
	 * Force a clean "Orders" title on the /orders/verify/ sub-page.
	 *
	 * The sub-page reuses the orders endpoint's value slot, so WooCommerce would otherwise render the
	 * title as "Orders (Page 0)". Other orders pages keep their default title.
	 *
	 * @internal
	 * @param string $title Default orders endpoint title.
	 * @return string
	 */
	public function maybe_filter_orders_title( $title ): string {
		if ( self::VERIFY_VALUE === get_query_var( 'orders' ) ) {
			return __( 'Orders', 'woocommerce' );
		}
		return $title;
	}

	/**
	 * URL of the /orders/verify/ sub-page that hosts the code-entry form.
	 *
	 * Reuses the orders endpoint's existing value segment, so no new rewrite rule (and no flush) is
	 * needed for the URL to resolve.
	 *
	 * @return string
	 */
	private function verify_url(): string {
		return wc_get_endpoint_url( 'orders', self::VERIFY_VALUE, wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * Build the permanent-lockout message block.
	 *
	 * @return string Fully escaped HTML.
	 */
	private function get_locked_html(): string {
		return wc_print_notice( esc_html( $this->locked_message() ), 'error', array(), true );
	}

	/**
	 * Build the "send confirmation code" call-to-action block.
	 *
	 * @return string Fully escaped HTML.
	 */
	private function get_send_cta_html(): string {
		$send_url = wp_nonce_url(
			add_query_arg( self::SEND_PARAM, '1', wc_get_account_endpoint_url( 'orders' ) ),
			self::SEND_NONCE_ACTION
		);

		$notice = sprintf(
			'<a href="%2$s" class="button wc-forward">%3$s</a> %1$s',
			esc_html__( 'Confirm your email address to check for past orders and link them to your account.', 'woocommerce' ),
			esc_url( $send_url ),
			esc_html__( 'Send confirmation code', 'woocommerce' )
		);

		return wc_print_notice( $notice, 'notice', array(), true );
	}

	/**
	 * Build the orders-panel notice shown when a code is already pending: a pointer to the
	 * /orders/verify/ sub-page where the customer enters it.
	 *
	 * @return string Fully escaped HTML.
	 */
	private function get_pending_notice_html(): string {
		$notice = sprintf(
			'<a href="%2$s" class="button wc-forward">%3$s</a> %1$s',
			esc_html__( 'We emailed you a confirmation code to confirm your email address.', 'woocommerce' ),
			esc_url( $this->verify_url() ),
			esc_html__( 'Enter your code', 'woocommerce' )
		);

		return wc_print_notice( $notice, 'notice', array(), true );
	}

	/**
	 * Build the code-entry form block.
	 *
	 * @return string Fully escaped HTML.
	 */
	private function get_code_form_html(): string {
		$user       = wp_get_current_user();
		$resend_url = wp_nonce_url(
			add_query_arg( self::SEND_PARAM, '1', wc_get_account_endpoint_url( 'orders' ) ),
			self::SEND_NONCE_ACTION
		);

		$template = '
<form method="post" action="%1$s" class="woocommerce-verify-email-form">
<p>%2$s</p>
<div class="woocommerce-otp-input-wrapper">
<input type="text" name="%3$s" aria-label="%4$s" placeholder="······" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="input-text woocommerce-otp-input" />
<button type="submit" class="wp-element-button button">%5$s</button>
</div>
<p class="woocommerce-otp-resend">%6$s <a href="%7$s" class="wc-forward">%8$s</a></p>
<input type="hidden" name="%9$s" value="1" />
%10$s
</form>
';

		return sprintf(
			$template,
			esc_url( $this->verify_url() ),
			// translators: %s: the customer's email address.
			sprintf( esc_html__( 'Enter the 6-digit code that was sent to %s within 10 minutes to confirm your email address.', 'woocommerce' ), '<b>' . esc_html( $user->user_email ) . '</b>' ),
			esc_attr( self::CODE_FIELD ),
			esc_attr__( 'Verification code', 'woocommerce' ),
			esc_html__( 'Confirm email', 'woocommerce' ),
			esc_html__( 'Didn\'t receive the code?', 'woocommerce' ),
			esc_url( $resend_url ),
			esc_html__( 'Resend a new one', 'woocommerce' ),
			esc_attr( self::SUBMIT_FIELD ),
			wp_nonce_field( self::VERIFY_NONCE_ACTION, '_wpnonce', true, false )
		);
	}

	/**
	 * Enqueue the code-entry form's inline CSS and progressive-enhancement JS.
	 *
	 * Both attach to an empty-source handle and print only when the form renders, so neither ships
	 * site-wide and no build step is needed. The form is fully usable without the JS: native
	 * constraints (`required`, a 6-digit `pattern`) gate submission and `:invalid` styling dims the
	 * button until a complete code is entered. The JS only adds niceties — it trims pasted input,
	 * auto-submits a complete pasted code, and guards against a double submit.
	 *
	 * @return void
	 */
	private function enqueue_form_assets(): void {
		$handle = 'wc-customer-email-verification';

		// Empty-source handle carrying only the inline CSS/JS; re-registering is a no-op if it exists.
		wp_register_style( $handle, false, array(), \WC_VERSION );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $this->get_form_styles() );

		wp_register_script( $handle, false, array(), \WC_VERSION, true );
		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, $this->get_form_script() );
	}

	/**
	 * The inline CSS for the code-entry form.
	 *
	 * Printed only on the verification sub-page (via {@see self::enqueue_form_assets()}) rather than in
	 * the site-wide stylesheet. Selectors must stay in sync with {@see self::get_code_form_html()}. The
	 * submit button is dimmed purely via CSS: `:invalid` (the input is `required` with a 6-digit
	 * `pattern`) while the code is incomplete, and `.is-submitting` while the POST is in flight.
	 *
	 * @return string
	 */
	private function get_form_styles(): string {
		return <<<'CSS'
.woocommerce-verify-email-form {
	text-align: center;
	text-wrap: balance;
	border: 1px solid color-mix(in srgb, currentColor 20%, transparent);
	padding: 16px;
	margin: 0 0 16px;
	border-radius: 4px;
}
.woocommerce-verify-email-form .woocommerce-otp-input-wrapper {
	display: inline-flex;
	align-items: stretch;
	justify-content: center;
	flex-direction: column;
	gap: 8px;
	margin: 16px 0;
}
.woocommerce-verify-email-form .woocommerce-otp-input.input-text {
	font-size: 32px;
	font-weight: 700;
	letter-spacing: 0.4em;
	text-indent: 0.4em;
	padding: 0.2em 0.4em;
	font-variant-numeric: tabular-nums;
	text-align: center;
	line-height: 1;
	color: black;
	width: 6.8em;
	font-family: monospace;
	flex: 0;
}
.woocommerce-verify-email-form .woocommerce-otp-input-wrapper .button.wp-element-button {
	margin: 0;
}
.woocommerce-verify-email-form .woocommerce-otp-input:invalid + .button.wp-element-button,
.woocommerce-verify-email-form.is-submitting .button.wp-element-button {
	cursor: not-allowed;
	opacity: 0.5;
}
.woocommerce-verify-email-form .woocommerce-otp-resend {
	font-size: var(--wp--preset--font-size--x-small, calc(var(--wp--preset--font-size--small, 14px) * 0.875));
}
CSS;
	}

	/**
	 * The inline JavaScript that enhances the code-entry form.
	 *
	 * Pure progressive enhancement — the form works without it. Selectors must stay in sync with
	 * {@see self::get_code_form_html()}: the `.woocommerce-verify-email-form` form and its
	 * `wc_verify_email_code` input. The submit button's dimmed state is owned by CSS (`:invalid`), not
	 * here; this only trims input, auto-submits a complete pasted code, and blocks a double submit.
	 *
	 * @return string
	 */
	private function get_form_script(): string {
		return <<<'JS'
( function () {
	var form = document.querySelector( '.woocommerce-verify-email-form' );
	if ( ! form ) { return; }
	var input = form.querySelector( 'input[name="wc_verify_email_code"]' );
	if ( ! input ) { return; }

	input.focus();

	input.addEventListener( 'input', function ( event ) {
		// Trim whitespace and any non-digits (e.g. from a pasted code), capped at six digits.
		var digits = input.value.replace( /\D/g, '' ).slice( 0, 6 );
		if ( digits !== input.value ) { input.value = digits; }

		// Auto-submit only a complete code that was pasted or dropped in — never while typing, so a
		// mistyped digit can't submit by accident. requestSubmit() still runs native validation.
		var inserted = event && event.inputType;
		var pasted = 'insertFromPaste' === inserted || 'insertFromDrop' === inserted;
		if ( pasted && 6 === digits.length && form.requestSubmit ) {
			form.requestSubmit();
		}
	} );

	form.addEventListener( 'submit', function ( event ) {
		// Block a double submit; .is-submitting also dims the button while the POST is in flight.
		if ( form.classList.contains( 'is-submitting' ) ) {
			event.preventDefault();
			return;
		}
		form.classList.add( 'is-submitting' );
		input.readOnly = true;
	} );
}() );
JS;
	}

	/**
	 * The permanent-lockout message, shared by the rendered block and the submission notice.
	 *
	 * @return string
	 */
	private function locked_message(): string {
		return __( 'Too many incorrect attempts. Please contact the store owner to confirm your email address.', 'woocommerce' );
	}

	/**
	 * Send (or resend) a verification code to a user.
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

		$code = $this->service->create_code( $user_id );

		WC()->mailer();

		/**
		 * Triggers sending of the customer email-verification email.
		 *
		 * @param int    $user_id     The WordPress user ID of the customer.
		 * @param string $verify_code The one-time numeric code to include in the email.
		 *
		 * @since 11.0.0
		 */
		do_action( 'woocommerce_customer_verify_email_notification', $user_id, $code );
	}
}
