<?php
/**
 * My Account Shortcodes
 *
 * Shows the 'my account' section where the customer can view past orders and update their information.
 *
 * @package WooCommerce\Shortcodes\My_Account
 * @version 2.0.0
 */

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalController;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode my account class.
 */
class WC_Shortcode_My_Account {

	/**
	 * Signed password-reset form token version.
	 */
	private const PASSWORD_RESET_FORM_TOKEN_VERSION = 'wc1';

	/**
	 * Password-reset bridge lifetime in seconds.
	 */
	private const PASSWORD_RESET_BRIDGE_EXPIRATION = 10 * MINUTE_IN_SECONDS;

	/**
	 * Prefix for transient keys containing short-lived password-reset bridge state.
	 */
	private const PASSWORD_RESET_BRIDGE_TRANSIENT_PREFIX = 'wc_password_reset_bridge_';

	/**
	 * Character length of a password-reset bridge handle.
	 */
	private const PASSWORD_RESET_BRIDGE_HANDLE_LENGTH = 32;

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		global $wp;

		// Check cart class is loaded or abort.
		if ( is_null( WC()->cart ) ) {
			return;
		}

		self::my_account_add_notices();

		// Show the lost password page. This can still be accessed directly by logged in accounts which is important for the initial create password links sent via email.
		if ( isset( $wp->query_vars['lost-password'] ) ) {
			self::lost_password();
			return;
		}

		if ( wc_get_container()->get( OrderWithdrawalController::class )->is_endpoint_request() ) {
			// Order withdrawal is an EU regulation requirement which needs standalone access.
			wc_get_container()->get( OrderWithdrawalController::class )->render_view();
			return;
		}

		// Show login form if not logged in.
		if ( ! is_user_logged_in() ) {
			wc_get_template( 'myaccount/form-login.php' );
			return;
		}

		// Output the my account page.
		self::my_account( $atts );
	}

	/**
	 * Add notices to the my account page.
	 *
	 * Historically a filter has existed to render a message above the my account page content while the user is
	 * logged out. See `woocommerce_my_account_message`.
	 */
	private static function my_account_add_notices() {
		global $wp;

		if ( ! is_user_logged_in() ) {
			/**
			 * Filters the message shown on the 'my account' page when the user is not logged in.
			 *
			 * @since 2.6.0
			 */
			$message = apply_filters( 'woocommerce_my_account_message', '' );

			if ( ! empty( $message ) ) {
				wc_add_notice( $message );
			}
		}

		// After password reset, add confirmation message.
		if ( ! empty( $_GET['password-reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wc_add_notice( __( 'Your password has been reset successfully.', 'woocommerce' ) );
		}

		// After logging out without a nonce, add confirmation message.
		if ( isset( $wp->query_vars['customer-logout'] ) && is_user_logged_in() ) {
			/* translators: %s: logout url */
			wc_add_notice( sprintf( __( 'Are you sure you want to log out? <a href="%s">Confirm and log out</a>', 'woocommerce' ), wc_logout_url() ) );
		}

		// Suppress the nag during the resend cooldown so it doesn't contradict the "we emailed you" confirmation.
		// Driven off the same timestamp WC_Form_Handler::resend_set_password() writes, so the notice reappears
		// once the cooldown lapses and the link can be requested again.
		$last_resend_at  = (int) get_user_meta( get_current_user_id(), WC_Form_Handler::SET_PASSWORD_RESEND_META, true );
		$within_cooldown = $last_resend_at > 0 && ( time() - $last_resend_at ) < WC_Form_Handler::SET_PASSWORD_RESEND_RATE_LIMIT_SECONDS;

		if ( ! $within_cooldown && get_user_option( 'default_password_nag' ) && ( wc_is_current_account_menu_item( 'dashboard' ) || wc_is_current_account_menu_item( 'edit-account' ) ) ) {
			$resend_url = wp_nonce_url( add_query_arg( 'wc-resend-set-password', '1', wc_get_page_permalink( 'myaccount' ) ), 'wc-resend-set-password' );
			wc_add_notice(
				sprintf(
					/* translators: %1$s and %2$s are opening and closing anchor tags for the resend-link button. */
					__( '%1$sResend%2$s', 'woocommerce' ),
					'<a href="' . esc_url( $resend_url ) . '" class="button wc-forward">',
					'</a>'
				) . ' ' . __( 'Your account is using a temporary password. We emailed you a link to change your password.', 'woocommerce' ),
				'notice'
			);
		}
	}

	/**
	 * My account page.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	private static function my_account( $atts ) {
		$args = shortcode_atts(
			array(
				'order_count' => 15, // @deprecated 2.6.0. Keep for backward compatibility.
			),
			$atts,
			'woocommerce_my_account'
		);

		wc_get_template(
			'myaccount/my-account.php',
			array(
				'current_user' => get_user_by( 'id', get_current_user_id() ),
				'order_count'  => 'all' === $args['order_count'] ? -1 : $args['order_count'],
			)
		);
	}

	/**
	 * View order page.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function view_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! current_user_can( 'view_order', $order_id ) ) {
			wc_print_notice(
				esc_html__( 'Invalid order.', 'woocommerce' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'woocommerce' ) . '</a>',
				'error'
			);
			return;
		}

		// Backwards compatibility.
		$status       = new stdClass();
		$status->name = wc_get_order_status_name( $order->get_status() );

		wc_get_template(
			'myaccount/view-order.php',
			array(
				'status'   => $status, // @deprecated 2.2.
				'order'    => $order,
				'order_id' => $order->get_id(),
			)
		);
	}

	/**
	 * Edit account details page.
	 */
	public static function edit_account() {
		wc_get_template( 'myaccount/form-edit-account.php', array( 'user' => get_user_by( 'id', get_current_user_id() ) ) );
	}

	/**
	 * Edit address page.
	 *
	 * @param string $load_address Type of address; 'billing' or 'shipping'.
	 */
	public static function edit_address( $load_address = 'billing' ) {
		$current_user = wp_get_current_user();
		$load_address = sanitize_key( $load_address );
		$country      = get_user_meta( get_current_user_id(), $load_address . '_country', true );

		if ( ! $country ) {
			$country = WC()->countries->get_base_country();
		}

		if ( 'billing' === $load_address ) {
			$allowed_countries = WC()->countries->get_allowed_countries();

			if ( ! array_key_exists( $country, $allowed_countries ) ) {
				$country = current( array_keys( $allowed_countries ) );
			}
		}

		if ( 'shipping' === $load_address ) {
			$allowed_countries = WC()->countries->get_shipping_countries();

			if ( ! array_key_exists( $country, $allowed_countries ) ) {
				$country = current( array_keys( $allowed_countries ) );
			}
		}

		$address = WC()->countries->get_address_fields( $country, $load_address . '_' );

		// Enqueue scripts.
		wp_enqueue_script( 'wc-country-select' );
		wp_enqueue_script( 'wc-address-i18n' );

		// Prepare values.
		foreach ( $address as $key => $field ) {

			$value = get_user_meta( get_current_user_id(), $key, true );

			if ( ! $value ) {
				switch ( $key ) {
					case 'billing_email':
					case 'shipping_email':
						$value = $current_user->user_email;
						break;
				}
			}

			$address[ $key ]['value'] = apply_filters( 'woocommerce_my_account_edit_address_field_value', $value, $key, $load_address );
		}

		wc_get_template(
			'myaccount/form-edit-address.php',
			array(
				'load_address' => $load_address,
				'address'      => apply_filters( 'woocommerce_address_to_edit', $address, $load_address ),
			)
		);
	}

	/**
	 * Lost password page handling.
	 */
	public static function lost_password() {
		/**
		 * After sending the reset link, don't show the form again.
		 */
		if ( ! empty( $_GET['reset-link-sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI selector; login and path are normalized downstream.
			wc_get_template( 'myaccount/lost-password-confirmation.php' );
			return;

			/**
			 * Process reset key / login from email confirmation link
			 */
		} elseif ( ! empty( $_GET['show-reset-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI selector; login and path are normalized downstream.

			/*
			 * Three sources can carry reset credentials onto this page, in this order of precedence:
			 * WordPress's own wp-resetpass-* cookie, a single-use bridge handle in the URL (used when
			 * the cookie is SameSite=Strict and so does not survive the click from the email client),
			 * and the signed token re-posted by the form when password validation rejects a submission.
			 */
			$reset_cookie      = isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ? wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) : ''; // @codingStandardsIgnoreLine
			$has_reset_cookie  = is_string( $reset_cookie ) && 0 < strpos( $reset_cookie, ':' );
			$bridge_handle     = isset( $_GET['reset-token'] ) ? wc_clean( wp_unslash( $_GET['reset-token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$has_bridge_handle = is_string( $bridge_handle ) && '' !== $bridge_handle;
			$form_args         = false;

			if ( $has_reset_cookie ) {
				list( $rp_id, $rp_key ) = array_map( 'wc_clean', explode( ':', $reset_cookie, 2 ) );
				$userdata               = get_userdata( absint( $rp_id ) );
				$rp_login               = $userdata ? $userdata->user_login : '';

				// Reset key / login is correct, display reset password form with hidden key / login values.
				if ( is_string( $rp_key ) && check_password_reset_key( $rp_key, $rp_login ) instanceof WP_User ) {
					$form_args = array(
						'key'   => $rp_key,
						'login' => $rp_login,
					);
				}
			}

			if ( $form_args ) {
				// The cookie won, but a handle is single-use either way so it cannot be replayed.
				self::discard_password_reset_bridge_token( $bridge_handle );
			} else {
				$form_args = self::consume_password_reset_bridge_token( $bridge_handle );

				if ( ! $form_args ) {
					$form_args = self::get_posted_password_reset_bridge_credentials();
				}
			}

			if ( $form_args ) {
				wc_get_template( 'myaccount/form-reset-password.php', $form_args );
				return;
			}

			if ( $has_reset_cookie || $has_bridge_handle ) {
				self::add_password_reset_key_error_notice();
			}
		}

		// Show lost password form by default.
		wc_get_template(
			'myaccount/form-lost-password.php',
			array(
				'form' => 'lost_password',
			)
		);
	}

	/**
	 * Handles sending password retrieval email to customer.
	 *
	 * Based on retrieve_password() in core wp-login.php.
	 *
	 * @uses $wpdb WordPress Database object
	 * @return bool True: when finish. False: on error
	 */
	public static function retrieve_password() {
		$login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only UI selector; login and path are normalized downstream.

		if ( empty( $login ) ) {

			wc_add_notice( __( 'Enter a username or email address.', 'woocommerce' ), 'error' );

			return false;

		} else {
			// Check on username first, as customers can use emails as usernames.
			$user_data = get_user_by( 'login', $login );
		}

		// If no user found, check if it login is email and lookup user based on email.
		if ( ! $user_data && is_email( $login ) && apply_filters( 'woocommerce_get_username_from_email', true ) ) {
			$user_data = get_user_by( 'email', $login );
		}

		$errors = new WP_Error();

		do_action( 'lostpassword_post', $errors, $user_data );

		if ( $errors->get_error_code() ) {
			wc_add_notice( $errors->get_error_message(), 'error' );

			return false;
		}

		if ( ! $user_data ) {
			wc_add_notice( __( 'Invalid username or email.', 'woocommerce' ), 'error' );

			return false;
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {

			wc_add_notice( __( 'Password reset is not allowed for this user', 'woocommerce' ), 'error' );

			return false;

		} elseif ( is_wp_error( $allow ) ) {

			wc_add_notice( $allow->get_error_message(), 'error' );

			return false;
		}

		// Get password reset key (function introduced in WordPress 4.4).
		$key = get_password_reset_key( $user_data );

		// Send email notification.
		WC()->mailer(); // Load email classes.
		do_action( 'woocommerce_reset_password_notification', $user_login, $key );

		return true;
	}

	/**
	 * Retrieves a user row based on password reset key and login.
	 *
	 * Since 11.2.0 the key may also be a signed WooCommerce reset-form token, which is what the
	 * reset form carries when the WordPress reset cookie did not survive the click from the email
	 * client. Both formats are accepted so that anything reading the key handed to
	 * `myaccount/form-reset-password.php` keeps validating through this method.
	 *
	 * @uses $wpdb WordPress Database object.
	 * @param string $key   WordPress password reset key, or a signed WooCommerce reset-form token.
	 * @param string $login The user login.
	 * @return WP_User|bool User's database row on success, false for invalid keys
	 */
	public static function check_password_reset_key( $key, $login ) {
		// Check for the password reset key.
		// Get user data or an error message in case of invalid or expired key.
		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			$user = self::get_password_reset_bridge_user( $key, $login );

			if ( ! $user ) {
				self::add_password_reset_key_error_notice();
				return false;
			}
		}

		return $user;
	}

	/**
	 * Create a short-lived random handle that bridges a valid reset link across a redirect.
	 *
	 * Only a keyed digest of the handle and a verifier for WordPress's current hashed reset
	 * state are stored. The handle is consumed when it is exchanged for a separate signed
	 * form token, so the rendered page URL is not a reusable password-reset credential.
	 *
	 * @since 11.2.0
	 * @internal
	 *
	 * @param WP_User|WP_Error|null $user User with an active WordPress password-reset key. Anything
	 *                                    else yields an empty handle, so callers can pass the raw
	 *                                    result of check_password_reset_key().
	 * @return string Random bridge handle, or an empty string when no reset state exists.
	 */
	public static function create_password_reset_bridge_token( $user ) {
		if ( ! $user instanceof WP_User || empty( $user->user_activation_key ) ) {
			return '';
		}

		$now        = time();
		$expiration = min( $now + self::PASSWORD_RESET_BRIDGE_EXPIRATION, self::get_password_reset_state_expiration( $user ) );
		if ( $expiration <= $now ) {
			return '';
		}

		$payload = array(
			'user_id'         => $user->ID,
			'expiration'      => $expiration,
			'state_signature' => self::get_password_reset_state_signature( $user ),
		);

		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$handle         = wp_generate_password( self::PASSWORD_RESET_BRIDGE_HANDLE_LENGTH, false, false );
			$transient_name = self::get_password_reset_bridge_transient_name( $handle );

			if ( false !== get_transient( $transient_name ) ) {
				continue;
			}

			if ( set_transient( $transient_name, $payload, $expiration - $now ) ) {
				return $handle;
			}
		}

		return '';
	}

	/**
	 * Check whether a value has the shape of a password-reset bridge handle.
	 *
	 * @since 11.2.0
	 * @internal
	 *
	 * @param mixed $handle Candidate handle.
	 * @return bool Whether the value is a well-formed handle.
	 */
	public static function is_password_reset_bridge_handle( $handle ) {
		return is_string( $handle )
			&& 1 === preg_match( '/^[A-Za-z0-9]{' . self::PASSWORD_RESET_BRIDGE_HANDLE_LENGTH . '}$/D', $handle );
	}

	/**
	 * Delete a bridge handle without exchanging it, so it cannot be replayed.
	 *
	 * @param mixed $handle URL bridge handle.
	 */
	private static function discard_password_reset_bridge_token( $handle ): void {
		if ( self::is_password_reset_bridge_handle( $handle ) ) {
			delete_transient( self::get_password_reset_bridge_transient_name( $handle ) );
		}
	}

	/**
	 * Consume a URL bridge handle and exchange it for signed form credentials.
	 *
	 * @param mixed $handle URL bridge handle.
	 * @return array{key: string, login: string}|false Form credentials on success, false otherwise.
	 */
	private static function consume_password_reset_bridge_token( $handle ) {
		if ( ! self::is_password_reset_bridge_handle( $handle ) ) {
			return false;
		}

		$transient_name = self::get_password_reset_bridge_transient_name( $handle );
		$payload        = get_transient( $transient_name );
		delete_transient( $transient_name );

		if (
			! is_array( $payload ) ||
			! isset( $payload['user_id'], $payload['expiration'], $payload['state_signature'] ) ||
			! is_string( $payload['state_signature'] )
		) {
			return false;
		}

		$expiration = absint( $payload['expiration'] );
		$user       = self::get_eligible_password_reset_user( absint( $payload['user_id'] ), $expiration );

		if ( ! $user ) {
			return false;
		}

		if ( ! hash_equals( self::get_password_reset_state_signature( $user ), $payload['state_signature'] ) ) {
			return false;
		}

		return array(
			'key'   => self::create_password_reset_form_token( $user, $expiration ),
			'login' => $user->user_login,
		);
	}

	/**
	 * Resolve the user a bridge claim refers to, while the reset state and the viewer still allow it.
	 *
	 * @param int    $user_id    Claimed user ID.
	 * @param int    $expiration Claimed Unix expiry timestamp.
	 * @param string $login      Optional login the claim must match.
	 * @return WP_User|false User on success, false otherwise.
	 */
	private static function get_eligible_password_reset_user( $user_id, $expiration, $login = '' ) {
		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_activation_key ) || ( $login && $login !== $user->user_login ) ) {
			return false;
		}

		$now = time();
		if ( $expiration <= $now || self::get_password_reset_state_expiration( $user ) <= $now ) {
			return false;
		}

		$logged_in_user_id = get_current_user_id();
		if ( $logged_in_user_id && $logged_in_user_id !== $user_id ) {
			return false;
		}

		return $user;
	}

	/**
	 * Recover validated form credentials after password validation leaves the POST in place.
	 *
	 * @return array{key: string, login: string}|false Form credentials on success, false otherwise.
	 */
	private static function get_posted_password_reset_bridge_credentials() {
		$nonce_value = wc_get_var( $_POST['woocommerce-reset-password-nonce'], wc_get_var( $_POST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'reset_password' ) || ! isset( $_POST['reset_key'], $_POST['reset_login'] ) ) {
			return false;
		}

		$token = wc_clean( wp_unslash( $_POST['reset_key'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$login = sanitize_user( wp_unslash( $_POST['reset_login'] ) );
		$user  = is_string( $token ) ? self::get_password_reset_bridge_user( $token, $login ) : false;

		if ( ! $user ) {
			return false;
		}

		return array(
			'key'   => $token,
			'login' => $user->user_login,
		);
	}

	/**
	 * Create the signed token submitted by the reset-password form.
	 *
	 * @param WP_User $user       Token owner.
	 * @param int     $expiration Unix expiry timestamp.
	 * @return string Signed form token.
	 */
	private static function create_password_reset_form_token( $user, $expiration ) {
		$nonce     = wp_generate_password( 32, false, false );
		$signature = self::get_password_reset_bridge_signature( $user, $expiration, $nonce );

		return implode( '.', array( self::PASSWORD_RESET_FORM_TOKEN_VERSION, $user->ID, $expiration, $nonce, $signature ) );
	}

	/**
	 * Resolve and validate a signed password-reset bridge token.
	 *
	 * @param string $token Signed bridge token.
	 * @param string $login Optional login to bind during form submission.
	 * @return WP_User|false User on success, false otherwise.
	 */
	private static function get_password_reset_bridge_user( $token, $login = '' ) {
		$pattern = '/^' . self::PASSWORD_RESET_FORM_TOKEN_VERSION . '\.([1-9][0-9]*)\.([0-9]+)\.([A-Za-z0-9]{32})\.([a-f0-9]{64})$/D';
		if ( ! is_string( $token ) || ! preg_match( $pattern, $token, $matches ) ) {
			return false;
		}

		$user_id    = absint( $matches[1] );
		$expiration = absint( $matches[2] );
		$nonce      = $matches[3];
		$signature  = $matches[4];

		$user = self::get_eligible_password_reset_user( $user_id, $expiration, $login );
		if ( ! $user ) {
			return false;
		}

		if ( ! hash_equals( self::get_password_reset_bridge_signature( $user, $expiration, $nonce ), $signature ) ) {
			return false;
		}

		return $user;
	}

	/**
	 * Sign bridge claims together with WordPress's current hashed reset state.
	 *
	 * @param WP_User $user       Token owner.
	 * @param int     $expiration Unix expiry timestamp.
	 * @param string  $nonce      Random token nonce.
	 * @return string HMAC signature.
	 */
	private static function get_password_reset_bridge_signature( $user, $expiration, $nonce ) {
		return self::sign_password_reset_claims( array( self::PASSWORD_RESET_FORM_TOKEN_VERSION, $user->ID, $expiration, $nonce, $user->user_activation_key ) );
	}

	/**
	 * Sign password-reset bridge claims with the site's nonce salt.
	 *
	 * @param array<int, int|string> $claims Claims to sign.
	 * @return string HMAC signature.
	 */
	private static function sign_password_reset_claims( $claims ) {
		return hash_hmac( 'sha256', implode( '|', $claims ), wp_salt( 'nonce' ) );
	}

	/**
	 * Get the transient name for a bridge handle without storing the raw handle.
	 *
	 * @param string $handle URL bridge handle.
	 * @return string Transient name.
	 */
	private static function get_password_reset_bridge_transient_name( $handle ) {
		return self::PASSWORD_RESET_BRIDGE_TRANSIENT_PREFIX . self::sign_password_reset_claims( array( $handle ) );
	}

	/**
	 * Sign the reset state persisted in the short-lived bridge payload.
	 *
	 * @param WP_User $user Token owner.
	 * @return string Reset-state signature.
	 */
	private static function get_password_reset_state_signature( $user ) {
		return self::sign_password_reset_claims( array( $user->ID, $user->user_activation_key ) );
	}

	/**
	 * Get the current WordPress expiration for a user's hashed reset state.
	 *
	 * Old-style activation keys do not carry a request timestamp. WordPress may still
	 * accept those through the password_reset_key_expired filter, so the bridge's own
	 * short expiry remains the limit for that compatibility path.
	 *
	 * @param WP_User $user Token owner.
	 * @return int Unix expiry timestamp.
	 */
	private static function get_password_reset_state_expiration( $user ) {
		if ( false === strpos( $user->user_activation_key, ':' ) ) {
			return PHP_INT_MAX;
		}

		list( $request_time ) = explode( ':', $user->user_activation_key, 2 );
		if ( ! ctype_digit( $request_time ) ) {
			return 0;
		}

		// This filter is documented in WordPress core.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$expiration_duration = (int) apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );

		return (int) $request_time + max( 0, $expiration_duration );
	}

	/**
	 * Add the standard invalid password-reset key notice.
	 */
	private static function add_password_reset_key_error_notice(): void {
		wc_add_notice( __( 'This key is invalid or has already been used. Please reset your password again if needed.', 'woocommerce' ), 'error' );
	}

	/**
	 * Handles resetting the user's password.
	 *
	 * @since 9.4.0 This will log the user in after resetting the password/session.
	 *
	 * @param WP_User $user     The user.
	 * @param string  $new_pass New password for the user in plaintext.
	 */
	public static function reset_password( $user, $new_pass ) {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'password_reset', $user, $new_pass );

		wp_set_password( $new_pass, $user->ID );
		update_user_meta( $user->ID, 'default_password_nag', false );
		// The temporary-password notice is gone for good now, so drop its resend rate-limit timestamp.
		delete_user_meta( $user->ID, WC_Form_Handler::SET_PASSWORD_RESEND_META );

		// WordPress core hooks wp_password_change_notification() onto after_password_reset. Detach it around
		// the action so it doesn't duplicate the notification WooCommerce sends directly below (guarded by the
		// woocommerce_disable_password_change_notification filter), then restore it to its original priority.
		$core_notification_priority = has_action( 'after_password_reset', 'wp_password_change_notification' );
		if ( false !== $core_notification_priority ) {
			remove_action( 'after_password_reset', 'wp_password_change_notification', $core_notification_priority );
		}

		try {
			/**
			 * Fires after the user's password has been reset via WooCommerce.
			 *
			 * This provides parity with WordPress core's reset_password() function.
			 *
			 * @since 10.9.0
			 * @param WP_User $user     The user.
			 * @param string  $new_pass New user password in plaintext.
			 */
			do_action( 'after_password_reset', $user, $new_pass );
		} finally {
			if ( false !== $core_notification_priority ) {
				add_action( 'after_password_reset', 'wp_password_change_notification', $core_notification_priority );
			}
		}

		self::set_reset_password_cookie();
		wc_set_customer_auth_cookie( $user->ID );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		if ( ! apply_filters( 'woocommerce_disable_password_change_notification', false ) ) {
			wp_password_change_notification( $user );
		}
	}

	/**
	 * Set or unset the cookie.
	 *
	 * @param string $value Cookie value.
	 */
	public static function set_reset_password_cookie( $value = '' ) {
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		$rp_path   = isset( $_SERVER['REQUEST_URI'] ) ? current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only UI selector; login and path are normalized downstream.

		if ( $value ) {
			setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		} else {
			setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Show the add payment method page.
	 */
	public static function add_payment_method() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit();
		} else {
			do_action( 'before_woocommerce_add_payment_method' );

			wc_get_template( 'myaccount/form-add-payment-method.php' );

			do_action( 'after_woocommerce_add_payment_method' );
		}
	}
}
