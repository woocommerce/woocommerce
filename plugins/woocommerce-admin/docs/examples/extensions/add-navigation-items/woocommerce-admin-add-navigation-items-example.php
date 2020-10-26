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

	wp_register_script(
		'add-navigation-items',
		plugins_url( '/dist/index.js', __FILE__ ),
		array(
			'wp-hooks',
			'wp-element',
			'wp-i18n',
			'wc-components',
			'wp-plugins',
		),
		filemtime( dirname( __FILE__ ) . '/dist/index.js' ),
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
		! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_category' ) ||
		! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_item' )
	) {
		return;
	}

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_item(
		array(
			'id'         => 'example-marketing-plugin',
			'title'      => 'Example Marketing Settings',
			'capability' => 'view_woocommerce_reports',
			'parent'     => 'settings',
			'url'        => 'https://www.google.com',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_category(
		array(
			'id'         => 'example-marketing-category',
			'parent'     => 'woocommerce-marketing',
			'title'      => 'Example Marketing Category',
			'capability' => 'view_woocommerce_reports',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_item(
		array(
			'id'         => 'example-marketing-category-child-1',
			'parent'     => 'example-marketing-category',
			'title'      => 'Sub Menu Child 1',
			'capability' => 'view_woocommerce_reports',
			'url'        => 'https://www.google.com',
		)
	);

	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_item(
		array(
			'id'         => 'example-marketing-category-child-2',
			'parent'     => 'example-marketing-category',
			'title'      => 'Sub Menu Child 2',
			'capability' => 'view_woocommerce_reports',
			'url'        => 'https://www.google.com',
		)
	);
}
add_filter( 'admin_menu', 'add_navigation_items_register_items' );
