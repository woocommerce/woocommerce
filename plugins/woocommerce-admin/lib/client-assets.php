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

	if ( in_array( $screen_id, wc_admin_get_embed_enabled_screen_ids(), true ) ) {
		$entry = 'embedded';
	} else {
		$entry = 'app';
	}

	wp_register_script(
		'wc-csv',
		wc_admin_url( 'dist/csv-export/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/csv-export/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-currency',
		wc_admin_url( 'dist/currency/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/currency/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-navigation',
		wc_admin_url( 'dist/navigation/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/navigation/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-date',
		wc_admin_url( 'dist/date/index.js' ),
		array( 'wp-date', 'wp-i18n' ),
		filemtime( wc_admin_dir_path( 'dist/date/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-components',
		wc_admin_url( 'dist/components/index.js' ),
		array(
			'wp-components',
			'wp-data',
			'wp-element',
			'wp-hooks',
			'wp-i18n',
			'wp-keycodes',
			'wc-csv',
			'wc-currency',
			'wc-date',
			'wc-navigation',
		),
		filemtime( wc_admin_dir_path( 'dist/components/index.js' ) ),
		true
	);

	wp_register_script(
		WC_ADMIN_APP,
		wc_admin_url( "dist/{$entry}/index.js" ),
		array( 'wc-components', 'wc-navigation', 'wp-date', 'wp-html-entities', 'wp-keycodes', 'wp-i18n' ),
		filemtime( wc_admin_dir_path( "dist/{$entry}/index.js" ) ),
		true
	);

	// Set up the text domain and translations.
	// The old way (pre WP5 RC1).
	if ( function_exists( 'wp_get_jed_locale_data' ) ) {
		$locale_data = wp_get_jed_locale_data( 'wc-admin' );
	} elseif ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
		$locale_data = gutenberg_get_jed_locale_data( 'wc-admin' );
	} else {
		$locale_data = '';
	}
	if ( ! empty( $locale_data ) ) {
		$content = 'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ', "wc-admin" );';
		wp_add_inline_script( 'wp-i18n', $content, 'after' );
	}
	// The new way (WP5 RC1 and later).
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( WC_ADMIN_APP, 'wc-admin' );
	}

	wp_register_style(
		'wc-components',
		wc_admin_url( 'dist/components/style.css' ),
		array( 'wp-edit-blocks' ),
		filemtime( wc_admin_dir_path( 'dist/components/style.css' ) )
	);

	wp_register_style(
		WC_ADMIN_APP,
		wc_admin_url( "dist/{$entry}/style.css" ),
		array( 'wc-components' ),
		filemtime( wc_admin_dir_path( "dist/{$entry}/style.css" ) )
	);
}
add_action( 'admin_enqueue_scripts', 'wc_admin_register_script' );

/**
 * Format order statuses by removing a leading 'wc-' if present.
 *
 * @param array $statuses Order statuses.
 * @return array formatted statuses.
 */
function format_order_statuses( $statuses ) {
	$formatted_statuses = array();
	foreach ( $statuses as $key => $value ) {
		$formatted_key                        = preg_replace( '/^wc-/', '', $key );
		$formatted_statuses[ $formatted_key ] = $value;
	}
	return $formatted_statuses;
}

/**
 * Output the wcSettings global before printing any script tags.
 */
function wc_admin_print_script_settings() {
	// Add Tracks script to the DOM if tracking is opted in, and Jetpack is installed/activated.
	$tracking_enabled = 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	$tracking_script  = '';
	if ( $tracking_enabled && defined( 'JETPACK__VERSION' ) ) {
		$tracking_script  = "var wc_tracking_script = document.createElement( 'script' );\n";
		$tracking_script .= "wc_tracking_script.src = '//stats.wp.com/w.js';\n"; // TODO Version/cache buster.
		$tracking_script .= "wc_tracking_script.type = 'text/javascript';\n";
		$tracking_script .= "wc_tracking_script.async = true;\n";
		$tracking_script .= "wc_tracking_script.defer = true;\n";
		$tracking_script .= "window._tkq = window._tkq || [];\n";
		$tracking_script .= "document.head.appendChild( wc_tracking_script );\n";
	}
	/**
	 * TODO: On merge, once plugin images are added to core WooCommerce, `wcAdminAssetUrl` can be retired, and
	 * `wcAssetUrl` can be used in its place throughout the codebase.
	 */

	// Settings and variables can be passed here for access in the app.
	$settings = array(
		'adminUrl'         => admin_url(),
		'wcAssetUrl'       => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'wcAdminAssetUrl'  => plugins_url( 'images/', wc_admin_dir_path( 'wc-admin.php' ) ), // Temporary for plugin. See above.
		'embedBreadcrumbs' => wc_admin_get_embed_breadcrumbs(),
		'siteLocale'       => esc_attr( get_bloginfo( 'language' ) ),
		'currency'         => wc_admin_currency_settings(),
		'date'             => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
		'orderStatuses'    => format_order_statuses( wc_get_order_statuses() ),
		'stockStatuses'    => wc_get_product_stock_status_options(),
		'siteTitle'        => get_bloginfo( 'name' ),
		'trackingEnabled'  => $tracking_enabled,
	);
	?>
	<script type="text/javascript">
		<?php
		echo $tracking_script; // WPCS: XSS ok.
		?>
		var wcSettings = <?php echo wp_json_encode( $settings ); ?>;
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'wc_admin_print_script_settings', 1 );

/**
 * Load plugin text domain for translations.
 */
function wc_admin_load_plugin_textdomain() {
	load_plugin_textdomain( 'wc-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wc_admin_load_plugin_textdomain' );
