<?php
/**
 * Plugin Name: WooCommerce Admin Add Abbreviated Notification Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function add_abbreviated_notification_register_script() {
	if (
		! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) ||
		! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page()
	) {
		return;
	}

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'add-abbreviated-notification',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'add-abbreviated-notification' );
}
add_action( 'admin_enqueue_scripts', 'add_abbreviated_notification_register_script' );
