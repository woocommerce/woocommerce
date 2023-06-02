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
		// Add this after we've finished migrating menu items to avoid hiding these items.
		add_action( 'admin_menu', array( $this, 'add_dashboard_menu_items' ), PHP_INT_MAX );
	}

	/**
	 * Add registered admin settings as menu items.
	 */
	public static function get_setting_items() {
		// Calling this method adds pages to the below tabs filter on non-settings pages.
		\WC_Admin_Settings::get_settings_pages();
		$tabs = apply_filters( 'woocommerce_settings_tabs_array', array() );

		$menu_items = array();
		$order      = 0;
		foreach ( $tabs as $key => $setting ) {
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
				'title'      => __( 'Orders', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-orders',
				'order'      => 10,
			),
			array(
				'title'      => __( 'Products', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-products',
				'order'      => 20,
			),
			array(
				'title'      => __( 'Analytics', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-analytics',
				'order'      => 30,
			),
			array(
				'title'      => __( 'Marketing', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-marketing',
				'order'      => 40,
			),
			array(
				'title'      => __( 'Settings', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-settings',
				'menuId'     => 'secondary',
				'order'      => 10,
				'url'        => 'admin.php?page=wc-settings',
			),
			array(
				'title'      => __( 'Tools', 'woocommerce' ),
				'capability' => 'manage_woocommerce',
				'id'         => 'woocommerce-tools',
				'menuId'     => 'secondary',
				'order'      => 30,
			),
		);
	}

	/**
	 * Get all menu items.
	 *
	 * @return array
	 */
	public static function get_items() {
		$order_items       = Menu::get_post_type_items( 'shop_order', array( 'parent' => 'woocommerce-orders' ) );
		$product_items     = Menu::get_post_type_items( 'product', array( 'parent' => 'woocommerce-products' ) );
		$product_tag_items = Menu::get_taxonomy_items(
			'product_tag',
			array(
				'parent' => 'woocommerce-products',
				'order'  => 30,
			)
		);
		$product_cat_items = Menu::get_taxonomy_items(
			'product_cat',
			array(
				'parent' => 'woocommerce-products',
				'order'  => 20,
			)
		);

		$coupon_items  = Menu::get_post_type_items( 'shop_coupon', array( 'parent' => 'woocommerce-marketing' ) );
		$setting_items = self::get_setting_items();
		$wca_items     = array();
		$wca_pages     = \Automattic\WooCommerce\Admin\PageController::get_instance()->get_pages();

		foreach ( $wca_pages as $page ) {
			if ( ! isset( $page['nav_args'] ) ) {
				continue;
			}

			$path = isset( $page['path'] ) ? $page['path'] : null;
			$item = array_merge(
				array(
					'id'         => $page['id'],
					'url'        => $path,
					'title'      => $page['title'][0],
					'capability' => isset( $page['capability'] ) ? $page['capability'] : 'manage_woocommerce',
				),
				$page['nav_args']
			);

			// Don't allow top-level items to be added to the primary menu.
			if ( ! isset( $item['parent'] ) || 'woocommerce' === $item['parent'] ) {
				$item['menuId'] = 'plugins';
			}

			$wca_items[] = $item;
		}

		$home_item = array();
		if ( defined( '\Automattic\WooCommerce\Admin\Features\AnalyticsDashboard::MENU_SLUG' ) ) {
			$home_item = array(
				'id'              => 'woocommerce-home',
				'title'           => __( 'Home', 'woocommerce' ),
				'url'             => \Automattic\WooCommerce\Admin\Features\AnalyticsDashboard::MENU_SLUG,
				'order'           => 0,
				'matchExpression' => 'page=wc-admin((?!path=).)*$',
			);
		}

		$customers_item = array();
		if ( class_exists( '\Automattic\WooCommerce\Admin\Features\Analytics' ) ) {
			$customers_item = array(
				'id'    => 'woocommerce-analytics-customers',
				'title' => __( 'Customers', 'woocommerce' ),
				'url'   => 'wc-admin&path=/customers',
				'order' => 50,
			);
		}

		return array_merge(
			array(
				$home_item,
				$customers_item,
				$order_items['all'],
				$order_items['new'],
				$product_items['all'],
				$product_cat_items['default'],
				$product_tag_items['default'],
				array(
					'id'              => 'woocommerce-product-attributes',
					'title'           => __( 'Attributes', 'woocommerce' ),
					'url'             => 'edit.php?post_type=product&page=product_attributes',
					'capability'      => 'manage_product_terms',
					'order'           => 40,
					'parent'          => 'woocommerce-products',
					'matchExpression' => 'edit.php(?=.*[?|&]page=product_attributes(&|$|#))|edit-tags.php(?=.*[?|&]taxonomy=pa_)(?=.*[?|&]post_type=product(&|$|#))',
				),
				array_merge( $product_items['new'], array( 'order' => 50 ) ),
				$coupon_items['default'],
				// Marketplace category.
				array(
					'title'      => __( 'Marketplace', 'woocommerce' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'woocommerce-marketplace',
					'url'        => 'wc-addons',
					'menuId'     => 'secondary',
					'order'      => 20,
				),
				// Tools category.
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'System status', 'woocommerce' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-system-status',
					'url'        => 'wc-status',
					'order'      => 20,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Import / Export', 'woocommerce' ),
					'capability' => 'import',
					'id'         => 'tools-import-export',
					'url'        => 'import.php',
					'migrate'    => false,
					'order'      => 10,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Utilities', 'woocommerce' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-utilities',
					'url'        => 'admin.php?page=wc-status&tab=tools',
					'order'      => 30,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Logs', 'woocommerce' ),
					'capability' => 'manage_woocommerce',
					'id'         => 'tools-logs',
					'url'        => 'admin.php?page=wc-status&tab=logs',
					'order'      => 40,
				),
				array(
					'parent'     => 'woocommerce-tools',
					'title'      => __( 'Scheduled Actions', 'woocommerce' ),
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
	 * Add the dashboard items to the WP menu to create a quick-access flyout menu.
	 */
	public function add_dashboard_menu_items() {
		global $submenu, $menu;
		$top_level_items = Menu::get_category_items( 'woocommerce' );

		// phpcs:disable
		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach( $top_level_items as $item ) {
			// Skip extensions.
			if ( ! isset( $item['menuId'] ) || $item['menuId'] === 'plugins' ) {
				continue;
			}

			// Skip specific categories.
			if (
				in_array(
					$item['id'],
					array(
						'woocommerce-tools',
					),
					true
				)
			) {
				continue;
			}

			// Use the link from the first item if it's a category.
			if ( ! isset( $item['url'] ) ) {
				$category_items = Menu::get_category_items( $item['id'] );
				$first_item     = $category_items[0];

				$submenu['woocommerce'][] = array(
					$item['title'],
					$first_item['capability'],
					isset( $first_item['url'] ) ? $first_item['url'] : null,
					$item['title'],
				);

				continue;
			}

			// Show top-level items.
			$submenu['woocommerce'][] = array(
				$item['title'],
				$item['capability'],
				isset( $item['url'] ) ? $item['url'] : null,
				$item['title'],
			);
		}
		// phpcs:enable
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
			'wc-settings',
		);

		return apply_filters( 'woocommerce_navigation_core_excluded_items', $excluded_items );
	}
}
