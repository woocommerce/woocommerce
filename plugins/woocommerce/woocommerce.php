<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 7.9.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.1
 * Requires PHP: 7.3
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';
require_once __DIR__ . '/vendor/autoload_packages.php';


if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}

\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WooCommerce::instance();
}

/**
 * Returns the WooCommerce object container.
 * Code in the `includes` directory should use the container to get instances of classes in the `src` directory.
 *
 * @since  4.4.0
 * @return \Automattic\WooCommerce\Container The WooCommerce object container.
 */
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();


/**
 * Initialize the Jetpack functionalities: connection, identity crisis, etc.
 */
function wc_jetpack_init() {

	$config = new Automattic\Jetpack\Config();
	$config->ensure(
		'connection',
		array(
			'slug' => 'woocommerce',
			'name' => __( 'WooCommerce', 'woocommerce' ),
		)
	);

	// When only WooCommerce is active, minimize the data to send back to WP.com for supporting Woo Mobile apps.
	$config->ensure(
		'sync',
		array_merge_recursive(
			\Automattic\Jetpack\Sync\Data_Settings::MUST_SYNC_DATA_SETTINGS,
			array(
				'jetpack_sync_modules'           => array(
					'Automattic\\Jetpack\\Sync\\Modules\\Options',
					'Automattic\\Jetpack\\Sync\\Modules\\Full_Sync',
				),
				'jetpack_sync_options_whitelist' => array(
					'active_plugins',
					'blogdescription',
					'blogname',
					'timezone_string',
					'gmt_offset',
				),
			)
		)
	);
}

// Automattic\Jetpack\Connection\Rest_Authentication::init();
// Jetpack-config will initialize the modules on "plugins_loaded" with priority 2, so this code needs to be run before that.
 add_action( 'plugins_loaded', 'wc_jetpack_init', 1 );
//
// if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
// Automattic\Jetpack\Connection\Rest_Authentication::init();
// Automattic\Jetpack\Connection\Manager::configure();
// }
