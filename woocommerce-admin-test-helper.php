<?php
/**
 * Plugin Name: WooCommerce Admin Test Helper
 * Plugin URI: https://github.com/woocommerce/woocommerce-admin-test-helper
 * Description: A helper plugin to assist with testing WooCommerce Admin
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Version: 0.7.0
 *
 * @package WooCommerce\Admin\TestHelper
 */

define( 'WC_ADMIN_TEST_HELPER_PLUGIN_FILE', 'woocommerce-admin-test-helper/woocommerce-admin-test-helper.php' );

/**
 * Register the JS.
 */
function add_extension_register_script() {
	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require( $script_asset_path )
		: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
	$script_url = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'woocommerce-admin-test-helper',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);
	wp_enqueue_script( 'woocommerce-admin-test-helper' );

	$css_file_version = filemtime( dirname( __FILE__ ) . '/build/index.css' );

	wp_register_style(
		'wp-components',
		plugins_url( 'dist/components/style.css', WC_ADMIN_TEST_HELPER_PLUGIN_FILE ),
		array(),
		$css_file_version
	);

	wp_register_style(
		'woocommerce-admin-test-helper',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(
			'wp-components'
		),
		$css_file_version
	);

	wp_enqueue_style( 'woocommerce-admin-test-helper' );
}

add_action( 'plugins_loaded', function() {
	if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
		return;
	}
	
	add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );

	// Load the plugin
	require( 'plugin.php' );
});


