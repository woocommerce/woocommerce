<?php
/**
 * Plugin Name: WooCommerce REST API
 * Plugin URI: https://github.com/woocommerce/woocommerce-rest-api
 * Description: The WooCommerce core REST API, installed as a feature plugin for development and testing purposes. Requires WooCommerce 3.7+ and PHP 5.3+.
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Version: 1.0.0-dev
 * Requires PHP: 5.6
 * License: GPLv3
 *
 * @package WooCommerce/RestApi
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

/**
 * Get API feature plugin version and callback function.
 */
$version       = include __DIR__ . '/version.php';
$init_callback = include __DIR__ . '/init.php';

/**
 * This callback registers this version of the API with WooCommerce.
 */
$register_callback = function() use ( $version, $init_callback ) {
	if ( ! class_exists( '\WooCommerce\Core\PackageManager' ) ) {
		return;
	}
	\WooCommerce\Core\PackageManager::register( 'woocommerce-rest-api', $version, $init_callback, __DIR__ );
};

add_action( 'woocommerce_loaded', $register_callback );
