<?php
/**
 * Display notices in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Notices' ) ) :

/**
 * WC_Admin_Notices Class
 */
class WC_Admin_Notices {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_print_styles', array( $this, 'add_notices' ) );
	}

	/**
	 * Add notices + styles if needed.
	 */
	public function add_notices() {
		if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'install_notice' ) );
		}

		$template = get_option( 'template' );

		if ( ! current_theme_supports( 'woocommerce' ) && ! in_array( $template, array( 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' ) ) ) {

			if ( ! empty( $_GET['hide_woocommerce_theme_support_check'] ) ) {
				update_option( 'woocommerce_theme_support_check', $template );
				return;
			}

			if ( get_option( 'woocommerce_theme_support_check' ) !== $template ) {
				wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
				add_action( 'admin_notices', array( $this, 'theme_check_notice' ) );
			}
		}
	}

	/**
	 * Show the install notices
	 */
	function install_notice() {

		// If we need to update, include a message with the update button
		if ( get_option( '_wc_needs_update' ) == 1 ) {
			include( 'views/html-notice-update.php' );
		}

		// If we have just installed, show a message with the install pages button
		elseif ( get_option( '_wc_needs_pages' ) == 1 ) {
			include( 'views/html-notice-install.php' );
		}
	}

	/**
	 * Show the Theme Check notice
	 */
	function theme_check_notice() {
		include( 'views/html-notice-theme-support.php' );
	}
}

endif;

return new WC_Admin_Notices();