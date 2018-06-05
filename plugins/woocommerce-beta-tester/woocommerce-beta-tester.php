<?php
/**
 * Plugin Name: WooCommerce Beta Tester
 * Plugin URI: https://github.com/woocommerce/woocommerce-beta-tester
 * Description: Run bleeding edge versions of WooCommerce. This will replace your installed version of WooCommerce with the latest tagged release - use with caution, and not on production sites. You have been warned.
 * Version: 1.0.3
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 4.4
 * Tested up to: 4.9
 *
 * Text Domain: woocommerce-beta-tester
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Confirm woocommerce is at least installed before doing anything
 * Curiously, developers are discouraged from using WP_PLUGIN_DIR and not given a
 * function with which to get the plugin directory, so this is what we have to do.
 */
if ( ! file_exists( trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'woocommerce/woocommerce.php' ) ) {
	if ( ! function_exists( 'wcbt_woocoommerce_not_installed' ) ) {

		/**
		 * WooCommerce Not Installed Notice.
		 */
		function wcbt_woocoommerce_not_installed() {
			/* translators: %s: woocommerce url */
			echo '<div class="error"><p>' . wp_kses_post( sprintf( __( 'WooCommerce Beta Tester requires %s to be installed.', 'woocommerce-beta-tester' ), '<a href="http://www.woocommerce.com/" target="_blank">WooCommerce</a>' ) ) . '</p></div>';
		}
	}

	add_action( 'admin_notices', 'wcbt_woocoommerce_not_installed' );
} elseif ( ! class_exists( 'WC_Beta_Tester' ) ) {
	include dirname( __FILE__ ) . '/includes/class-wc-beta-tester.php';

	register_activation_hook( __FILE__, array( 'WC_Beta_Tester', 'activate' ) );

	add_action( 'admin_init', array( 'WC_Beta_Tester', 'instance' ) );
}
