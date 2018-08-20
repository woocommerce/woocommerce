<?php
/**
 * Register javascript & css files.
 *
 * @package WC_Admin
 */

/**
 * Registers the JS & CSS for the admin and admin embed
 */
function wc_admin_register_script() {
	// Are we displaying the full React app or just embedding the header on a classic screen?
	$screen_id = wc_admin_get_current_screen_id();

	if ( in_array( $screen_id, wc_admin_get_embed_enabled_screen_ids() ) ) {
		$js_entry  = 'dist/embedded.js';
		$css_entry = 'dist/css/embedded.css';
	} else {
		$js_entry  = 'dist/index.js';
		$css_entry = 'dist/css/index.css';
	}

	wp_register_script(
		'wc-components',
		wc_admin_url( 'dist/components.js' ),
		[ 'wp-components', 'wp-data', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-keycodes' ],
		filemtime( wc_admin_dir_path( 'dist/components.js' ) ),
		true
	);

	wp_register_style(
		'wc-components',
		wc_admin_url( 'dist/css/components.css' ),
		[ 'wp-edit-blocks' ],
		filemtime( wc_admin_dir_path( 'dist/css/components.css' ) )
	);

	wp_register_script(
		WC_ADMIN_APP,
		wc_admin_url( $js_entry ),
		[ 'wc-components', 'wp-date', 'wp-html-entities', 'wp-keycodes' ],
		filemtime( wc_admin_dir_path( $js_entry ) ),
		true
	);

	wp_register_style(
		WC_ADMIN_APP,
		wc_admin_url( $css_entry ),
		[ 'wc-components' ],
		filemtime( wc_admin_dir_path( $css_entry ) )
	);

	// Set up the text domain and translations.
	$locale_data = gutenberg_get_jed_locale_data( 'wc-admin' );
	$content     = 'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "wc-admin" );';
	wp_add_inline_script( 'wc-components', $content, 'before' );

	wp_enqueue_script( 'wp-api' );

	// Settings and variables can be passed here for access in the app.
	$settings = array(
		'adminUrl'         => admin_url(),
		'wcAssetUrl'       => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'embedBreadcrumbs' => wc_admin_get_embed_breadcrumbs(),
		'siteLocale'       => esc_attr( get_bloginfo( 'language' ) ),
		'currency'         => wc_admin_currency_settings(),
		'date'             => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
		'orderStatuses'    => wc_get_order_statuses(),
		'siteTitle'        => get_bloginfo( 'name' ),
	);

	wp_add_inline_script(
		'wc-components',
		'var wcSettings = ' . json_encode( $settings ) . ';',
		'before'
	);

	// Resets lodash to wp-admin's version of lodash.
	wp_add_inline_script(
		WC_ADMIN_APP,
		'_.noConflict();',
		'after'
	);

}
add_action( 'admin_enqueue_scripts', 'wc_admin_register_script' );

/**
 * Load plugin text domain for translations.
 */
function wc_admin_load_plugin_textdomain() {
	load_plugin_textdomain( 'wc-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wc_admin_load_plugin_textdomain' );
