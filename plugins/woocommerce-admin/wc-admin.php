<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a modern, javascript-driven WooCommerce admin experience.
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: woocommerce
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

/**
 * Notify users of the plugin requirements
 */
function wc_admin_plugins_notice() {
	$message = sprintf(
		__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">Gutenberg</a> and <a href="%2$s">WooCommerce</a> to be installed and active.', 'wc-admin' ),
		'https://wordpress.org/plugins/gutenberg/',
		'https://wordpress.org/plugins/woocommerce/'
	);
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Set up the plugin, only if we can detect both Gutenberg and WooCommerce
 */
function wc_admin_plugins_loaded() {
	if (
		! ( defined( 'GUTENBERG_DEVELOPMENT_MODE' ) || defined( 'GUTENBERG_VERSION' ) ) ||
		! class_exists( 'WooCommerce' )
	) {
		add_action( 'admin_notices', 'wc_admin_plugins_notice' );
		return;
	}

	// Initialize the WC API extensions.
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-api-init.php';

	// Some common utilities
	require_once dirname( __FILE__ ) . '/lib/common.php';

	// Register script files
	require_once dirname( __FILE__ ) . '/lib/client-assets.php';

	// Create the Admin pages
	require_once dirname( __FILE__ ) . '/lib/admin.php';
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );
