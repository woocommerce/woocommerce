<?php
/**
 * Lost Password Shortcode
 *
 * Used on the checkout page, the checkout shortcode displays the checkout process.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Lost_Password
 * @version     2.0.0
 */

class WC_Shortcode_Lost_Password {

	/**
	 * Get the shortcode content.
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function get( $atts ) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @access public
	 * @param array $atts
	 * @return void
	 */
	public static function output( $atts ) {
		global $woocommerce;

		global $post;

		// arguments to pass to template
		$args = array( 'form' => 'lost_password' );

		// process lost password form
		if( isset( $_POST['user_login'] ) ) {

			$woocommerce->verify_nonce( 'lost_password' );

			self::retrieve_password();
		}

		// process reset key / login from email confirmation link
		if( isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {

			$user = self::check_password_reset_key( $_GET['key'], $_GET['login'] );

			// reset key / login is correct, display reset password form with hidden key / login values
			if( is_object( $user ) ) {
				$args['form'] = 'reset_password';
				$args['key'] = esc_attr( $_GET['key'] );
				$args['login'] = esc_attr( $_GET['login'] );
			}
		}

		// process reset password form
		if( isset( $_POST['password_1'] ) && isset( $_POST['password_2'] ) && isset( $_POST['reset_key'] ) && isset( $_POST['reset_login'] ) ) :

			// verify reset key again
			$user = self::check_password_reset_key( $_POST['reset_key'], $_POST['reset_login'] );

			if( is_object( $user ) ) {

				// save these values into the form again in case of errors
				$args['key'] = esc_attr( $_POST['reset_key'] );
				$args['login'] = esc_attr( $_POST['reset_login'] );

				$woocommerce->verify_nonce( 'reset_password' );

				if( empty( $_POST['password_1'] ) || empty( $_POST['password_2'] ) ) {
					$woocommerce->add_error( __( 'Please enter your password.', 'woocommerce' ) );
					$args['form'] = 'reset_password';
				}

				if( $_POST[ 'password_1' ] !== $_POST[ 'password_2' ] ) {
					$woocommerce->add_error( __( 'Passwords do not match.', 'woocommerce' ) );
					$args['form'] = 'reset_password';
				}

				if( 0 == $woocommerce->error_count() && ( $_POST['password_1'] == $_POST['password_2'] ) ) {

					self::reset_password( $user, esc_attr( $_POST['password_1'] ) );

					do_action( 'woocommerce_customer_reset_password', $user );

					$woocommerce->add_message( __( 'Your password has been reset.', 'woocommerce' ) . ' <a href="' . get_permalink( woocommerce_get_page_id( 'myaccount' ) ) . '">' . __( 'Log in', 'woocommerce' ) . '</a>' );
				}
			}

		endif;

		woocommerce_get_template( 'myaccount/form-lost-password.php', $args );
	}

	/**
	 * Handles sending password retrieval email to customer.
	 *
	 * @access public
	 * @uses $wpdb WordPress Database object
	 * @return bool True: when finish. False: on error
	 */
	public static function retrieve_password() {
		global $woocommerce,$wpdb;

		if ( empty( $_POST['user_login'] ) ) {

			$woocommerce->add_error( __( 'Enter a username or e-mail address.', 'woocommerce' ) );

		} elseif ( strpos( $_POST['user_login'], '@' ) ) {

			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );

			if ( empty( $user_data ) )
				$woocommerce->add_error( __( 'There is no user registered with that email address.', 'woocommerce' ) );

		} else {

			$login = trim( $_POST['user_login'] );

			$user_data = get_user_by('login', $login );
		}

		do_action('lostpassword_post');

		if( $woocommerce->error_count() > 0 )
			return false;

		if ( ! $user_data ) {
			$woocommerce->add_error( __( 'Invalid username or e-mail.', 'woocommerce' ) );
			return false;
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action('retrieve_password', $user_login);

		$allow = apply_filters('allow_password_reset', true, $user_data->ID);

		if ( ! $allow ) {

			$woocommerce->add_error( __( 'Password reset is not allowed for this user' ) );

			return false;

		} elseif ( is_wp_error( $allow ) ) {

			$woocommerce->add_error( $allow->get_error_message );

			return false;
		}

		$key = $wpdb->get_var( $wpdb->prepare( "SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login ) );

		if ( empty( $key ) ) {

			// Generate something random for a key...
			$key = wp_generate_password( 20, false );

			do_action('retrieve_password_key', $user_login, $key);

			// Now insert the new md5 key into the db
			$wpdb->update( $wpdb->users, array( 'user_activation_key' => $key ), array( 'user_login' => $user_login ) );
		}

		// Send email notification
		$mailer = $woocommerce->mailer();
		do_action( 'woocommerce_reset_password_notification', $user_login, $key );

		$woocommerce->add_message( __( 'Check your e-mail for the confirmation link.' ) );
		return true;
	}

	/**
	 * Retrieves a user row based on password reset key and login
	 *
	 * @uses $wpdb WordPress Database object
	 *
	 * @access public
	 * @param string $key Hash to validate sending user's password
	 * @param string $login The user login
	 * @return object|bool User's database row on success, false for invalid keys
	 */
	public static function check_password_reset_key( $key, $login ) {
		global $woocommerce,$wpdb;

		$key = preg_replace( '/[^a-z0-9]/i', '', $key );

		if ( empty( $key ) || ! is_string( $key ) ) {
			$woocommerce->add_error( __( 'Invalid key', 'woocommerce' ) );
			return false;
		}

		if ( empty( $login ) || ! is_string( $login ) ) {
			$woocommerce->add_error( __( 'Invalid key', 'woocommerce' ) );
			return false;
		}

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login ) );

		if ( empty( $user ) ) {
			$woocommerce->add_error( __( 'Invalid key', 'woocommerce' ) );
			return false;
		}

		return $user;
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
}