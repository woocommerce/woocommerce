<?php
/**
 * WooCommerce Updates
 *
 * Plugin updates script which updates the database.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Runs the installer.
 *
 * @access public
 * @return void
 */
function do_update_woocommerce() {
	global $woocommerce;
	
	// Do updates
	$current_db_version = get_option( 'woocommerce_db_version' );
	
	if ( version_compare( $current_db_version, '1.4', '<' ) ) {
		include( 'includes/updates/woocommerce-update-1.4.php' );
		update_option( 'woocommerce_db_version', '1.4' );
	}
	
	if ( version_compare( $current_db_version, '1.5', '<' ) ) {
		include( 'includes/updates/woocommerce-update-1.5.php' );
		update_option( 'woocommerce_db_version', '1.5' );
	}
	
	if ( version_compare( $current_db_version, '1.7', '<' ) ) {
		include( 'includes/updates/woocommerce-update-1.7.php' );
		update_option( 'woocommerce_db_version', '1.7' );
	}
	
	update_option( 'woocommerce_db_version', $woocommerce->version );
}