<?php
/**
 * WooCommerce Updates
 *
 * Plugin updates script which updates the database.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.0.0
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

	// Include installer so we have page creation functions
	include_once( 'woocommerce-admin-install.php' );

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

	if ( version_compare( $current_db_version, '2.0', '<' ) ) {
		include( 'includes/updates/woocommerce-update-2.0.php' );
		update_option( 'woocommerce_db_version', '2.0' );
	}

	update_option( 'woocommerce_db_version', $woocommerce->version );
}