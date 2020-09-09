<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://github.com/woocommerce/woocommerce-admin
 * Description: A new JavaScript-driven interface for managing your store. The plugin includes new and improved reports, and a dashboard to monitor all the important key metrics of your site.
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: woocommerce-admin
 * Domain Path: /languages
 * Version: 1.5.0
 * Requires at least: 5.3
 * Requires PHP: 5.6.20
 *
 * WC requires at least: 3.6.0
 * WC tested up to: 4.3.0
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\FeaturePlugin;
use \Automattic\WooCommerce\Admin\Loader;

/**
 * Autoload packages.
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
				esc_html__( 'Your installation of the WooCommerce Admin feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woocommerce' ),
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
						esc_html__( 'Your installation of the WooCommerce Admin feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woocommerce' ),
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

/**
 * Returns whether the current version is a development version
 * Note this relies on composer.json version, not plugin version.
 * Development installs of the plugin don't have a version defined in
 * composer json.
 *
 * @return bool True means the current version is a development version.
 */
function woocommerce_admin_is_development_version() {
	$composer_file = __DIR__ . '/composer.json';
	if ( ! is_readable( $composer_file ) ) {
		return false;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- including local file
	$composer_config = json_decode( file_get_contents( $composer_file ), true );
	return ! isset( $composer_config['version'] );
}

/**
 * Returns true if build file exists.
 *
 * @return bool
 */
function woocommerce_admin_check_build_files() {
	$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$suffix       = Loader::should_use_minified_js_file( $script_debug ) ? '.min' : '';
	return file_exists( __DIR__ . "/dist/app/index{$suffix}.js" );
}

/**
 * If development version is detected and the Jetpack constant is not defined, show a notice.
 */
if ( woocommerce_admin_is_development_version() && ! defined( 'JETPACK_AUTOLOAD_DEV' ) ) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>';
			printf(
				/* Translators: %1$s is referring to a php constant name, %2$s is referring to the wp-config.php file. */
				esc_html__( 'WooCommerce Admin development mode requires the %1$s constant to be defined and true in your %2$s file. Otherwise you are loading the admin package from WooCommerce core.', 'woocommerce' ),
				'<code>JETPACK_AUTOLOAD_DEV</code>',
				'<code>wp-config.php</code>'
			);
			echo '</p></div>';
		}
	);
}

/**
 * If we're missing expected files, notify users that the plugin needs to be built.
 */
if ( ! woocommerce_admin_check_build_files() ) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>';
			printf(
				/* Translators: %1$s, %2$s, and %3$s are all build commands to be run in order.  */
				esc_html__( 'You have installed a development version of WooCommerce Admin which requires files to be built. From the plugin directory, run %1$s and %2$s to install dependencies, then %3$s to build the files.', 'woocommerce' ),
				'<code>composer install</code>',
				'<code>npm install</code>',
				'<code>npm run build</code>'
			);
			printf(
				/* translators: 1: URL of GitHub Repository build page */
				esc_html__( 'Or you can download a pre-built version of the plugin by visiting <a href="%1$s">the releases page in the repository</a>.', 'woocommerce' ),
				'https://github.com/woocommerce/woocommerce-admin/releases'
			);
			echo '</p></div>';
		}
	);
}

FeaturePlugin::instance()->init();
