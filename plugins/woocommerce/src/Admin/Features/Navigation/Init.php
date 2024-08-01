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
		}
	}

	/**
	 * Create a deprecation notice.
	 */
	public static function deprecation_notice( $function ) {
		error_log( 'Automattic\WooCommerce\Admin\Features\Navigation\\' . $function . ' is deprecated. This class will be removed in WooCommerce 9.4' );
	}
}
