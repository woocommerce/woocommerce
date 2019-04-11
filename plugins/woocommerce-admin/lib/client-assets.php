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
		wc_admin_plugin_url( 'dist/csv-export/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/csv-export/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-currency',
		wc_admin_plugin_url( 'dist/currency/index.js' ),
		array( 'wc-number' ),
		filemtime( wc_admin_dir_path( 'dist/currency/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-navigation',
		wc_admin_plugin_url( 'dist/navigation/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/navigation/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-number',
		wc_admin_plugin_url( 'dist/number/index.js' ),
		array(),
		filemtime( wc_admin_dir_path( 'dist/number/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-date',
		wc_admin_plugin_url( 'dist/date/index.js' ),
		array( 'wp-date', 'wp-i18n' ),
		filemtime( wc_admin_dir_path( 'dist/date/index.js' ) ),
		true
	);

	wp_register_script(
		'wc-components',
		wc_admin_plugin_url( 'dist/components/index.js' ),
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
			'wc-number',
		),
		filemtime( wc_admin_dir_path( 'dist/components/index.js' ) ),
		true
	);

	wp_register_script(
		WC_ADMIN_APP,
		wc_admin_plugin_url( "dist/{$entry}/index.js" ),
		array( 'wc-components', 'wc-navigation', 'wp-date', 'wp-html-entities', 'wp-keycodes', 'wp-i18n' ),
		filemtime( wc_admin_dir_path( "dist/{$entry}/index.js" ) ),
		true
	);

	// Set up the text domain and translations.
	// The old way (pre WP5 RC1).
	if ( function_exists( 'wp_get_jed_locale_data' ) ) {
		$locale_data = wp_get_jed_locale_data( 'woocommerce-admin' );
	} elseif ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
		$locale_data = gutenberg_get_jed_locale_data( 'woocommerce-admin' );
	} else {
		$locale_data = '';
	}
	if ( ! empty( $locale_data ) ) {
		$content = 'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ', "woocommerce-admin" );';
		wp_add_inline_script( 'wp-i18n', $content, 'after' );
	}
	// The new way (WP5 RC1 and later).
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( WC_ADMIN_APP, 'woocommerce-admin' );
	}

	wp_register_style(
		'wc-components',
		wc_admin_plugin_url( 'dist/components/style.css' ),
		array( 'wp-edit-blocks' ),
		filemtime( wc_admin_dir_path( 'dist/components/style.css' ) )
	);
	wp_style_add_data( 'wc-components', 'rtl', 'replace' );

	wp_register_style(
		'wc-components-ie',
		wc_admin_plugin_url( 'dist/components/ie.css' ),
		array( 'wp-edit-blocks' ),
		filemtime( wc_admin_dir_path( 'dist/components/ie.css' ) )
	);
	wp_style_add_data( 'wc-components-ie', 'rtl', 'replace' );

	wp_register_style(
		WC_ADMIN_APP,
		wc_admin_plugin_url( "dist/{$entry}/style.css" ),
		array( 'wc-components' ),
		filemtime( wc_admin_dir_path( "dist/{$entry}/style.css" ) )
	);
	wp_style_add_data( WC_ADMIN_APP, 'rtl', 'replace' );
}
add_action( 'admin_enqueue_scripts', 'wc_admin_register_script' );

/**
 * Format order statuses by removing a leading 'wc-' if present.
 *
 * @param array $statuses Order statuses.
 * @return array formatted statuses.
 */
function wc_admin_format_order_statuses( $statuses ) {
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
	global $wp_locale;

	$preload_data_endpoints = array(
		'countries'             => '/wc/v4/data/countries',
		'performanceIndicators' => '/wc/v4/reports/performance-indicators/allowed',
		'leaderboards'          => '/wc/v4/leaderboards/allowed',
	);

	if ( function_exists( 'gutenberg_preload_api_request' ) ) {
		$preload_function = 'gutenberg_preload_api_request';
	} else {
		$preload_function = 'rest_preload_api_request';
	}

	$preload_data = array_reduce(
		array_values( $preload_data_endpoints ),
		$preload_function
	);

	$current_user_data = array();
	foreach ( wc_admin_get_user_data_fields() as $user_field ) {
		$current_user_data[ $user_field ] = json_decode( get_user_meta( get_current_user_id(), 'wc_admin_' . $user_field, true ) );
	}

	/**
	 * Settings and variables can be passed here for access in the app.
	 *
	 * @todo On merge, once plugin images are added to core WooCommerce, `wcAdminAssetUrl` can be retired, and
	 * `wcAssetUrl` can be used in its place throughout the codebase.
	 */
	$settings = array(
		'adminUrl'          => admin_url(),
		'wcAssetUrl'        => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'wcAdminAssetUrl'   => plugins_url( 'images/', wc_admin_dir_path( 'wc-admin.php' ) ), // Temporary for plugin. See above.
		'embedBreadcrumbs'  => wc_admin_get_embed_breadcrumbs(),
		'siteLocale'        => esc_attr( get_bloginfo( 'language' ) ),
		'currency'          => wc_admin_currency_settings(),
		'orderStatuses'     => wc_admin_format_order_statuses( wc_get_order_statuses() ),
		'stockStatuses'     => wc_get_product_stock_status_options(),
		'siteTitle'         => get_bloginfo( 'name' ),
		'dataEndpoints'     => array(),
		'l10n'              => array(
			'userLocale'    => get_user_locale(),
			'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
		),
		'currentUserData'   => $current_user_data,
		'alertCount'        => WC_Admin_Notes::get_notes_count( array( 'error', 'update' ), array( 'unactioned' ) ),
		'reviewsEnabled'    => get_option( 'woocommerce_enable_reviews' ),
		'manageStock'       => get_option( 'woocommerce_manage_stock' ),
		'commentModeration' => get_option( 'comment_moderation' ),
	);
	$settings = wc_admin_add_custom_settings( $settings );

	foreach ( $preload_data_endpoints as $key => $endpoint ) {
		$settings['dataEndpoints'][ $key ] = $preload_data[ $endpoint ]['body'];
	}

	$settings = apply_filters( 'wc_admin_wc_settings', $settings );
	?>
	<script type="text/javascript">
		var wcSettings = <?php echo wp_json_encode( $settings ); ?>;
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'wc_admin_print_script_settings', 1 );

/**
 * Add in custom settings used for WC Admin.
 *
 * @param array $settings Array of settings to merge into.
 * @return array
 */
function wc_admin_add_custom_settings( $settings ) {
	$wc_rest_settings_options_controller = new WC_REST_Setting_Options_Controller();
	$wc_admin_group_settings             = $wc_rest_settings_options_controller->get_group_settings( 'wc_admin' );
	$settings['wcAdminSettings']         = array();

	foreach ( $wc_admin_group_settings as $setting ) {
		$settings['wcAdminSettings'][ $setting['id'] ] = $setting['value'];
	}
	return $settings;
}

/**
 * Load plugin text domain for translations.
 */
function wc_admin_load_plugin_textdomain() {
	load_plugin_textdomain( 'woocommerce-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wc_admin_load_plugin_textdomain' );
