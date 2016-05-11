<?php
/**
 * WooCommerce Webhook functions
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get Webhook statuses.
 *
 * @since  2.3.0
 * @return array
 */
function wc_get_webhook_statuses() {
	return apply_filters( 'woocommerce_webhook_statuses', array(
		'active'   => __( 'Active', 'woocommerce' ),
		'paused'   => __( 'Paused', 'woocommerce' ),
		'disabled' => __( 'Disabled', 'woocommerce' ),
	) );
}

/**
 * Generate webhook secret based in the user data.
 *
 * @since 2.6.0
 * @param int $user_id User ID.
 * @return string Secret of empty string if not found the user.
 */
function wc_webhook_generate_secret( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user = get_userdata( $user_id ) ) {
		return md5( $user_id . '|' . $user->data->user_login );
	}

	return '';
}
