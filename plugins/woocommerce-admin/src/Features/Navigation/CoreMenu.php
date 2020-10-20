<?php
/**
 * WooCommerce Navigation Core Menu
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;


/**
 * CoreMenu class. Handles registering Core menu items.
 */
class CoreMenu {
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
		add_action( 'admin_menu', array( $this, 'add_core_items' ) );
		add_action( 'admin_menu', array( $this, 'add_core_setting_items' ) );
		add_filter( 'add_menu_classes', array( $this, 'migrate_child_items' ) );
	}

	/**
	 * Add registered admin settings as menu items.
	 */
	public function add_core_setting_items() {
		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		$settings      = array();
		foreach ( $setting_pages as $setting_page ) {
			$settings = $setting_page->add_settings_page( $settings );
		}

		$order = 0;
		foreach ( $settings as $key => $setting ) {
			$order += 10;
			Menu::add_item(
				array(
					'parent'     => 'settings',
					'title'      => $setting,
					'capability' => 'manage_woocommerce',
					'id'         => $key,
					'url'        => 'admin.php?page=wc-settings&tab=' . $key,
					'order'      => $order,
				)
			);
		}
	}

	/**
	 * Add the core menu items to the new navigation
	 */
	public function add_core_items() {
		// Orders category.
		Screen::register_post_type( 'shop_order', null );
		Menu::add_post_type_category( 'shop_order', array( 'order' => 20 ) );

		// Products category.
		Screen::register_post_type( 'product', null );
		Menu::add_post_type_category( 'product', array( 'order' => 40 ) );

		// Marketing category.
		Menu::add_category(
			array(
				'title'        => __( 'Marketing', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-marketing',
				'order'        => 30,
				'is_top_level' => true,
			)
		);
		Screen::register_post_type( 'shop_coupon', 'woocommerce-marketing' );

		// Extensions category.
		Menu::add_category(
			array(
				'title'        => __( 'Extensions', 'woocommerce-admin' ),
				'capability'   => 'activate_plugins',
				'id'           => 'extensions',
				'migrate'      => false,
				'menuId'       => 'secondary',
				'order'        => 20,
				'is_top_level' => true,
			)
		);
		Menu::add_item(
			array(
				'parent'     => 'extensions',
				'title'      => __( 'My extensions', 'woocommerce-admin' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'my-extensions',
				'url'        => 'plugins.php',
				'migrate'    => false,
			)
		);
		Menu::add_item(
			array(
				'parent'     => 'extensions',
				'title'      => __( 'Marketplace', 'woocommerce-admin' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'marketplace',
				'url'        => 'wc-addons',
			)
		);

		// Settings category.
		Menu::add_category(
			array(
				'title'        => __( 'Settings', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'settings',
				'menuId'       => 'secondary',
				'order'        => 10,
				'url'          => 'admin.php?page=wc-settings',
				'is_top_level' => true,
			)
		);

		// Tools category.
		Menu::add_category(
			array(
				'title'        => __( 'Tools', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'tools',
				'menuId'       => 'secondary',
				'order'        => 30,
				'is_top_level' => true,
			)
		);
		Menu::add_item(
			array(
				'parent'     => 'tools',
				'title'      => __( 'System status', 'woocommerce-admin' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'system-status',
				'url'        => 'wc-status',
			)
		);
		Menu::add_item(
			array(
				'parent'     => 'tools',
				'title'      => __( 'Import / Export', 'woocommerce-admin' ),
				'capability' => 'import',
				'id'         => 'import-export',
				'url'        => 'import.php',
				'migrate'    => false,
			)
		);
		Menu::add_item(
			array(
				'parent'     => 'tools',
				'title'      => __( 'Utilities', 'woocommerce-admin' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'utilities',
				'url'        => 'admin.php?page=wc-status&tab=tools',
			)
		);
	}

	/**
	 * Get items excluded from WooCommerce menu migration.
	 *
	 * @return array
	 */
	public static function get_excluded_items() {
		$excluded_items = array(
			'woocommerce',
			'wc-reports',
		);

		return apply_filters( 'woocommerce_navigation_core_excluded_items', $excluded_items );
	}

	/**
	 * Migrate any remaining WooCommerce child items.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	public function migrate_child_items( $menu ) {
		global $submenu;

		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as $menu_item ) {
			if ( in_array( $menu_item[2], self::get_excluded_items(), true ) ) {
				continue;
			}

			// Don't add already added items.
			$callbacks = Menu::instance()::get_callbacks();
			if ( array_key_exists( $menu_item[2], $callbacks ) ) {
				continue;
			}

			Menu::add_item(
				array(
					'parent'     => 'settings',
					'title'      => $menu_item[0],
					'capability' => $menu_item[1],
					'id'         => sanitize_title( $menu_item[0] ),
					'url'        => $menu_item[2],
				)
			);
		}

		return $menu;
	}
}
