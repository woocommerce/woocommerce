<?php
/**
 * WooCommerce Navigation Screen
 *
 * @deprecated 9.3.0 Navigation is no longer a feature and its classes will be removed in WooCommerce 9.5.
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use WC_Tracks;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Screen {
    private static $names = array(
        'get_screen_ids',
        'get_post_types',
        'get_taxonomies',
        'is_woocommerce_page',
        'is_woocommerce_core_taxonomy',
        'add_body_class',
        'add_screen',
        'get_plugin_page',
        'register_post_type',
        'register_taxonomy',
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