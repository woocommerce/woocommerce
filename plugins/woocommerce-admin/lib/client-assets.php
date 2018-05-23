<?php

/**
 * Registers the JS & CSS for the Dashboard
 */
function woo_dash_register_script() {
	wp_register_script(
		WOO_DASH_APP,
		woo_dash_url( 'dist/index.js' ),
		[ 'wp-components', 'wp-blocks', 'wp-element', 'wp-i18n' ],
		filemtime( woo_dash_dir_path( 'dist/index.js' ) ),
		true
	);

	wp_register_style(
		WOO_DASH_APP,
		woo_dash_url( 'dist/css/style.css' ),
		[ 'wp-edit-blocks' ],
		filemtime( woo_dash_dir_path( 'dist/css/style.css' ) )
	);

	// Set up the text domain and translations
	$locale_data = gutenberg_get_jed_locale_data( 'woo-dash' );
	$content = 'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "woo-dash" );';
	wp_add_inline_script( WOO_DASH_APP, $content, 'before' );

	wp_enqueue_script( 'wp-api' );
	gutenberg_extend_wp_api_backbone_client();

	// Settings and variables can be passed here for access in the app
	$settings = array(
		'adminUrl' => admin_url(),
	);
	wp_add_inline_script(
		WOO_DASH_APP,
		'var wcSettings = '. json_encode( $settings ) . ';',
		'before'
	);

	// Resets lodash to wp-admin's version of lodash
	wp_add_inline_script(
		WOO_DASH_APP,
		'_.noConflict();',
		'after'
	);

}
add_action( 'init', 'woo_dash_register_script' );

/**
 * Load plugin text domain for translations.
 */
function woo_dash_load_plugin_textdomain() {
	load_plugin_textdomain( 'woo-dash', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'woo_dash_load_plugin_textdomain' );
