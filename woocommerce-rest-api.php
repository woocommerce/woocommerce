<?php
/**
 * Plugin Name: WooCommerce REST API
 * Plugin URI: https://github.com/woocommerce/woocommerce-rest-api
 * Description: The WooCommerce core REST API, installed as a feature plugin for development and testing purposes. Requires WooCommerce 3.7+ and PHP 5.3+.
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Version: 1.0.0-dev
 * Requires PHP: 5.3
 * License: GPLv3
 *
 * @package WooCommerce/RestAPI
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	return;
}

if ( ! function_exists( 'wc_rest_api_1_dot_0_dot_0_dev' ) ) {
	/**
	 * This is a version specific loader used as a callback so only the latest version of the API plugin is used.
	 *
	 * @internal Never call manually - this function will change between versions.
	 */
	function wc_rest_api_1_dot_0_dot_0_dev() {
		require_once dirname( __FILE__ ) . '/src/class-server.php';
		\WooCommerce\Rest_Api\Server::instance()->init();
	}
}

add_action(
	'woocommerce_loaded',
	function() {
		if ( is_callable( array( wc()->api, 'register' ) ) ) {
			// @internal When bumping the version remember to update the function names below.
			wc()->api->register( '1.1.0', 'wc_rest_api_1_dot_0_dot_0_dev' );
		}
	}
);
