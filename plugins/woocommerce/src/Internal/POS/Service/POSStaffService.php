<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use WP_Error;

/**
 * Creates new POS staff WP users.
 *
 * Backs the "create new user inline" path on the POS Staff admin form. New users
 * are inserted with the `pos_staff` WP role and a random password — POS staff
 * authenticate by PIN at the till, not via wp-login. The caller is responsible
 * for assigning a POS preset and PIN afterwards via {@see Capabilities::set_pos_preset()}
 * and {@see POSPinService::set_pin()}.
 *
 * @since 11.0.0
 * @internal
 */
class POSStaffService {

	/**
	 * Create a new POS staff WP user.
	 *
	 * @param string               $email        Staff email address. Required.
	 * @param string               $display_name Staff display name. Required.
	 * @param array<string, mixed> $args         Optional overrides for wp_insert_user(). Useful keys:
	 *                                            - user_login: defaults to a sanitized form of display_name.
	 *                                            - user_pass:  defaults to a random 24-char password.
	 * @return int|WP_Error User ID on success, WP_Error on validation failure.
	 */
	public function create_staff( string $email, string $display_name, array $args = array() ) {
		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error(
				'woocommerce_pos_staff_invalid_email',
				__( 'Please provide a valid email address.', 'woocommerce' )
			);
		}

		if ( email_exists( $email ) ) {
			return new WP_Error(
				'woocommerce_pos_staff_email_exists',
				__( 'An account is already registered with that email address.', 'woocommerce' )
			);
		}

		$display_name = trim( $display_name );
		if ( '' === $display_name ) {
			return new WP_Error(
				'woocommerce_pos_staff_missing_display_name',
				__( 'Please provide a display name for the staff member.', 'woocommerce' )
			);
		}

		$user_login = isset( $args['user_login'] ) && '' !== $args['user_login']
			? sanitize_user( (string) $args['user_login'], true )
			: wc_create_new_customer_username( $email, array( 'first_name' => $display_name ) );

		if ( '' === $user_login || ! validate_username( $user_login ) ) {
			return new WP_Error(
				'woocommerce_pos_staff_invalid_username',
				__( 'Could not derive a valid username from the staff member email. Provide an explicit username.', 'woocommerce' )
			);
		}

		if ( username_exists( $user_login ) ) {
			return new WP_Error(
				'woocommerce_pos_staff_username_exists',
				__( 'That username is already in use. Choose a different one.', 'woocommerce' )
			);
		}

		$user_pass = isset( $args['user_pass'] ) && '' !== $args['user_pass']
			? (string) $args['user_pass']
			: wp_generate_password( 24, true, true );

		$user_data = array_merge(
			$args,
			array(
				'user_login'   => $user_login,
				'user_email'   => $email,
				'user_pass'    => $user_pass,
				'display_name' => $display_name,
				'role'         => Capabilities::POS_STAFF_ROLE,
			)
		);

		$user_id = wp_insert_user( $user_data );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		/**
		 * Fires after a POS staff WP user has been created.
		 *
		 * @since 11.0.0
		 *
		 * @param int                  $user_id   New user ID.
		 * @param array<string, mixed> $user_data User data passed to wp_insert_user().
		 */
		do_action( 'woocommerce_created_pos_staff', $user_id, $user_data );

		return (int) $user_id;
	}
}
