<?php
/**
 * Plugin Name: WooCommerce Admin Dashboard Section Example
 *
 * @package WC_Admin
 */

/**
 * Register the JS.
 */
function dashboard_section_register_script() {

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ) {
		return;
	}

	wp_register_script(
		'dashboard_section',
		plugins_url( '/dist/index.js', __FILE__ ),
		array(
			'wp-hooks',
			'wp-element',
			'wp-i18n',
			'wc-components',
		),
		filemtime( dirname( __FILE__ ) . '/dist/index.js' ),
		true
	);

	wp_enqueue_script( 'dashboard_section' );
}
add_action( 'admin_enqueue_scripts', 'dashboard_section_register_script' );
