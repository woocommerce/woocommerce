<?php
/**
 * Plugin Name: Test useGetLocation hook
 * Description: Registers custom dummy block that uses useGetLocation hook and displays the result in Editor
 * @package     WordPress
 */

/**
 * Registers a custom script for the plugin.
 */
function enqueue_dummy_block_plugin_script() {
	wp_enqueue_script(
		'woocommerce-test-use-get-location',
		plugins_url( 'use-get-location-block.js', __FILE__ ),
		array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'@woocommerce/base-hooks',
		),
		filemtime( plugin_dir_path( __FILE__ ) . 'use-get-location-block.js' ),
		true
	);
}

add_action( 'init', 'enqueue_dummy_block_plugin_script' );
