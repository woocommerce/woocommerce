<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a modern, javascript-driven WooCommerce admin experience.
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: wc-admin
 * Domain Path: /languages
 * Version: 0.3.0
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
	// The notice varies by WordPress version.
	$wordpress_version            = get_bloginfo( 'version' );
	$wordpress_includes_gutenberg = version_compare( $wordpress_version, '4.9.9', '>' );

	if ( $wordpress_includes_gutenberg ) {
		$message = sprintf(
			/* translators: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires <a href="%s">WooCommerce</a> (>3.5) to be installed and active.', 'wc-admin' ),
			'https://wordpress.org/plugins/woocommerce/'
		);
	} else {
		$message = sprintf(
			/* translators: 1: URL of Gutenberg plugin, 2: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">Gutenberg</a> and <a href="%2$s">WooCommerce</a> (>3.5) to be installed and active.', 'wc-admin' ),
			'https://wordpress.org/plugins/gutenberg/',
			'https://wordpress.org/plugins/woocommerce/'
		);
	}
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Returns true if all dependencies for the wc-admin plugin are loaded.
 *
 * @return bool
 */
function dependencies_satisfied() {
	$woocommerce_minimum_met = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.5', '>' );
	if ( ! $woocommerce_minimum_met ) {
		return false;
	}

	$wordpress_version            = get_bloginfo( 'version' );
	$wordpress_includes_gutenberg = version_compare( $wordpress_version, '4.9.9', '>' );
	$gutenberg_plugin_active      = defined( 'GUTENBERG_DEVELOPMENT_MODE' ) || defined( 'GUTENBERG_VERSION' );

	return $wordpress_includes_gutenberg || $gutenberg_plugin_active;
}

/**
 * Daily events to run.
 */
function do_wc_admin_daily() {
	WC_Admin_Notes_New_Sales_Record::possibly_add_sales_record_note();
}
add_action( 'wc_admin_daily', 'do_wc_admin_daily' );

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

	if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
		wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
	}
}
register_activation_hook( WC_ADMIN_PLUGIN_FILE, 'activate_wc_admin_plugin' );

/**
 * Deactivate wc-admin plugin if dependencies not satisfied.
 */
function possibly_deactivate_wc_admin_plugin() {
	if ( ! dependencies_satisfied() ) {
		deactivate_plugins( plugin_basename( WC_ADMIN_PLUGIN_FILE ) );
		unset( $_GET['activate'] );
	}
}
add_action( 'admin_init', 'possibly_deactivate_wc_admin_plugin' );

/**
 * On deactivating the wc-admin plugin.
 */
function deactivate_wc_admin_plugin() {
	wp_clear_scheduled_hook( 'wc_admin_daily' );
}
register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, 'deactivate_wc_admin_plugin' );

/**
 * Update the database tables if needed. This hooked function does NOT need to
 * be ported to WooCommerce's code base - WC_Install will do this on plugin
 * update automatically.
 */
function wc_admin_init() {
	if ( ! dependencies_satisfied() ) {
		return;
	}

	// Only create/update tables on init if WP_DEBUG is true.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		WC_Admin_Api_Init::create_db_tables();
	}
}
add_action( 'init', 'wc_admin_init' );

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

	// Admin note providers.
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-notes-new-sales-record.php';
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-notes-settings-notes.php';
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-notes-woo-subscriptions-notes.php';
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );

/**
 * Things to do after WooCommerce updates.
 */
function wc_admin_woocommerce_updated() {
	WC_Admin_Notes_Settings_Notes::add_notes_for_settings_that_have_moved();
}
add_action( 'woocommerce_updated', 'wc_admin_woocommerce_updated' );
