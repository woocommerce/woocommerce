<?php
/**
 * WooCommerce Marketing.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Marketing\InstalledExtensions;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Contains backend logic for the Marketing feature.
 */
class Marketing {

	use CouponsMovedTrait;

	/**
	 * Constant representing the key for the submenu name value in the global $submenu array.
	 *
	 * @var int
	 */
	const SUBMENU_NAME_KEY = 0;

	/**
	 * Constant representing the key for the submenu location value in the global $submenu array.
	 *
	 * @var int
	 */
	const SUBMENU_LOCATION_KEY = 2;

	/**
	 * Class instance.
	 *
	 * @var Marketing instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_pages' ), 5 );
		add_action( 'admin_menu', array( $this, 'add_parent_menu_item' ), 6 );

		// Overwrite submenu default ordering for marketing menu. High priority gives plugins the chance to register their own menu items.
		add_action( 'admin_menu', array( $this, 'reorder_marketing_submenu' ), 99 );

		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'component_settings' ), 30 );
	}

	/**
	 * Add main marketing menu item.
	 *
	 * Uses priority of 9 so other items can easily be added at the default priority (10).
	 */
	public function add_parent_menu_item() {
		if ( ! Features::is_enabled( 'navigation' ) ) {
			add_menu_page(
				__( 'Marketing', 'woocommerce' ),
				__( 'Marketing', 'woocommerce' ),
				'manage_woocommerce',
				'woocommerce-marketing',
				null,
				'dashicons-megaphone',
				58
			);
		}

		PageController::get_instance()->connect_page(
			[
				'id'         => 'woocommerce-marketing',
				'title'      => 'Marketing',
				'capability' => 'manage_woocommerce',
				'path'       => 'wc-admin&path=/marketing',
			]
		);
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		$this->register_overview_page();

		$controller = PageController::get_instance();
		$defaults   = [
			'parent'        => 'woocommerce-marketing',
			'existing_page' => false,
		];

		$marketing_pages = apply_filters( 'woocommerce_marketing_menu_items', [] );
		foreach ( $marketing_pages as $marketing_page ) {
			if ( ! is_array( $marketing_page ) ) {
				continue;
			}

			$marketing_page = array_merge( $defaults, $marketing_page );

			if ( $marketing_page['existing_page'] ) {
				$controller->connect_page( $marketing_page );
			} else {
				$controller->register_page( $marketing_page );
			}
		}
	}

	/**
	 * Register the main Marketing page, which is Marketing > Overview.
	 *
	 * This is done separately because we need to ensure the page is registered properly and
	 * that the link is done properly. For some reason the normal page registration process
	 * gives us the wrong menu link.
	 */
	protected function register_overview_page() {
		global $submenu;

		// First register the page.
		PageController::get_instance()->register_page(
			[
				'id'       => 'woocommerce-marketing-overview',
				'title'    => __( 'Overview', 'woocommerce' ),
				'path'     => 'wc-admin&path=/marketing',
				'parent'   => 'woocommerce-marketing',
				'nav_args' => array(
					'parent' => 'woocommerce-marketing',
					'order'  => 10,
				),
			]
		);

		// Now fix the path, since register_page() gets it wrong.
		if ( ! isset( $submenu['woocommerce-marketing'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce-marketing'] as &$item ) {
			// The "slug" (aka the path) is the third item in the array.
			if ( 0 === strpos( $item[2], 'wc-admin' ) ) {
				$item[2] = 'admin.php?page=' . $item[2];
			}
		}
	}

	/**
	 * Order marketing menu items alphabeticaly.
	 * Overview should be first, and Coupons should be second, followed by other marketing menu items.
	 *
	 * @return  void
	 */
	public function reorder_marketing_submenu() {
		global $submenu;

		if ( ! isset( $submenu['woocommerce-marketing'] ) ) {
			return;
		}

		$marketing_submenu = $submenu['woocommerce-marketing'];
		$new_menu_order    = array();

		// Overview should be first.
		$overview_key = array_search( 'Overview', array_column( $marketing_submenu, self::SUBMENU_NAME_KEY ), true );

		if ( false === $overview_key ) {
			/*
			 * If Overview is not found we may be on a site witha different language.
			 * We can use a fallback and try to find the overview page by its path.
			 */
			$overview_key = array_search( 'admin.php?page=wc-admin&path=/marketing', array_column( $marketing_submenu, self::SUBMENU_LOCATION_KEY ), true );
		}

		if ( false !== $overview_key ) {
			$new_menu_order[] = $marketing_submenu[ $overview_key ];
			array_splice( $marketing_submenu, $overview_key, 1 );
		}

		// Coupons should be second.
		$coupons_key = array_search( 'Coupons', array_column( $marketing_submenu, self::SUBMENU_NAME_KEY ), true );

		if ( false === $coupons_key ) {
			/*
			 * If Coupons is not found we may be on a site witha different language.
			 * We can use a fallback and try to find the coupons page by its path.
			 */
			$coupons_key = array_search( 'edit.php?post_type=shop_coupon', array_column( $marketing_submenu, self::SUBMENU_LOCATION_KEY ), true );
		}

		if ( false !== $coupons_key ) {
			$new_menu_order[] = $marketing_submenu[ $coupons_key ];
			array_splice( $marketing_submenu, $coupons_key, 1 );
		}

		// Sort the rest of the items alphabetically.
		usort(
			$marketing_submenu,
			function( $a, $b ) {
				return strcmp( $a[0], $b[0] );
			}
		);

		$new_menu_order = array_merge( $new_menu_order, $marketing_submenu );

		$submenu['woocommerce-marketing'] = $new_menu_order;  //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Add settings for marketing feature.
	 *
	 * @param array $settings Component settings.
	 * @return array
	 */
	public function component_settings( $settings ) {
		// Bail early if not on a wc-admin powered page.
		if ( ! PageController::is_admin_page() ) {
			return $settings;
		}

		$settings['marketing']['installedExtensions'] = InstalledExtensions::get_data();

		return $settings;
	}
}
