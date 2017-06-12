<?php
/**
 * My Account Shortcodes
 *
 * Shows the 'my account' section where the customer can view past orders and update their information.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/My_Account
 * @version     2.0.0
 */
class WC_Shortcode_My_Account {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
		global $wp;

		// Check cart class is loaded or abort
		if ( is_null( WC()->cart ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			$message = apply_filters( 'woocommerce_my_account_message', '' );

			if ( ! empty( $message ) ) {
				wc_add_notice( $message );
			}

			// After password reset, add confirmation message.
			if ( ! empty( $_GET['password-reset'] ) ) {
				wc_add_notice( __( 'Your password has been reset successfully.', 'woocommerce' ) );
			}

			if ( isset( $wp->query_vars['lost-password'] ) ) {
				self::lost_password();
			} else {
				wc_get_template( 'myaccount/form-login.php' );
			}
		} else {
			// Start output buffer since the html may need discarding for BW compatibility
			ob_start();

			if ( isset( $wp->query_vars['customer-logout'] ) ) {
				wc_add_notice( sprintf( __( 'Are you sure you want to log out? <a href="%s">Confirm and log out</a>', 'woocommerce' ), wc_logout_url() ) );
			}

			// Collect notices before output
			$notices = wc_get_notices();

			// Output the new account page
			self::my_account( $atts );

			/**
			 * Deprecated my-account.php template handling. This code should be
			 * removed in a future release.
			 *
			 * If woocommerce_account_content did not run, this is an old template
			 * so we need to render the endpoint content again.
			 */
			if ( ! did_action( 'woocommerce_account_content' ) ) {
				foreach ( $wp->query_vars as $key => $value ) {
					if ( 'pagename' === $key ) {
						continue;
					}
					if ( has_action( 'woocommerce_account_' . $key . '_endpoint' ) ) {
						ob_clean(); // Clear previous buffer
						wc_set_notices( $notices );
						wc_print_notices();
						do_action( 'woocommerce_account_' . $key . '_endpoint', $value );
						break;
					}
	 			}

				wc_deprecated_function( 'Your theme version of my-account.php template', '2.6', 'the latest version, which supports multiple account pages and navigation, from WC 2.6.0' );
			}

			// Send output buffer
			ob_end_flush();
		}
	}

	/**
	 * My account page.
	 *
	 * @param array $atts
	 */
	private static function my_account( $atts ) {
		extract( shortcode_atts( array(
			'order_count' => 15, // @deprecated 2.6.0. Keep for backward compatibility.
		), $atts, 'woocommerce_my_account' ) );

		wc_get_template( 'myaccount/my-account.php', array(
			'current_user' => get_user_by( 'id', get_current_user_id() ),
			'order_count'  => 'all' == $order_count ? -1 : $order_count,
		) );
	}

	/**
	 * View order page.
	 *
	 * @param int $order_id
	 */
	public static function view_order( $order_id ) {
		$order   = wc_get_order( $order_id );

		if ( ! current_user_can( 'view_order', $order_id ) ) {
			echo '<div class="woocommerce-error">' . __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . wc_get_page_permalink( 'myaccount' ) . '" class="wc-forward">' . __( 'My account', 'woocommerce' ) . '</a>' . '</div>';
			return;
		}

		// Backwards compatibility
		$status       = new stdClass();
		$status->name = wc_get_order_status_name( $order->get_status() );

		wc_get_template( 'myaccount/view-order.php', array(
			'status'    => $status, // @deprecated 2.2
			'order'     => wc_get_order( $order_id ),
			'order_id'  => $order_id,
		) );
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
	 * @param string $load_address
	 */
	public static function edit_address( $load_address = 'billing' ) {
		$current_user = wp_get_current_user();
		$load_address = sanitize_key( $load_address );

		$address = WC()->countries->get_address_fields( get_user_meta( get_current_user_id(), $load_address . '_country', true ), $load_address . '_' );

		// Enqueue scripts
		wp_enqueue_script( 'wc-country-select' );
		wp_enqueue_script( 'wc-address-i18n' );

		// Prepare values
		foreach ( $address as $key => $field ) {

			$value = get_user_meta( get_current_user_id(), $key, true );

			if ( ! $value ) {
				switch ( $key ) {
					case 'billing_email' :
					case 'shipping_email' :
						$value = $current_user->user_email;
					break;
					case 'billing_country' :
					case 'shipping_country' :
						$value = WC()->countries->get_base_country();
					break;
					case 'billing_state' :
					case 'shipping_state' :
						$value = WC()->countries->get_base_state();
					break;
				}
			}

			$address[ $key ]['value'] = apply_filters( 'woocommerce_my_account_edit_address_field_value', $value, $key, $load_address );
		}

		wc_get_template( 'myaccount/form-edit-address.php', array(
			'load_address' 	=> $load_address,
			'address'		=> apply_filters( 'woocommerce_address_to_edit', $address, $load_address ),
		) );
	}

	/**
	 * Lost password page handling.
	 */
	public static function lost_password() {
		/**
		 * After sending the reset link, don't show the form again.
		 */
		if ( ! empty( $_GET['reset-link-sent'] ) ) {
			return wc_get_template( 'myaccount/lost-password-confirmation.php' );

		/**
		 * Process reset key / login from email confirmation link
		 */
		} elseif ( ! empty( $_GET['show-reset-form'] ) ) {
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) && 0 < strpos( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ], ':' ) ) {
				list( $rp_login, $rp_key ) = array_map( 'wc_clean', explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 ) );
				$user = self::check_password_reset_key( $rp_key, $rp_login );

				// reset key / login is correct, display reset password form with hidden key / login values
				if ( is_object( $user ) ) {
					return wc_get_template( 'myaccount/form-reset-password.php', array(
						'key'   => $rp_key,
						'login' => $rp_login,
					) );
				} else {
					self::set_reset_password_cookie();
				}
			}
		}

		// Show lost password form by default
		wc_get_template( 'myaccount/form-lost-password.php', array(
			'form'  => 'lost_password',
		) );
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
		global $wpdb, $wp_hasher;

		$login = trim( $_POST['user_login'] );

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

		do_action( 'lostpassword_post', $errors );

		if ( $errors->get_error_code() ) {
			wc_add_notice( $errors->get_error_message(), 'error' );
			return false;
		}

		if ( ! $user_data ) {
			wc_add_notice( __( 'Invalid username or email.', 'woocommerce' ), 'error' );
			return false;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
			wc_add_notice( __( 'Invalid username or email.', 'woocommerce' ), 'error' );
			return false;
		}

		// redefining user_login ensures we return the right case in the email
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

		// Send email notification
		WC()->mailer(); // load email classes
		do_action( 'woocommerce_reset_password_notification', $user_login, $key );

		return true;
	}

	/**
	 * Retrieves a user row based on password reset key and login.
	 *
	 * @uses $wpdb WordPress Database object
	 *
	 * @param string $key Hash to validate sending user's password
	 * @param string $login The user login
	 * @return WP_User|bool User's database row on success, false for invalid keys
	 */
	public static function check_password_reset_key( $key, $login ) {
		// Check for the password reset key.
		// Get user data or an error message in case of invalid or expired key.
		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			wc_add_notice( $user->get_error_message(), 'error' );
			return false;
		}

		return $user;
	}

	/**
	 * Handles resetting the user's password.
	 *
	 * @param object $user The user
	 * @param string $new_pass New password for the user in plaintext
	 */
	public static function reset_password( $user, $new_pass ) {
		do_action( 'password_reset', $user, $new_pass );

		wp_set_password( $new_pass, $user->ID );
		self::set_reset_password_cookie();

		wp_password_change_notification( $user );
	}

	/**
	 * Set or unset the cookie.
	 */
	public static function set_reset_password_cookie( $value = '' ) {
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		$rp_path   = current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

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

			wc_print_notices();

			wc_get_template( 'myaccount/form-add-payment-method.php' );

			do_action( 'after_woocommerce_add_payment_method' );

		}
	}
}
