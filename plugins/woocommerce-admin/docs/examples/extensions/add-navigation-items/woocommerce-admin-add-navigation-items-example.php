<?php
/**
 * Plugin Name: WooCommerce Admin Add Navigation Items Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function add_navigation_items_register_script() {
	if ( ! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Screen' ) || ! \Automattic\WooCommerce\Admin\Features\Navigation\Screen::is_woocommerce_page() ) {
		return;
	}

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'add-navigation-items',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'add-navigation-items' );
}
add_action( 'admin_enqueue_scripts', 'add_navigation_items_register_script' );

/**
 * Add Example items to WooCommerce Navigation
 */
function add_navigation_items_register_items() {
	if (
		! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_category' ) ||
		! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_item' )
	) {
		return;
	}

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
		array(
			'id'         => 'example-plugin',
			'title'      => 'Example Plugin',
			'capability' => 'view_woocommerce_reports',
			'url'        => 'https://www.google.com',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
		array(
			'id'         => 'example-category',
			'title'      => 'Example Category',
			'capability' => 'view_woocommerce_reports',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
		array(
			'id'         => 'example-category-child-1',
			'parent'     => 'example-category',
			'title'      => 'Sub Menu Child 1',
			'capability' => 'view_woocommerce_reports',
			'url'        => 'https://www.google.com',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
		array(
			'id'         => 'example-category-child-2',
			'parent'     => 'example-category',
			'title'      => 'Sub Menu Child 2',
			'capability' => 'view_woocommerce_reports',
			'url'        => 'https://www.google.com',
		)
	);
}
add_filter( 'admin_menu', 'add_navigation_items_register_items' );
