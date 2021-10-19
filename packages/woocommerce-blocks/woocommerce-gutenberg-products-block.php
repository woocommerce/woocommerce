<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 6.1.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * Requires at least: 5.8
 * Requires PHP: 7.0
 * WC requires at least: 5.7
 * WC tested up to: 5.7
 *
 * @package WooCommerce\Blocks
 * @internal This file is only used when running as a feature plugin.
 */

defined( 'ABSPATH' ) || exit;

$minimum_wp_version = '5.8';

if ( ! defined( 'WC_BLOCKS_IS_FEATURE_PLUGIN' ) ) {
	define( 'WC_BLOCKS_IS_FEATURE_PLUGIN', true );
}
/**
 * Whether notices must be displayed in the current page (plugins and WooCommerce pages).
 *
 * @since 2.5.0
 */
function should_display_compatibility_notices() {
	$current_screen = get_current_screen();

	if ( ! isset( $current_screen ) ) {
		return false;
	}

	$is_plugins_page     =
		property_exists( $current_screen, 'id' ) &&
		'plugins' === $current_screen->id;
	$is_woocommerce_page =
		property_exists( $current_screen, 'parent_base' ) &&
		'woocommerce' === $current_screen->parent_base;

	return $is_plugins_page || $is_woocommerce_page;
}

if ( version_compare( $GLOBALS['wp_version'], $minimum_wp_version, '<' ) ) {
	/**
	 * Outputs for an admin notice about running WooCommerce Blocks on outdated WordPress.
	 *
	 * @since 2.5.0
	 */
	function woocommerce_blocks_admin_unsupported_wp_notice() {
		if ( should_display_compatibility_notices() ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'The WooCommerce Blocks feature plugin requires a more recent version of WordPress and has been paused. Please update WordPress to continue enjoying WooCommerce Blocks.', 'woocommerce' ); ?></p>
			</div>
			<?php
		}
	}
	add_action( 'admin_notices', 'woocommerce_blocks_admin_unsupported_wp_notice' );
	return;
}

/**
 * Returns whether the current version is a development version
 * Note this relies on composer.json version, not plugin version.
 * Development installs of the plugin don't have a version defined in
 * composer json.
 *
 * @return bool True means the current version is a development version.
 */
function woocommerce_blocks_is_development_version() {
	$composer_file = __DIR__ . '/composer.json';
	if ( ! is_readable( $composer_file ) ) {
		return false;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- including local file
	$composer_config = json_decode( file_get_contents( $composer_file ), true );
	return ! isset( $composer_config['version'] );
}

/**
 * If development version is detected and the Jetpack constant is not defined, show a notice.
 */
if ( woocommerce_blocks_is_development_version() && ! defined( 'JETPACK_AUTOLOAD_DEV' ) ) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>';
			printf(
				/* translators: %1$s is referring to a php constant name, %2$s is referring to the wp-config.php file. */
				esc_html__( 'WooCommerce Blocks development mode requires the %1$s constant to be defined and true in your %2$s file. Otherwise you are loading the blocks package from WooCommerce core.', 'woocommerce' ),
				'JETPACK_AUTOLOAD_DEV',
				'wp-config.php'
			);
			echo '</p></div>';
		}
	);
}


/**
 * Autoload packages.
 *
 * The package autoloader includes version information which prevents classes in this feature plugin
 * conflicting with WooCommerce core.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload_packages.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the WooCommerce Blocks feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woocommerce' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the WooCommerce Blocks feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woocommerce' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

add_action( 'plugins_loaded', array( '\Automattic\WooCommerce\Blocks\Package', 'init' ) );

/**
 * Pre-filters script translations for the given file, script handle and text domain.
 *
 * @param string|false|null $translations JSON-encoded translation data. Default null.
 * @param string|false      $file         Path to the translation file to load. False if there isn't one.
 * @param string            $handle       Name of the script to register a translation domain to.
 * @param string            $domain       The text domain.
 * @return string JSON translations.
 */
function woocommerce_blocks_get_i18n_data_json( $translations, $file, $handle, $domain ) {
	if ( 'woo-gutenberg-products-block' !== $domain ) {
		return $translations;
	}

	global $wp_scripts;

	if ( ! isset( $wp_scripts->registered[ $handle ], $wp_scripts->registered[ $handle ]->src ) ) {
		return $translations;
	}

	$handle_filename = basename( $wp_scripts->registered[ $handle ]->src );
	$locale          = determine_locale();
	$lang_dir        = WP_LANG_DIR . '/plugins';

	// Translations are always based on the unminified filename.
	if ( substr( $handle_filename, -7 ) === '.min.js' ) {
		$handle_filename = substr( $handle_filename, 0, -7 ) . '.js';
	}

	// WordPress 5.0 uses md5 hashes of file paths to associate translation
	// JSON files with the file they should be included for. This is an md5
	// of 'packages/woocommerce-blocks/build/FILENAME.js'.
	$core_path_md5     = md5( 'packages/woocommerce-blocks/build/' . $handle_filename );
	$core_json_file    = $lang_dir . '/woocommerce-' . $locale . '-' . $core_path_md5 . '.json';
	$json_translations = is_file( $core_json_file ) && is_readable( $core_json_file ) ? file_get_contents( $core_json_file ) : false; // phpcs:ignore

	if ( ! $json_translations ) {
		return $translations;
	}

	// Rather than short circuit pre_load_script_translations, we will output
	// core translations using an inline script. This will allow us to continue
	// to load feature-plugin translations which may exist as well.
	$output = <<<JS
	( function( domain, translations ) {
		var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
		localeData[""].domain = domain;
		wp.i18n.setLocaleData( localeData, domain );
	} )( "{$domain}", {$json_translations} );
JS;

	printf( "<script type='text/javascript'>\n%s\n</script>\n", $output ); // phpcs:ignore

	// Finally, short circuit the pre_load_script_translations hook by returning
	// the translation JSON from the feature plugin, if it exists so this hook
	// does not run again for the current handle.
	$path_md5     = md5( 'build/' . $handle_filename );
	$json_file    = $lang_dir . '/' . $domain . '-' . $locale . '-' . $path_md5 . '.json';
	$translations = is_file( $json_file ) && is_readable( $json_file ) ? file_get_contents( $json_file ) : false; // phpcs:ignore

	if ( $translations ) {
		return $translations;
	}

	// Return valid empty Jed locale.
	return '{ "locale_data": { "messages": { "": {} } } }';
}

add_filter( 'pre_load_script_translations', 'woocommerce_blocks_get_i18n_data_json', 10, 4 );

/**
 * Filter translations so we can retrieve translations from Core when the original and the translated
 * texts are the same (which happens when translations are missing).
 *
 * @param string $translation Translated text based on WC Blocks translations.
 * @param string $text        Text to translate.
 * @param string $domain      The text domain.
 * @return string WC Blocks translation. In case it's the same as $text, Core translation.
 */
function woocommerce_blocks_get_php_translation_from_core( $translation, $text, $domain ) {
	if ( 'woo-gutenberg-products-block' !== $domain ) {
		return $translation;
	}

	// When translation is the same, that could mean the string is not translated.
	// In that case, load it from core.
	if ( $translation === $text ) {
		return translate( $text, 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.TextDomainMismatch
	}
	return $translation;
}

add_filter( 'gettext', 'woocommerce_blocks_get_php_translation_from_core', 10, 3 );
