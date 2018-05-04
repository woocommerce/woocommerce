<?php
/**
 * Plugin Name: Woo Dashboard
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a new Dashboard view of WooCommerce
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: woo-dash
 * Domain Path: /languages
 * Version: 0.1.0
 *
 * @package         Woo_Dash
 */

if ( ! defined( 'WOO_DASH_APP' ) ) {
	define( 'WOO_DASH_APP', 'woo-dash-app' );
}

/**
 * Notify users of the plugin requirements
 */
function woo_dash_plugins_notice() {
	$message = sprintf(
		__( 'The WooCommerce Dashboard feature plugin requires both <a href="%1$s">Gutenberg</a> and <a href="%2$s">WooCommerce</a> to be installed and active.', 'woo-dash' ),
		'https://wordpress.org/plugins/gutenberg/',
		'https://wordpress.org/plugins/woocommerce/'
	);
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Set up the plugin, only if we can detect both Gutenberg and WooCommerce
 */
function woo_dash_plugins_loaded() {
	if (
		! defined( 'GUTENBERG_DEVELOPMENT_MODE' ) ||
		! defined( 'GUTENBERG_VERSION' ) ||
		! class_exists( 'WooCommerce' )
	) {
		add_action( 'admin_notices', 'woo_dash_plugins_notice' );
		return;
	}

	// Some common utilities
	require_once dirname( __FILE__ ) . '/lib/common.php';

	// Register script files
	require_once dirname( __FILE__ ) . '/lib/client-assets.php';

	// Create the Admin pages
	require_once dirname( __FILE__ ) . '/lib/admin.php';
}
add_action( 'plugins_loaded', 'woo_dash_plugins_loaded' );
