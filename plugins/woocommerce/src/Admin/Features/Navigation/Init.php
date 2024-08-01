<?php
/**
 * Navigation Experience
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;
use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\CoreMenu;
use WC_Tracks;

/**
 * Contains logic for the Navigation
 */
class Init {
	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_navigation_enabled';

	/**
	 * Determines if the feature has been toggled on or off.
	 *
	 * @var boolean
	 */
	protected static $is_updated = false;

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		if ( Features::is_enabled( 'navigation' ) ) {
			// Disable the option to turn off the feature.
			update_option( self::TOGGLE_OPTION_NAME, 'no' );

			if ( class_exists( 'WC_Tracks' ) ) {
				WC_Tracks::record_event( 'deprecated_navigation_in_use' );
			}

			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) );
				exit();
			}
		}
	}

	/**
	 * Determine if sufficient versions are present to support Navigation feature
	 */
	public function is_nav_compatible() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$gutenberg_minimum_version = '9.0.0'; // https://github.com/WordPress/gutenberg/releases/tag/v9.0.0.
		$wp_minimum_version        = '5.6';
		$has_gutenberg             = is_plugin_active( 'gutenberg/gutenberg.php' );
		$gutenberg_version         = $has_gutenberg ? get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' )['Version'] : false;

		if ( $gutenberg_version && version_compare( $gutenberg_version, $gutenberg_minimum_version, '>=' ) ) {
			return true;
		}

		// Get unmodified $wp_version.
		include ABSPATH . WPINC . '/version.php';

		// Strip '-src' from the version string. Messes up version_compare().
		$wp_version = str_replace( '-src', '', $wp_version );

		if ( version_compare( $wp_version, $wp_minimum_version, '>=' ) ) {
			return true;
		}

		return false;
	}
}
