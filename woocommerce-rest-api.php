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

$autoloader = __DIR__ . '/vendor/autoload_packages.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
}
