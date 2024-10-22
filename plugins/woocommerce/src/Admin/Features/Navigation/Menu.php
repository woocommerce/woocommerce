<?php
/**
 * WooCommerce Navigation Menu
 *
 * @deprecated 9.3.0 Navigation is no longer a feature and its classes will be removed in WooCommerce 9.5.
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use WC_Tracks;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Menu {
	/**
	 * List of public methods that are deprecated.
	 */
	private static $names = array(
		'get_callback_url',
		'get_parent_key',
		'add_category',
		'add_item',
		'get_item_menu_id',
		'add_plugin_category',
		'add_plugin_item',
		'add_setting_item',
		'get_post_type_items',
		'get_taxonomy_items',
		'add_core_items',
		'add_item_and_taxonomy',
		'migrate_core_child_items',
		'has_callback',
		'migrate_menu_items',
		'hide_wp_menu_item',
		'get_items',
		'get_category_items',
		'get_callbacks',
		'get_mapped_menu_items',
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
		self::handle_deprecated_method_call( $name );
    }

	/**
	 * Handle static calls to deprecated methods.
	 *
	 * @param string $name The name of the deprecated method.
	 * @param array  $arguments The arguments passed to the deprecated method.
	 */
    public static function __callStatic( $name, $arguments ) {
		self::handle_deprecated_method_call( $name );
    }
}
