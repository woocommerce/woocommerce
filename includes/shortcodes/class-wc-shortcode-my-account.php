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
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		return WC_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
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

			if ( isset( $wp->query_vars['lost-password'] ) ) {

				self::lost_password();

			} else {

				wc_get_template( 'myaccount/form-login.php' );

			}

		} else {

			if ( ! empty( $wp->query_vars['view-order'] ) ) {

				self::view_order( absint( $wp->query_vars['view-order'] ) );

			} elseif ( isset( $wp->query_vars['edit-account'] ) ) {

				self::edit_account();

			} elseif ( isset( $wp->query_vars['edit-address'] ) ) {

				self::edit_address( wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) );

			} elseif ( isset( $wp->query_vars['add-payment-method'] ) ) {

				self::add_payment_method( $wp->query_vars['add-payment-method'] );

			} else {

				self::my_account( $atts );

			}
		}
	}

	/**
	 * My account page
	 *
	 * @param  array $atts
	 */
	private static function my_account( $atts ) {
		extract( shortcode_atts( array(
	    	'order_count' => 15
		), $atts ) );

		wc_get_template( 'myaccount/my-account.php', array(
			'current_user' 	=> get_user_by( 'id', get_current_user_id() ),
			'order_count' 	=> 'all' == $order_count ? -1 : $order_count
		) );
	}

	/**
	 * View order page
	 *
	 * @param  int $order_id
	 */
	private static function view_order( $order_id ) {

		$user_id      	= get_current_user_id();
		$order 			= wc_get_order( $order_id );

		if ( ! current_user_can( 'view_order', $order_id ) ) {
			echo '<div class="woocommerce-error">' . __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . wc_get_page_permalink( 'myaccount' ).'" class="wc-forward">'. __( 'My Account', 'woocommerce' ) .'</a>' . '</div>';
			return;
		}

		// Backwards compatibility
		$status       = new stdClass();
		$status->name = wc_get_order_status_name( $order->get_status() );

		wc_get_template( 'myaccount/view-order.php', array(
	        'status'    => $status, // @deprecated 2.2
	        'order'     => wc_get_order( $order_id ),
	        'order_id'  => $order_id
	    ) );
	}

	/**
	 * Edit account details page
	 */
	private static function edit_account() {
		wc_get_template( 'myaccount/form-edit-account.php', array( 'user' => get_user_by( 'id', get_current_user_id() ) ) );
	}

	/**
	 * Edit address page.
	 *
	 * @access public
	 * @param string $load_address
	 */
	private static function edit_address( $load_address = 'billing' ) {

		// Current user
		global $current_user;
		get_currentuserinfo();

		$load_address = sanitize_key( $load_address );

		$address = WC()->countries->get_address_fields( get_user_meta( get_current_user_id(), $load_address . '_country', true ), $load_address . '_' );

		// Enqueue scripts
		wp_enqueue_script( 'wc-country-select' );
		wp_enqueue_script( 'wc-address-i18n' );

		// Prepare values
		foreach ( $address as $key => $field ) {

			$value = get_user_meta( get_current_user_id(), $key, true );

			if ( ! $value ) {
				switch( $key ) {
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
			'address'		=> apply_filters( 'woocommerce_address_to_edit', $address )
		) );
	}

	/**
	 * Lost password page
	 */
	public static function lost_password() {

		global $post;

		// arguments to pass to template
		$args = array( 'form' => 'lost_password' );

		// process reset key / login from email confirmation link
		if ( isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {

			$user = self::check_password_reset_key( $_GET['key'], $_GET['login'] );

			// reset key / login is correct, display reset password form with hidden key / login values
			if( is_object( $user ) ) {
				$args['form'] = 'reset_password';
				$args['key'] = esc_attr( $_GET['key'] );
				$args['login'] = esc_attr( $_GET['login'] );
			}
		} elseif ( isset( $_GET['reset'] ) ) {
			wc_add_notice( __( 'Your password has been reset.', 'woocommerce' ) . ' <a href="' . wc_get_page_permalink( 'myaccount' ) . '">' . __( 'Log in', 'woocommerce' ) . '</a>' );
		}

		wc_get_template( 'myaccount/form-lost-password.php', $args );
	}

	/**
	 * Handles sending password retrieval email to customer.
	 *
	 * Based on retrieve_password() in core wp-login.php
	 *
	 * @access public
	 * @uses $wpdb WordPress Database object
	 * @return bool True: when finish. False: on error
	 */
	public static function retrieve_password() {
		global $wpdb, $wp_hasher;

		if ( empty( $_POST['user_login'] ) ) {

			wc_add_notice( __( 'Enter a username or e-mail address.', 'woocommerce' ), 'error' );
			return false;

		} else {
			// Check on username first, as customers can use emails as usernames.
			$login = trim( $_POST['user_login'] );
			$user_data = get_user_by( 'login', $login );
		}

		// If no user found, check if it login is email and lookup user based on email.
		if ( ! $user_data && is_email( $_POST['user_login'] ) && apply_filters( 'woocommerce_get_username_from_email', true ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		}

		do_action( 'lostpassword_post' );

		if ( ! $user_data ) {
			wc_add_notice( __( 'Invalid username or e-mail.', 'woocommerce' ), 'error' );
			return false;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
			wc_add_notice( __( 'Invalid username or e-mail.', 'woocommerce' ), 'error' );
			return false;
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {

			wc_add_notice( __( 'Password reset is not allowed for this user', 'woocommerce' ), 'error' );

			return false;

		} elseif ( is_wp_error( $allow ) ) {

			wc_add_notice( $allow->get_error_message(), 'error' );

			return false;
		}

		$key = wp_generate_password( 20, false );

		do_action( 'retrieve_password_key', $user_login, $key );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}

		$hashed = $wp_hasher->HashPassword( $key );

		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );

		// Send email notification
		WC()->mailer(); // load email classes
		do_action( 'woocommerce_reset_password_notification', $user_login, $key );

		wc_add_notice( __( 'Check your e-mail for the confirmation link.', 'woocommerce' ) );
		return true;
	}

	/**
	 * Retrieves a user row based on password reset key and login
	 *
	 * @uses $wpdb WordPress Database object
	 *
	 * @param string $key Hash to validate sending user's password
	 * @param string $login The user login
	 * @return WP_USER|bool User's database row on success, false for invalid keys
	 */
	public static function check_password_reset_key( $key, $login ) {
		global $wpdb, $wp_hasher;

		$key = preg_replace( '/[^a-z0-9]/i', '', $key );

		if ( empty( $key ) || ! is_string( $key ) ) {
			wc_add_notice( __( 'Invalid key', 'woocommerce' ), 'error' );
			return false;
		}

		if ( empty( $login ) || ! is_string( $login ) ) {
			wc_add_notice( __( 'Invalid key', 'woocommerce' ), 'error' );
			return false;
		}

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_login = %s", $login ) );

		if ( ! empty( $user ) ) {
			if ( empty( $wp_hasher ) ) {
				require_once ABSPATH . 'wp-includes/class-phpass.php';
				$wp_hasher = new PasswordHash( 8, true );
			}

			$valid = $wp_hasher->CheckPassword( $key, $user->user_activation_key );
		}

		if ( empty( $user ) || empty( $valid ) ) {
			wc_add_notice( __( 'Invalid key', 'woocommerce' ), 'error' );
			return false;
		}

		return get_userdata( $user->ID );
	}

	/**
	 * Handles resetting the user's password.
	 *
	 * @access public
	 * @param object $user The user
	 * @param string $new_pass New password for the user in plaintext
	 * @return void
	 */
	public static function reset_password( $user, $new_pass ) {
		do_action( 'password_reset', $user, $new_pass );

		wp_set_password( $new_pass, $user->ID );

		wp_password_change_notification( $user );
	}

	/**
	 * Show the add payment method page
	 */
	private static function add_payment_method() {

		if ( ! is_user_logged_in() ) {

			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit();

		} else {

			do_action( 'before_woocommerce_add_payment_method' );

			wc_add_notice( __( 'Add a new payment method.', 'woocommerce' ), 'notice'  );

			wc_print_notices();

			// Add payment method form
			wc_get_template( 'myaccount/form-add-payment-method.php' );

			wc_print_notices();

			do_action( 'after_woocommerce_add_payment_method' );

		}

	}
}
