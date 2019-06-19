<?php
/**
 * WooCommerce package class loader.
 *
 * Loads external packages which are home to core functionality.
 *
 * @package WooCommerce/Core
 * @since   3.7.0
 */

namespace WooCommerce\Core;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\Core\Package;

/**
 * PackageManager class.
 */
final class PackageManager {

	/**
	 * List of packages to load, found in the /packages/ directory.
	 *
	 * @var array
	 */
	private static $packages = [
		'woocommerce-rest-api',
	];

	/**
	 * List of registered package versions.
	 *
	 * @var array
	 */
	private static $package_versions = [];

	/**
	 * Setup class.
	 */
	public static function init() {
		if ( empty( self::$package_versions ) ) {
			self::register_core_packages();
		}
	}

	/**
	 * Register a version of a package available to load.
	 *
	 * @since 3.7.0
	 * @param string $package Package name.
	 * @param string $version Version of the REST API being registered.
	 * @param mixed  $callback Callback function to load the REST API.
	 * @param string $path Path to package.
	 * @return bool
	 */
	public static function register( $package, $version, $callback, $path ) {
		if ( isset( self::$package_versions[ $package ][ $version ] ) ) {
			return false; // This version is already registered.
		}

		$registered_package           = new Package();
		$registered_package->version  = $version;
		$registered_package->callback = $callback;
		$registered_package->path     = $path;

		self::$package_versions[ $package ][ $version ] = $registered_package;

		return true;
	}

	/**
	 * Returns the latest version of a package.
	 *
	 * @param string $package Package name.
	 * @return Package
	 */
	public static function get_package( $package ) {
		if ( ! in_array( $package, self::$packages, true ) ) {
			return new Package();
		}
		$package_versions = self::$package_versions[ $package ];

		uksort( $package_versions, 'version_compare' );

		return end( $package_versions );
	}

	/**
	 * Show a build notice in admin if files are missing.
	 */
	public static function show_build_notice() {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				printf(
					/* Translators: %1$s WooCommerce plugin directory, %2$s is the install command, %3$s is the build command. */
					esc_html__( 'The development version of WooCommerce requires a build step to function correctly. From the plugin directory (%1$s), run %2$s to install dependencies and %3$s to build assets.', 'woocommerce' ),
					'<code>' . esc_html( str_replace( ABSPATH, '', WC_ABSPATH ) ) . '</code>',
					'<code>npm install</code>',
					'<code>npm run build</code>'
				);
				echo '</p></div>';
			}
		);
	}

	/**
	 * Register each package so we know which versions are available to load.
	 */
	private static function register_core_packages() {
		foreach ( self::$packages as $package ) {
			self::register_core_package( $package );
		}
	}

	/**
	 * Register a package included in core.
	 *
	 * Packages MUST include a valid version.php and init.php file so core knows
	 * which version to load, should the same package exist in multiple places.
	 *
	 * Packages are found in the /packages/ directory and are pulled in using
	 * composer during the build process.
	 *
	 * Packags also contain their own custom autoloader. This ensures that if
	 * multiple same-packages exist, only the chosen/latest one is used.
	 *
	 * @param string $package Package to load.
	 */
	private static function register_core_package( $package ) {
		$version  = '0';
		$callback = array( __CLASS__, 'show_build_notice' );
		$path     = WC_ABSPATH . 'packages/' . $package;

		if ( file_exists( $path . '/init.php' ) ) {
			$version  = include $path . '/version.php';
			$callback = include $path . '/init.php';
		}
		self::register( $package, $version, $callback, $path );
	}
}
