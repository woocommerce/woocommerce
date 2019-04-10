<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://github.com/woocommerce/woocommerce-admin
 * Description: A new JavaScript-driven interface for managing your store. The plugin includes new and improved reports, and a dashboard to monitor all the important key metrics of your site.
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: woocommerce-admin
 * Domain Path: /languages
 * Version: 0.10.0
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 3.5.7
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

if ( ! defined( 'WC_ADMIN_VERSION_NUMBER' ) ) {
	define( 'WC_ADMIN_VERSION_NUMBER', '0.10.0' );
}

/**
 * Notify users of the plugin requirements.
 */
function wc_admin_plugins_notice() {
	// The notice varies by WordPress version.
	$wordpress_version            = get_bloginfo( 'version' );
	$wordpress_includes_gutenberg = version_compare( $wordpress_version, '4.9.9', '>' );

	if ( $wordpress_includes_gutenberg ) {
		$message = sprintf(
			/* translators: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires <a href="%s">WooCommerce</a> 3.5 or greater to be installed and active.', 'woocommerce-admin' ),
			'https://wordpress.org/plugins/woocommerce/'
		);
	} else {
		$message = sprintf(
			/* translators: 1: URL of WordPress.org, 2: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">WordPress</a> 5.0 or greater and <a href="%2$s">WooCommerce</a> 3.5 or greater to be installed and active.', 'woocommerce-admin' ),
			'https://wordpress.org/',
			'https://wordpress.org/plugins/woocommerce/'
		);
	}
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Notify users that the plugin needs to be built.
 */
function wc_admin_build_notice() {
	$message_one = __( 'You have installed a development version of WooCommerce Admin which requires files to be built. From the plugin directory, run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files.', 'woocommerce-admin' );
	$message_two = sprintf(
		/* translators: 1: URL of GitHub Repository build page */
		__( 'Or you can download a pre-built version of the plugin by visiting <a href="%1$s">the releases page in the repository</a>.', 'woocommerce-admin' ),
		'https://github.com/woocommerce/woocommerce-admin/releases'
	);
	printf( '<div class="error"><p>%s %s</p></div>', $message_one, $message_two ); /* WPCS: xss ok. */
}

/**
 * Returns true if all dependencies for the wc-admin plugin are loaded.
 *
 * @return bool
 */
function wc_admin_dependencies_satisfied() {
	$woocommerce_minimum_met = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.5', '>' );
	if ( ! $woocommerce_minimum_met ) {
		return false;
	}

	$wordpress_version = get_bloginfo( 'version' );
	return version_compare( $wordpress_version, '4.9.9', '>' );
}

/**
 * Returns true if build file exists.
 *
 * @return bool
 */
function wc_admin_build_file_exists() {
	return file_exists( plugin_dir_path( __FILE__ ) . '/dist/app/index.js' );
}

/**
 * Daily events to run.
 *
 * Note: WC_Admin_Notes_Order_Milestones::other_milestones is hooked to this as well.
 */
function wc_admin_do_wc_admin_daily() {
	WC_Admin_Notes_New_Sales_Record::possibly_add_sales_record_note();
	WC_Admin_Notes_Mobile_App::possibly_add_mobile_app_note();
}
add_action( 'wc_admin_daily', 'wc_admin_do_wc_admin_daily' );

/**
 * Initializes wc-admin daily action when plugin activated.
 */
function wc_admin_activate_wc_admin_plugin() {
	if ( ! wc_admin_dependencies_satisfied() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
		wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
	}
}
register_activation_hook( WC_ADMIN_PLUGIN_FILE, 'wc_admin_activate_wc_admin_plugin' );

/**
 * Deactivate wc-admin plugin if dependencies not satisfied.
 */
function wc_admin_possibly_deactivate_wc_admin_plugin() {
	if ( ! wc_admin_dependencies_satisfied() ) {
		deactivate_plugins( plugin_basename( WC_ADMIN_PLUGIN_FILE ) );
		unset( $_GET['activate'] );
	}
}
add_action( 'admin_init', 'wc_admin_possibly_deactivate_wc_admin_plugin' );

/**
 * On deactivating the wc-admin plugin.
 */
function wc_admin_deactivate_wc_admin_plugin() {
	if ( wc_admin_dependencies_satisfied() ) {
		wp_clear_scheduled_hook( 'wc_admin_daily' );
		WC_Admin_Reports_Sync::clear_queued_actions();
	}
}
register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, 'wc_admin_deactivate_wc_admin_plugin' );

/**
 * Set up the plugin, only if we can detect both Gutenberg and WooCommerce
 */
function wc_admin_plugins_loaded() {
	if ( ! wc_admin_dependencies_satisfied() ) {
		add_action( 'admin_notices', 'wc_admin_plugins_notice' );
		return;
	}

	if ( ! function_exists( 'wc_admin_get_feature_config' ) ) {
		require_once WC_ADMIN_ABSPATH . '/includes/feature-config.php';
	}

	// Initialize the WC API extensions.
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-reports-sync.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-install.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-api-init.php';

	// Some common utilities.
	require_once WC_ADMIN_ABSPATH . '/lib/common.php';

	// Admin note providers.
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-new-sales-record.php';
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-mobile-app.php';
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-settings-notes.php';
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-woo-subscriptions-notes.php';
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-historical-data.php';
	require_once WC_ADMIN_ABSPATH . '/includes/notes/class-wc-admin-notes-order-milestones.php';

	// Verify we have a proper build.
	if ( ! wc_admin_build_file_exists() ) {
		add_action( 'admin_notices', 'wc_admin_build_notice' );
		return;
	}

	// Register script files.
	require_once WC_ADMIN_ABSPATH . '/lib/client-assets.php';

	// Create the Admin pages.
	require_once WC_ADMIN_ABSPATH . '/lib/admin.php';
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );

/**
 * Things to do after WooCommerce updates.
 */
function wc_admin_woocommerce_updated() {
	WC_Admin_Notes_Settings_Notes::add_notes_for_settings_that_have_moved();
}
add_action( 'woocommerce_updated', 'wc_admin_woocommerce_updated' );

/*
 * Remove the emoji script as it always defaults to replacing emojis with Twemoji images.
 * Gutenberg has also disabled emojis. More on that here -> https://github.com/WordPress/gutenberg/pull/6151
 */
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

/**
 * Filter in our ActionScheduler Store class.
 *
 * @param string $store_class ActionScheduler Store class name.
 * @return string ActionScheduler Store class name.
 */
function wc_admin_set_actionscheduler_store_class( $store_class ) {
	// Don't override any other overrides.
	if ( 'ActionScheduler_wpPostStore' !== $store_class ) {
		return $store_class;
	}

	// Include our store class here instead of wc_admin_plugins_loaded()
	// because ActionScheduler is hooked into `plugins_loaded` at a
	// much higher priority.
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-actionscheduler-wppoststore.php';

	return 'WC_Admin_ActionScheduler_WPPostStore';
}

// Hook up our modified ActionScheduler Store.
add_filter( 'action_scheduler_store_class', 'wc_admin_set_actionscheduler_store_class' );
