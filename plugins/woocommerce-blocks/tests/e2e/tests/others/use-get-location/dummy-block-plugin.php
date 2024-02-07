<?php
/**
 * Plugin Name: Dummy block with useGetLocation
 * Description: Registers custom dummy block that uses useGetLocation hook and displays the result in Editor
 * @package     WordPress
 */

/**
 * Registers a custom script for the plugin.
 */
function enqueue_dummy_block_plugin_script() {
	wp_enqueue_script(
		'woocommerce-test-use-get-location',
		plugins_url( 'dummy-block-plugin.js', __FILE__ ),
		array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'wp-i18n',
		),
		filemtime( plugin_dir_path( __FILE__ ) . 'dummy-block-plugin.js' ),
		true
	);
}

add_action( 'init', 'enqueue_dummy_block_plugin_script' );
