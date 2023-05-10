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

	// Load admin.
	require 'plugin.php';
}

add_action( 'plugins_loaded', '_woo_ai_bootstrap' );

/**
 * Register the JS.
 */
function add_woo_ai_register_script() {
	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_path ),
		);
	$script_url        = plugins_url( $script_path, __FILE__ );

	$script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

	wp_register_script(
		'woo-ai',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);
	wp_enqueue_script( 'woo-ai' );

	$css_file_version = filemtime( dirname( __FILE__ ) . '/build/index.css' );

	wp_register_style(
		'wp-components',
		plugins_url( 'dist/components/style.css', __FILE__ ),
		array(),
		$css_file_version
	);

	wp_register_style(
		'woo-ai',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(
			'wp-components',
		),
		$css_file_version
	);

	wp_enqueue_style( 'woo-ai' );
}

add_action( 'admin_enqueue_scripts', 'add_woo_ai_register_script' );
