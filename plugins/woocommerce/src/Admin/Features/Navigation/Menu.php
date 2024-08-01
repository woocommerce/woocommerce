<?php
/**
 * WooCommerce Navigation Menu
 *
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Navigation\Init;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Menu {
	/**
	 * Class instance.
	 *
	 * @var Menu instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init.
	 */
	public function init() {
		return;
	}

	/**
	 * Adds a top level menu item to the navigation.
	 * 
	 * DEPRECATED: This method is deprecated and will be removed in WooCommerce 9.4.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'      => (string) The unique ID of the menu item. Required.
	 *      'title'   => (string) Title of the menu item. Required.
	 *      'url'     => (string) URL or callback to be used. Required.
	 *      'order'   => (int) Menu item order.
	 *      'migrate' => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'  => (string) The ID of the menu to add the category to.
	 *    ).
	 */
	private static function add_category( $args ) {
		Init::deprecation_notice( 'Menu::add_category' );

		return;
	}

	/**
	 * Adds a child menu item to the navigation.
	 * 
	 * DEPRECATED: This method is deprecated and will be removed in WooCommerce 9.4.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'              => (string) The unique ID of the menu item. Required.
	 *      'title'           => (string) Title of the menu item. Required.
	 *      'parent'          => (string) Parent menu item ID.
	 *      'capability'      => (string) Capability to view this menu item.
	 *      'url'             => (string) URL or callback to be used. Required.
	 *      'order'           => (int) Menu item order.
	 *      'migrate'         => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'          => (string) The ID of the menu to add the item to.
	 *      'matchExpression' => (string) A regular expression used to identify if the menu item is active.
	 *    ).
	 */
	private static function add_item( $args ) {
		Init::deprecation_notice( 'Menu::add_item' );

		return;
	}

	/**
	 * Adds a plugin category.
	 * 
	 * DEPRECATED: This method is deprecated and will be removed in WooCommerce 9.4.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'      => (string) The unique ID of the menu item. Required.
	 *      'title'   => (string) Title of the menu item. Required.
	 *      'url'     => (string) URL or callback to be used. Required.
	 *      'migrate' => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'order'   => (int) Menu item order.
	 *    ).
	 */
	public static function add_plugin_category( $args ) {
		Init::deprecation_notice( 'Menu::add_plugin_category' );

		return;
	}

	/**
	 * Adds a plugin item.
	 * 
	 * DEPRECATED: This method is deprecated and will be removed in WooCommerce 9.4.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'              => (string) The unique ID of the menu item. Required.
	 *      'title'           => (string) Title of the menu item. Required.
	 *      'parent'          => (string) Parent menu item ID.
	 *      'capability'      => (string) Capability to view this menu item.
	 *      'url'             => (string) URL or callback to be used. Required.
	 *      'migrate'         => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'order'           => (int) Menu item order.
	 *      'matchExpression' => (string) A regular expression used to identify if the menu item is active.
	 *    ).
	 */
	public static function add_plugin_item( $args ) {
		Init::deprecation_notice( 'Menu::add_plugin_item' );

		return;
	}

	/**
	 * Adds a plugin setting item.
	 * 
	 * DEPRECATED: This method is deprecated and will be removed in WooCommerce 9.4.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'         => (string) The unique ID of the menu item. Required.
	 *      'title'      => (string) Title of the menu item. Required.
	 *      'capability' => (string) Capability to view this menu item.
	 *      'url'        => (string) URL or callback to be used. Required.
	 *      'migrate'    => (bool) Whether or not to hide the item in the wp admin menu.
	 *    ).
	 */
	public static function add_setting_item( $args ) {
		Init::deprecation_notice( 'Menu::add_setting_item' );

		return;
	}
}
