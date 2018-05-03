<?php

/**
 * Registers the JS & CSS for the Dashboard
 */
function woo_dash_register_script() {
	wp_register_script(
		WOO_DASH_APP,
		woo_dash_url( 'js/index.js' ),
		[ 'wp-blocks', 'wp-element', 'wp-i18n' ],
		filemtime( woo_dash_dir_path( '/js/index.js' ) ),
		true
	);

	wp_register_style(
		WOO_DASH_APP,
		woo_dash_url( 'js/style.css' ),
		[ 'wp-components', 'wp-blocks', 'wp-edit-blocks' ],
		filemtime( woo_dash_dir_path( '/js/index.js' ) )
	);

	// Set up the text domain and translations
	$locale_data = gutenberg_get_jed_locale_data( 'woo-dash' );
	$content = 'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "woo-dash" );';
	wp_add_inline_script( WOO_DASH_APP, $content, 'before' );

	wp_enqueue_script( 'wp-api' );
	gutenberg_extend_wp_api_backbone_client();
}
add_action( 'init', 'woo_dash_register_script' );

/**
 * Load plugin text domain for translations.
 */
function woo_dash_load_plugin_textdomain() {
	load_plugin_textdomain( 'woo-dash', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'woo_dash_load_plugin_textdomain' );
