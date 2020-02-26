<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/WCAdmin
 */

/**
 * This namespace isn't compatible with the PSR-4
 * which ensures that the copy in the standalone plugin will not be autoloaded.
 */
namespace Automattic\WooCommerce\Admin\Composer;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Deactivate_Plugin;
use Automattic\WooCommerce\Admin\FeaturePlugin;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '0.26.1';

	/**
	 * Package active.
	 *
	 * @var bool
	 */
	private static $package_active = false;

	/**
	 * Init the package.
	 *
	 * Only initialize for WP 5.3 or greater.
	 */
	public static function init() {
		$wordpress_minimum_met = version_compare( get_bloginfo( 'version' ), '5.3', '>=' );
		if ( ! $wordpress_minimum_met ) {
			return;
		}

		// Indicate to the feature plugin that the core package exists.
		if ( ! defined( 'WC_ADMIN_PACKAGE_EXISTS' ) ) {
			define( 'WC_ADMIN_PACKAGE_EXISTS', true );
		}

		// Avoid double initialization when the feature plugin is in use.
		if ( defined( 'WC_ADMIN_VERSION_NUMBER' ) ) {
			$update_version = new WC_Admin_Notes_Deactivate_Plugin();
			if ( version_compare( WC_ADMIN_VERSION_NUMBER, self::VERSION, '<' ) ) {
				$update_version::add_note();
			} else {
				$update_version::delete_note();
			}

			// Register a deactivation hook for the feature plugin.
			register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, array( __CLASS__, 'on_deactivation' ) );

			return;
		}

		self::$package_active = true;
		FeaturePlugin::instance()->init();
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the active version of WC Admin.
	 *
	 * @return string
	 */
	public static function get_active_version() {
		return self::$package_active ? self::VERSION : WC_ADMIN_VERSION_NUMBER;
	}

	/**
	 * Return whether the package is active.
	 *
	 * @return bool
	 */
	public static function is_package_active() {
		return self::$package_active;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Add deactivation hook for versions of the plugin that don't have the deactivation note.
	 */
	public static function on_deactivation() {
		$update_version = new WC_Admin_Notes_Deactivate_Plugin();
		$update_version::delete_note();
	}
}
