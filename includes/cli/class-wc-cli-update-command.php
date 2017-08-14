<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows updates via CLI.
 *
 * @version 3.0.0
 * @package WooCommerce
 */
class WC_CLI_Update_Command {

	/**
	 * Registers the update command.
	 */
	public static function register_commands() {
		WP_CLI::add_command( 'wc update', array( 'WC_CLI_Update_Command', 'update' ) );
	}

	/**
	 * Runs all pending WooCommerce database updates.
	 */
	public static function update() {
		global $wpdb;

		$wpdb->hide_errors();

		include_once( WC_ABSPATH . 'includes/class-wc-install.php' );
		include_once( WC_ABSPATH . 'includes/wc-update-functions.php' );

		$current_db_version = get_option( 'woocommerce_db_version' );
		$update_count       = 0;

		foreach ( WC_Install::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					WP_CLI::log( sprintf( __( 'Calling update function: %s', 'woocommerce' ), $update_callback ) );
					call_user_func( $update_callback );
					$update_count ++;
				}
			}
		}

		WC_Admin_Notices::remove_notice( 'update' );
		WP_CLI::success( sprintf( __( '%1$d updates complete. Database version is %2$s', 'woocommerce' ), absint( $update_count ), get_option( 'woocommerce_db_version' ) ) );
	}
}
