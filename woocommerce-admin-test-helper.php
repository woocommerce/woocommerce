<?php
/**
 * Plugin Name: WooCommerce Admin Test Helper
 * Plugin URI: https://github.com/woocommerce/woocommerce-admin-test-helper
 * Description: A helper plugin to assist with testing WooCommerce Admin
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Version: 0.1.0-dev
 *
 * @package WooCommerce\Admin\TestHelper
 */

/**
 * Register the JS.
 */
function add_extension_register_script() {
	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ) {
		return;
	}
	
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

	wp_register_style(
		'woocommerce-admin-test-helper',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(),
		filemtime( dirname( __FILE__ ) . '/build/index.css' )
	);

	wp_enqueue_script( 'woocommerce-admin-test-helper' );
	wp_enqueue_style( 'woocommerce-admin-test-helper' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );

// Load the plugin
require( 'plugin.php' );
