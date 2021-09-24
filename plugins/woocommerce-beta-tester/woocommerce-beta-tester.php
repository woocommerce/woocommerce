<?php
/**
 * Plugin Name: WooCommerce Beta Tester
 * Plugin URI: https://github.com/woocommerce/woocommerce-beta-tester
 * Description: Run bleeding edge versions of WooCommerce. This will replace your installed version of WooCommerce with the latest tagged release - use with caution, and not on production sites.
 * Version: 2.0.3
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 4.4
 * Tested up to: 5.8
 * WC requires at least: 3.6.0
 * WC tested up to: 5.7.0
 * Text Domain: woocommerce-beta-tester
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

// Define WC_BETA_TESTER_FILE.
if ( ! defined( 'WC_BETA_TESTER_FILE' ) ) {
	define( 'WC_BETA_TESTER_FILE', __FILE__ );
}

if ( ! defined( 'WC_BETA_TESTER_VERSION' ) ) {
	define( 'WC_BETA_TESTER_VERSION', '2.0.2' );
}

/**
 * Load text domain before all other code.
 *
 * @since 2.0.0
 */
function _wc_beta_tester_load_textdomain() {
	load_plugin_textdomain( 'woocommerce-beta-tester', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', '_wc_beta_tester_load_textdomain' );

/**
 * Boostrap plugin.
 */
function _wc_beta_tester_bootstrap() {

	// Check if WooCommerce is enabled.
	if ( ! class_exists( 'WooCommerce' ) ) {
		include dirname( __FILE__ ) . '/includes/class-wc-beta-tester-admin-notices.php';
		$notices = new WC_Beta_Tester_Admin_Notices();

		add_action( 'admin_notices', array( $notices, 'woocoommerce_not_installed' ) );
	} elseif ( ! class_exists( 'WC_Beta_Tester' ) ) {
		include dirname( __FILE__ ) . '/includes/class-wc-beta-tester.php';
		// Settings.
		include dirname( __FILE__ ) . '/includes/class-wc-beta-tester-channel.php';
		include dirname( __FILE__ ) . '/includes/class-wc-beta-tester-import-export.php';
		new WC_Beta_Tester_Import_Export();
		// Tools.
		include dirname( __FILE__ ) . '/includes/class-wc-beta-tester-version-picker.php';

		register_activation_hook( __FILE__, array( 'WC_Beta_Tester', 'activate' ) );

		add_action( 'admin_init', array( 'WC_Beta_Tester', 'instance' ) );
	}
}

add_action( 'plugins_loaded', '_wc_beta_tester_bootstrap' );
