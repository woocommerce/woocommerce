<?php
/**
 * Plugin Name: WooCommerce Admin Add Report Example
 *
 * @package WC_Admin
 */

/**
 * Register the JS.
 */
function add_report_register_script() {

	if ( ! class_exists( 'WC_Admin_Loader' ) || ! WC_Admin_Loader::is_admin_page() ) {
		return;
	}

	wp_register_script(
		'add-report',
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

	wp_enqueue_script( 'add-report' );
}
add_action( 'admin_enqueue_scripts', 'add_report_register_script' );
