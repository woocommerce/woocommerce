<?php
/**
 * Plugin Name: WooCommerce Admin Dashboard Section Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function dashboard_section_register_script() {

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ) {
		return;
	}

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'dashboard_section',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'dashboard_section' );
}
add_action( 'admin_enqueue_scripts', 'dashboard_section_register_script' );
