<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 4.1.1
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

/**
 * Load core packages and the autoloader.
 *
 * The new packages and autoloader require PHP 5.6+. If this dependency is not met, do not include them. Users will be warned
 * that they are using an older version of PHP. WooCommerce will continue to load, but some functionality such as the REST API
 * and Blocks will be missing.
 *
 * This requirement will be enforced in future versions of WooCommerce.
 */
if ( version_compare( PHP_VERSION, '5.6.0', '>=' ) ) {
	require __DIR__ . '/src/Autoloader.php';
	require __DIR__ . '/src/Packages.php';

	if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
		return;
	}
	\Automattic\WooCommerce\Packages::init();
}

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();
