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
 * API feature plugin version.
 *
 * @internal This version needs incrementing when releasing new versions of the API.
 */
$version = '1.1.0';

/**
 * This callback loads this version of the API.
 */
$init_callback = function() {
	require __DIR__ . '/vendor/autoload.php';
	\WooCommerce\RestApi::instance()->init();
};

/**
 * This callback registers this version of the API with WooCommerce.
 */
$register_callback = function() use ( $version, $init_callback ) {
	if ( ! is_callable( array( wc()->api, 'register' ) ) ) {
		return;
	}
	wc()->api->register( $version, $init_callback );
};

add_action( 'woocommerce_loaded', $register_callback );
