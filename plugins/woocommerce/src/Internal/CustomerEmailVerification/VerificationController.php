<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

/**
 * Drives the customer email-verification UI on My Account and processes its requests.
 *
 * Verification uses a short-lived 6-digit code (OTP) the customer types into a form on the My
 * Account "Orders" panel — the same logged-in session that requested it. There is no verification
 * link, so an email client or security scanner that prefetches URLs cannot complete verification.
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
		add_action( 'woocommerce_before_account_orders', array( $this, 'render_prompt' ) );
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

		switch ( $this->service->verify_code( $user_id, (string) $code ) ) {
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

		wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
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
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		// Only send a fresh code once the last one is outside the rate-limit window; otherwise the
		// existing code still stands and the entry form continues to point the customer to their inbox.
		$seconds_since = $this->service->seconds_since_last_key( $user_id );
		if ( null === $seconds_since || $seconds_since >= self::SEND_RATE_LIMIT ) {
			$this->send_verification_email( $user_id );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
		exit;
	}

	/**
	 * Return whether the verification prompt should be shown for the current user.
	 *
	 * True only for a logged-in customer who is not yet verified, does not have a temporary
	 * password (those confirm via their set-password link), and has at least one guest order that
	 * could be linked (existence only; no details exposed).
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
	 * Render the verification prompt on the My Account "Orders" panel.
	 *
	 * Shows one of three states, derived from the user's stored verification state so the right
	 * thing appears after a reload, a second tab, or the back button: a permanent-lockout message,
	 * the code-entry form (when a code is pending), or the "send code" call to action.
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
			$this->enqueue_form_script();
			$html = $this->get_code_form_html();
		} else {
			$html = $this->get_send_cta_html();
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each helper escapes every interpolated value.
		echo $html;
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
			esc_html__( 'Confirm your email address to view past orders.', 'woocommerce' ),
			esc_url( $send_url ),
			esc_html__( 'Send confirmation code', 'woocommerce' )
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

		// The submit marker is a hidden field, not the button's name/value, so the form stays routable
		// even when JavaScript disables the button while submitting.
		$template = '
<form method="post" action="%1$s" class="woocommerce-verify-email-form">
<p>%2$s <a href="%3$s" class="wc-forward">%4$s</a></p>
<div class="woocommerce-otp-input-wrapper">
<input type="text" name="%5$s" placeholder="%6$s" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" class="input-text woocommerce-otp-input" aria-label="%7$s" />
<button type="submit" class="wp-element-button button">%8$s</button>
</div>
<input type="hidden" name="%9$s" value="1" />
%10$s
</form>
';

		return sprintf(
			$template,
			esc_url( wc_get_account_endpoint_url( 'orders' ) ),
			// translators: %s: the customer's email address.
			sprintf( esc_html__( 'Enter the 6-digit code that was sent to %s within 10 minutes to confirm your email address.', 'woocommerce' ), esc_html( $user->user_email ) ),
			esc_url( $resend_url ),
			esc_html__( 'Resend code', 'woocommerce' ),
			esc_attr( self::CODE_FIELD ),
			esc_attr__( '······', 'woocommerce' ),
			esc_attr__( 'Verification code', 'woocommerce' ),
			esc_html__( 'Confirm', 'woocommerce' ),
			esc_attr( self::SUBMIT_FIELD ),
			wp_nonce_field( self::VERIFY_NONCE_ACTION, '_wpnonce', true, false )
		);
	}

	/**
	 * Enqueue the progressive-enhancement script for the code-entry form.
	 *
	 * Loaded only when the form is rendered. It is a small inline footer script attached to a
	 * script-less handle, so it needs no separate build step. The form works without it (the button
	 * stays enabled and submits normally); the script just trims pasted input, auto-submits a complete
	 * code that was pasted in, disables the empty-field submit, and shows a loading state while submitting.
	 *
	 * @return void
	 */
	private function enqueue_form_script(): void {
		$handle = 'wc-customer-email-verification';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script( $handle, false, array(), \WC_VERSION, true );
		}

		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, $this->get_form_script() );
	}

	/**
	 * The inline JavaScript that enhances the code-entry form.
	 *
	 * Selectors must stay in sync with {@see self::get_code_form_html()}: the
	 * `.woocommerce-verify-email-form` form and its `wc_verify_email_code` input.
	 *
	 * @return string
	 */
	private function get_form_script(): string {
		return <<<'JS'
( function () {
	var form = document.querySelector( '.woocommerce-verify-email-form' );
	if ( ! form ) { return; }
	var input = form.querySelector( 'input[name="wc_verify_email_code"]' );
	var button = form.querySelector( 'button[type="submit"]' );
	if ( ! input || ! button ) { return; }

	function setSubmitting() {
		if ( form.classList.contains( 'is-submitting' ) ) { return false; }
		form.classList.add( 'is-submitting' );
		input.readOnly = true;
		button.disabled = true;
		return true;
	}

	// Disable the submit button while the field is empty. Applied in JS so visitors without
	// JavaScript keep a working button.
	button.disabled = '' === input.value;

	input.addEventListener( 'input', function ( event ) {
		// Trim whitespace and any non-digits (e.g. from a pasted code), capped at six digits.
		var digits = input.value.replace( /\D/g, '' ).slice( 0, 6 );
		if ( digits !== input.value ) { input.value = digits; }
		button.disabled = '' === digits || form.classList.contains( 'is-submitting' );

		// Auto-submit only a complete code that was pasted or dropped in — never while typing, so a
		// mistyped digit can't submit the form by accident.
		var inserted = event && event.inputType;
		var pasted = 'insertFromPaste' === inserted || 'insertFromDrop' === inserted;
		if ( pasted && 6 === digits.length && setSubmitting() ) {
			if ( form.requestSubmit ) { form.requestSubmit(); } else { form.submit(); }
		}
	} );

	form.addEventListener( 'submit', function () {
		setSubmitting();
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
