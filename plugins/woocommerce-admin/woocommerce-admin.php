<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://github.com/woocommerce/woocommerce-admin
 * Description: A new JavaScript-driven interface for managing your store. The plugin includes new and improved reports, and a dashboard to monitor all the important key metrics of your site.
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: woocommerce-admin
 * Domain Path: /languages
 * Version: 0.11.0
 *
 * WC requires at least: 3.6.0
 * WC tested up to: 3.6.2
 *
 * @package WC_Admin
 */

if ( ! defined( 'WC_ADMIN_APP' ) ) {
	define( 'WC_ADMIN_APP', 'wc-admin-app' );
}

if ( ! defined( 'WC_ADMIN_ABSPATH' ) ) {
	define( 'WC_ADMIN_ABSPATH', dirname( __FILE__ ) . '/' );
}

if ( ! defined( 'WC_ADMIN_DIST_JS_FOLDER' ) ) {
	define( 'WC_ADMIN_DIST_JS_FOLDER', 'dist/' );
}

if ( ! defined( 'WC_ADMIN_DIST_CSS_FOLDER' ) ) {
	define( 'WC_ADMIN_DIST_CSS_FOLDER', 'dist/' );
}

if ( ! defined( 'WC_ADMIN_FEATURES_PATH' ) ) {
	define( 'WC_ADMIN_FEATURES_PATH', WC_ADMIN_ABSPATH . 'includes/features/' );
}

if ( ! defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
	define( 'WC_ADMIN_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WC_ADMIN_VERSION_NUMBER' ) ) {
	define( 'WC_ADMIN_VERSION_NUMBER', '0.11.0' );
}

/**
 * Removes core hooks in favor of our local feature plugin handlers.
 *
 * @see WC_Admin_Library::__construct()
 */
function wc_admin_initialize() {
	remove_action( 'init', array( 'WC_Admin_Library', 'load_features' ) );
	remove_action( 'admin_enqueue_scripts', array( 'WC_Admin_Library', 'register_scripts' ) );
	remove_action( 'admin_enqueue_scripts', array( 'WC_Admin_Library', 'load_scripts' ), 15 );
	remove_action( 'woocommerce_components_settings', array( 'WC_Admin_Library', 'add_component_settings' ) );
	remove_filter( 'admin_body_class', array( 'WC_Admin_Library', 'add_admin_body_classes' ) );
	remove_action( 'admin_menu', array( 'WC_Admin_Library', 'register_page_handler' ) );
	remove_filter( 'admin_title', array( 'WC_Admin_Library', 'update_admin_title' ) );

	remove_action( 'rest_api_init', array( 'WC_Admin_Library', 'register_user_data' ) );
	remove_action( 'in_admin_header', array( 'WC_Admin_Library', 'embed_page_header' ) );
	remove_filter( 'woocommerce_settings_groups', array( 'WC_Admin_Library', 'add_settings_group' ) );
	remove_filter( 'woocommerce_settings-wc_admin', array( 'WC_Admin_Library', 'add_settings' ) );

	remove_action( 'admin_head', array( 'WC_Admin_Library', 'update_link_structure' ), 20 );

	require_once WC_ADMIN_ABSPATH . 'includes/class-wc-admin-loader.php';
}
add_action( 'woocommerce_loaded', 'wc_admin_initialize' );

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
			__( 'The WooCommerce Admin feature plugin requires <a href="%s">WooCommerce</a> 3.6 or greater to be installed and active.', 'woocommerce-admin' ),
			'https://wordpress.org/plugins/woocommerce/'
		);
	} else {
		$message = sprintf(
			/* translators: 1: URL of WordPress.org, 2: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">WordPress</a> 5.0 or greater and <a href="%2$s">WooCommerce</a> 3.6 or greater to be installed and active.', 'woocommerce-admin' ),
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
	$woocommerce_minimum_met = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.6', '>=' );
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
 * Adds a menu item for the wc-admin devdocs.
 */
function wc_admin_devdocs() {
	if ( WC_Admin_Loader::is_feature_enabled( 'devdocs' ) && defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		wc_admin_register_page(
			array(
				'title'  => 'DevDocs',
				'parent' => 'woocommerce',
				'path'   => '/devdocs',
			)
		);
	}
}
add_action( 'admin_menu', 'wc_admin_devdocs' );

/**
 * Daily events to run.
 *
 * Note: WC_Admin_Notes_Order_Milestones::other_milestones is hooked to this as well.
 */
function wc_admin_do_wc_admin_daily() {
	WC_Admin_Notes_New_Sales_Record::possibly_add_sales_record_note();
	WC_Admin_Notes_Giving_Feedback_Notes::add_notes_for_admin_giving_feedback();
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
		WC_Admin_Notes::clear_queued_actions();
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

	// Initialize the WC API extensions.
	require_once WC_ADMIN_ABSPATH . 'includes/class-wc-admin-reports-sync.php';
	require_once WC_ADMIN_ABSPATH . 'includes/class-wc-admin-install.php';
	require_once WC_ADMIN_ABSPATH . 'includes/class-wc-admin-api-init.php';

	// Admin note providers.
	// @todo These should be bundled in the features/ folder, but loading them from there currently has a load order issue.
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-new-sales-record.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-settings-notes.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-giving-feedback-notes.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-woo-subscriptions-notes.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-historical-data.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-order-milestones.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-mobile-app.php';
	require_once WC_ADMIN_ABSPATH . 'includes/notes/class-wc-admin-notes-welcome-message.php';

	// Verify we have a proper build.
	if ( ! wc_admin_build_file_exists() ) {
		add_action( 'admin_notices', 'wc_admin_build_notice' );
		return;
	}
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );

/**
 * Overwrites the allowed features array using a local `feature-config.php` file.
 *
 * @param array $features Array of feature slugs.
 */
function wc_admin_overwrite_features( $features ) {
	if ( ! function_exists( 'wc_admin_get_feature_config' ) ) {
		require_once WC_ADMIN_ABSPATH . '/includes/feature-config.php';
	}
	$feature_config = wc_admin_get_feature_config();
	$features       = array_keys( array_filter( $feature_config ) );
	return $features;
}
add_filter( 'woocommerce_admin_features', 'wc_admin_overwrite_features' );

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

/**
 * Load plugin text domain for translations.
 */
function wc_admin_load_plugin_textdomain() {
	load_plugin_textdomain( 'woocommerce-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wc_admin_load_plugin_textdomain' );

/**
 * Format a number using the decimal and thousands separator settings in WooCommerce.
 *
 * @param mixed $number Number to be formatted.
 * @return string
 */
function wc_admin_number_format( $number ) {
	$currency_settings = WC_Admin_Loader::get_currency_settings();
	return number_format(
		$number,
		0,
		$currency_settings['decimal_separator'],
		$currency_settings['thousand_separator']
	);
}

/**
 * Retrieves a URL to relative path inside WooCommerce admin with
 * the provided query parameters.
 *
 * @param  string $path Relative path of the desired page.
 * @param  array  $query Query parameters to append to the path.
 *
 * @return string       Fully qualified URL pointing to the desired path.
 */
function wc_admin_url( $path, $query = array() ) {
	if ( ! empty( $query ) ) {
		$query_string = http_build_query( $query );
		$path         = $path . '?' . $query_string;
	}

	return admin_url( 'admin.php?page=wc-admin#' . $path, dirname( __FILE__ ) );
}
