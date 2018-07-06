<?php
/**
 * Registers the JS & CSS for the Dashboard
 */
function woo_dash_register_script() {
	// Are we displaying the full React app or just embedding the header on a classic screen?
	$screen_id = woo_dash_get_current_screen_id();

	if ( in_array( $screen_id, woo_dash_get_embed_enabled_screen_ids() ) ) {
		$js_entry = 'dist/embedded.js';
		$css_entry = 'dist/css/embedded.css';
	} else {
		$js_entry = 'dist/index.js';
		$css_entry = 'dist/css/index.css';
	}

	wp_register_script(
		WOO_DASH_APP,
		woo_dash_url( $js_entry ),
		[ 'wp-components', 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-date' ],
		filemtime( woo_dash_dir_path( $js_entry ) ),
		true
	);

	wp_register_style(
		WOO_DASH_APP,
		woo_dash_url( $css_entry ),
		[ 'wp-edit-blocks' ],
		filemtime( woo_dash_dir_path( $css_entry ) )
	);

	// Set up the text domain and translations
	$locale_data = gutenberg_get_jed_locale_data( 'woo-dash' );
	$content = 'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "woo-dash" );';
	wp_add_inline_script( WOO_DASH_APP, $content, 'before' );

	wp_enqueue_script( 'wp-api' );
	gutenberg_extend_wp_api_backbone_client();

	// Settings and variables can be passed here for access in the app
	$settings = array(
		'adminUrl'           => admin_url(),
		'embedBreadcrumbs'   => woo_dash_get_embed_breadcrumbs(),
		'siteLocale'   => esc_attr( get_bloginfo( 'language' ) ),
		'currency'  => woo_dash_currency_settings(),
		'date' => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
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
add_action( 'admin_enqueue_scripts', 'woo_dash_register_script' );

/**
 * Load plugin text domain for translations.
 */
function woo_dash_load_plugin_textdomain() {
	load_plugin_textdomain( 'woo-dash', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'woo_dash_load_plugin_textdomain' );
