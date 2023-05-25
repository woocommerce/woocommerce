<?php
/**
 * Plugin Name: Woo AI
 * Plugin URI: https://github.com/woocommerce/woo-ai
 * Description: Enable AI experiments within the WooCommerce experience.
 * Version: 0.1.0
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 5.8
 * Tested up to: 6.0
 * WC requires at least: 6.7
 * WC tested up to: 7.0
 * Text Domain: woo-ai
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

// If the key is not defined, don't load the plugin.
if ( ! defined( 'OPEN_AI_KEY' ) || ! OPEN_AI_KEY ) {
	return;
}

// Define WOO_AI_FILE.
if ( ! defined( 'WOO_AI_FILE' ) ) {
	define( 'WOO_AI_FILE', __FILE__ );
}

/**
 * Load text domain before all other code.
 *
 * @since 2.0.0
 */
function _woo_ai_load_textdomain() {
	load_plugin_textdomain( 'woo-ai', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', '_woo_ai_load_textdomain' );

/**
 * Bootstrap plugin.
 */
function _woo_ai_bootstrap() {

	// Check if WooCommerce is enabled.
	if ( ! class_exists( 'WooCommerce' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai-admin-notices.php';
		$notices = new Woo_AI_Admin_Notices();

		add_action( 'admin_notices', array( $notices, 'woocoommerce_not_installed' ) );
	} elseif ( ! class_exists( 'Woo_AI' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai.php';

		register_activation_hook( __FILE__, array( 'Woo_AI', 'activate' ) );

		add_action( 'admin_init', array( 'Woo_AI', 'instance' ) );
	}

}

add_action(
	'wp_loaded',
	function () {
		require 'api/api.php';
	}
);

add_action( 'plugins_loaded', '_woo_ai_bootstrap' );
