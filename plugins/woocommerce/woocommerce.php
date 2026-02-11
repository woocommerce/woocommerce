<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 10.6.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.8
 * Requires PHP: 7.4
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
require __DIR__ . '/src/Internal/FileManifest.php';

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid, Squiz.Commenting.FunctionComment.Missing

if ( ! \Automattic\WooCommerce\Internal\FileManifest::verify_installation( __FILE__ ) ) {
	/** @return null */
	function WC() {
		return null;
	}
	return;
}

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	/** @return null */
	function WC() {
		return null;
	}
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// If the class still doesn't exist the file failed to compile, treat it as an incomplete installation.
if ( ! class_exists( 'WooCommerce', false ) ) {
	\Automattic\WooCommerce\Internal\FileManifest::incomplete_installation( __FILE__ );
	/** @return null */
	function WC() {
		return null;
	}
	return;
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

if ( ! function_exists( 'WC' ) ) {
	/**
	 * Returns the main instance of WC.
	 *
	 * @since  2.1
	 * @return WooCommerce
	 */
	function WC() {
		return WooCommerce::instance();
	}
}

// phpcs:enable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid, Squiz.Commenting.FunctionComment.Missing

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

// Jetpack's Rest_Authentication needs to be initialized even before plugins_loaded.
if ( class_exists( \Automattic\Jetpack\Connection\Rest_Authentication::class ) ) {
	\Automattic\Jetpack\Connection\Rest_Authentication::init();
}
