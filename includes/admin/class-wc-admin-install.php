<?php
/**
 * Installation related functions and actions.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Install' ) ) :

/**
 * WC_Admin_Install Class
 */
class WC_Admin_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'install_actions' ) );
	}

	/**
	 * Install actions such as installing pages when a button is clicked.
	 */
	public function install_actions() {
		// Install - Add pages button
		if ( ! empty( $_GET['install_woocommerce_pages'] ) ) {

			require_once( 'woocommerce-admin-install.php' );
			woocommerce_create_pages();

			// We no longer need to install pages
			delete_option( '_wc_needs_pages' );
			delete_transient( '_wc_activation_redirect' );

			// What's new redirect
			wp_safe_redirect( admin_url( 'index.php?page=wc-about&wc-installed=true' ) );
			exit;

		// Skip button
		} elseif ( ! empty( $_GET['skip_install_woocommerce_pages'] ) ) {

			// We no longer need to install pages
			delete_option( '_wc_needs_pages' );
			delete_transient( '_wc_activation_redirect' );

			// Flush rules after install
			flush_rewrite_rules();

			// What's new redirect
			wp_safe_redirect( admin_url( 'index.php?page=wc-about' ) );
			exit;

		// Update button
		} elseif ( ! empty( $_GET['do_update_woocommerce'] ) ) {

			include_once( 'woocommerce-admin-update.php' );
			do_update_woocommerce();

			// Update complete
			delete_option( '_wc_needs_pages' );
			delete_option( '_wc_needs_update' );
			delete_transient( '_wc_activation_redirect' );

			// What's new redirect
			wp_safe_redirect( admin_url( 'index.php?page=wc-about&wc-updated=true' ) );
			exit;
		}
	}
}

endif;

return new WC_Admin_Install();