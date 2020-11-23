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
		add_action( 'admin_menu', array( $this, 'register_post_types' ) );
	}

	/**
	 * Add registered admin settings as menu items.
	 */
	public static function get_setting_items() {
		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		$settings      = array();
		foreach ( $setting_pages as $setting_page ) {
			$settings = $setting_page->add_settings_page( $settings );
		}

		$menu_items = array();
		$order      = 0;
		foreach ( $settings as $key => $setting ) {
			$order       += 10;
			$menu_items[] = (
				array(
					'parent'     => 'woocommerce-settings',
					'title'      => $setting,
					'capability' => 'manage_woocommerce',
					'id'         => 'settings-' . $key,
					'url'        => 'admin.php?page=wc-settings&tab=' . $key,
					'order'      => $order,
				)
			);
		}

		return $menu_items;
	}

	/**
	 * Get all menu categories.
	 *
	 * @return array
	 */
	public static function get_categories() {
		return array(
			array(
				'title'        => __( 'Orders', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-orders',
				'order'        => 10,
				'is_top_level' => true,
			),
			array(
				'title'        => __( 'Products', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-products',
				'order'        => 20,
				'is_top_level' => true,
			),
			array(
				'title'        => __( 'Analytics', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-analytics',
				'order'        => 30,
				'is_top_level' => true,
			),
			array(
				'title'        => __( 'Marketing', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-marketing',
				'order'        => 40,
				'is_top_level' => true,
			),
			array(
				'title'        => __( 'Settings', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-settings',
				'menuId'       => 'secondary',
				'order'        => 10,
				'url'          => 'admin.php?page=wc-settings',
				'is_top_level' => true,
			),
			array(
				'title'        => __( 'Tools', 'woocommerce-admin' ),
				'capability'   => 'manage_woocommerce',
				'id'           => 'woocommerce-tools',
				'menuId'       => 'secondary',
				'order'        => 30,
				'is_top_level' => true,
			),
		);
	}

	/**
	 * Get all menu items.
	 *
	 * @return array
	 */
	public static function get_items() {
		$order_items   = Menu::get_post_type_items( 'shop_order', array( 'parent' => 'woocommerce-orders' ) );
		$product_items = Menu::get_post_type_items( 'product', array( 'parent' => 'woocommerce-products' ) );
		$coupon_items  = Menu::get_post_type_items( 'shop_coupon', array( 'parent' => 'woocommerce-marketing' ) );
		$setting_items = self::get_setting_items();
		$wca_items     = array();
		$wca_pages     = \Automattic\WooCommerce\Admin\PageController::get_instance()->get_pages();

		foreach ( $wca_pages as $page ) {
			if ( ! isset( $page['nav_args'] ) ) {
				continue;
			}

			$wca_items[] = array_merge(
				array(
					'id'         => 'wc_admin-' . $page['path'],
					'url'        => $page['path'],
					'title'      => $page['title'][0],
					'capability' => $page['capability'],
				),
				$page['nav_args']
			);
		}

		$home_item = array();
		if ( defined( '\Automattic\WooCommerce\Admin\Features\AnalyticsDashboard::MENU_SLUG' ) ) {
			$home_item = array(
				'id'           => 'wc_admin-wc-admin&path=/',
				'title'        => __( 'Home', 'woocommerce-admin' ),
				'url'          => \Automattic\WooCommerce\Admin\Features\AnalyticsDashboard::MENU_SLUG,
				'is_top_level' => true,
				'order'        => 0,
			);
		}

		return array_merge(
			array(
				$home_item,
				$order_items['all'],
				$order_items['new'],
				$product_items['all'],
				$product_items['new'],
				$coupon_items['default'],
				// Marketplace category.
				array(
					'title'        => __( 'Marketplace', 'woocommerce-admin' ),
					'capability'   => 'manage_woocommerce',
					'id'           => 'woocommerce-marketplace',
					'url'          => 'wc-addons',
					'menuId'       => 'secondary',
					'order'        => 20,
					'is_top_level' => true,
				),
				// Tools category.
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'System status', 'woocommerce-admin' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-system-status',
					'url'        => 'wc-status',
					'order'      => 20,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Import / Export', 'woocommerce-admin' ),
					'capability' => 'import',
					'id'         => 'tools-import-export',
					'url'        => 'import.php',
					'migrate'    => false,
					'order'      => 10,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Utilities', 'woocommerce-admin' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-utilities',
					'url'        => 'admin.php?page=wc-status&tab=tools',
					'order'      => 30,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Logs', 'woocommerce-admin' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-logs',
					'url'        => 'admin.php?page=wc-status&tab=logs',
					'order'      => 40,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Scheduled Actions', 'woocommerce-admin' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-scheduled_actions',
					'url'        => 'admin.php?page=wc-status&tab=action-scheduler',
					'order'      => 50,
				),
			),
			// WooCommerce Admin items.
			$wca_items,
			// Settings category.
			$setting_items
		);
	}

	/**
	 * Register all core post types.
	 */
	public function register_post_types() {
		Screen::register_post_type( 'shop_order' );
		Screen::register_post_type( 'product' );
		Screen::register_post_type( 'shop_coupon' );
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
}
