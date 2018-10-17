<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a modern, javascript-driven WooCommerce admin experience.
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: wc-admin
 * Domain Path: /languages
 * Version: 0.1.0
 *
 * @package WC_Admin
 */

if ( ! defined( 'WC_ADMIN_APP' ) ) {
	define( 'WC_ADMIN_APP', 'wc-admin-app' );
}

if ( ! defined( 'WC_ADMIN_ABSPATH' ) ) {
	define( 'WC_ADMIN_ABSPATH', dirname( __FILE__ ) );
}

if ( ! defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
	define( 'WC_ADMIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Notify users of the plugin requirements
 */
function wc_admin_plugins_notice() {
	$message = sprintf(
		/* translators: 1: URL of Gutenberg plugin, 2: URL of WooCommerce plugin */
		__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">Gutenberg</a> and <a href="%2$s">WooCommerce</a> (>3.5) to be installed and active.', 'wc-admin' ),
		'https://wordpress.org/plugins/gutenberg/',
		'https://wordpress.org/plugins/woocommerce/'
	);
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Returns true if all dependencies for the wc-admin plugin are loaded.
 *
 * @return bool
 */
function dependencies_satisfied() {
	return ( defined( 'GUTENBERG_DEVELOPMENT_MODE' ) || defined( 'GUTENBERG_VERSION' ) )
			&& class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.5', '>' );
}

/**
 * Activates wc-admin plugin when installed.
 */
function activate_wc_admin_plugin() {
	if ( ! dependencies_satisfied() ) {
		return;
	}
	// Initialize the WC API extensions.
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-api-init.php';

	WC_Admin_Api_Init::install();

}
register_activation_hook( WC_ADMIN_PLUGIN_FILE, 'activate_wc_admin_plugin' );

/**
 * Set up the plugin, only if we can detect both Gutenberg and WooCommerce
 */
function wc_admin_plugins_loaded() {
	if ( ! dependencies_satisfied() ) {
		add_action( 'admin_notices', 'wc_admin_plugins_notice' );
		return;
	}

	// Initialize the WC API extensions.
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-api-init.php';

	// Some common utilities.
	require_once dirname( __FILE__ ) . '/lib/common.php';

	// Register script files.
	require_once dirname( __FILE__ ) . '/lib/client-assets.php';

	// Create the Admin pages.
	require_once dirname( __FILE__ ) . '/lib/admin.php';
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );
