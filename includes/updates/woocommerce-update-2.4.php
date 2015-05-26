<?php
/**
 * Update WC to 2.4.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Maintain the old coupon logic for upgrades
update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );

/**
 * Update the old user API keys to the new Apps keys
 */
$api_users = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'woocommerce_api_consumer_key'" );
$apps_keys = array();

// Get user data
foreach ( $api_users as $_user ) {
	$user = get_userdata( $_user->user_id );
	$apps_keys[] = array(
		'user_id'         => $user->ID,
		'permission'      => $user->woocommerce_api_key_permissions,
		'consumer_key'    => $user->woocommerce_api_consumer_key,
		'consumer_secret' => $user->woocommerce_api_consumer_secret
	);
}

if ( ! empty( $apps_keys ) ) {
	// Create new apps
	foreach ( $apps_keys as $app ) {
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			$app,
			array(
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);
	}

	// Delete old user keys from usermeta
	foreach ( $api_users as $_user ) {
		$user_id = intval( $_user->user_id );
		delete_user_meta( $user_id, 'woocommerce_api_consumer_key' );
		delete_user_meta( $user_id, 'woocommerce_api_consumer_secret' );
		delete_user_meta( $user_id, 'woocommerce_api_key_permissions' );
	}
}
