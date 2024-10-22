<?php
/**
 * WooCommerce Navigation Core Menu
 *
 * @deprecated 9.3.0 Navigation is no longer a feature and its classes will be removed in WooCommerce 9.5.
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use WC_Tracks;

/**
 * CoreMenu class. Handles registering Core menu items.
 */
class CoreMenu {
    /**
	 * List of public methods that are deprecated.
	 */
	private static $names = array(
        'get_setting_items',
        'get_shop_order_count',
        'get_categories',
        'get_items',
        'get_order_menu_items',
        'get_tool_items',
        'get_legacy_report_items',
        'register_post_types',
        'add_dashboard_menu_items',
        'get_excluded_items',
    );

    /**
	 * Handle deprecated method calls.
	 *
	 * @param string $name The name of the deprecated method.
	 */
	private static function handle_deprecated_method_call( $name ) {
		error_log( 'Automattic\WooCommerce\Admin\Features\Navigation\Menu is deprecated since 9.3 with no alternative. Navigation classes will be removed in WooCommerce 9.6' );

		if ( class_exists( 'WC_Tracks' ) ) {
			WC_Tracks::record_event( 'deprecated_navigation_method_called' );
		}
	}

	/**
	 * Handle calls to deprecated methods.
	 *
	 * @param string $name The name of the deprecated method.
	 * @param array  $arguments The arguments passed to the deprecated method.
	 */
    public function __call( $name, $arguments ) {
		if ( in_array( $name, self::$names ) ) {    
			self::handle_deprecated_method_call( $name );
		}
    }

	/**
	 * Handle static calls to deprecated methods.
	 *
	 * @param string $name The name of the deprecated method.
	 * @param array  $arguments The arguments passed to the deprecated method.
	 */
    public static function __callStatic( $name, $arguments ) {
		if ( in_array( $name, self::$names ) ) {    
			self::handle_deprecated_method_call( $name );
		}
    }
}