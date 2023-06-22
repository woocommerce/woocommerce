<?php
/**
 * WooCommerce Marketplace.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Contains backend logic for the Marketplace feature.
 */
class Marketplace {
	final public function init() {
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		$marketplace_pages = self::get_marketplace_pages();
		foreach ( $marketplace_pages as $marketplace_page ) {
			if ( ! is_null( $marketplace_page ) ) {
				wc_admin_register_page( $marketplace_page );
			}
		}
	}

	/**
	 * Get report pages.
	 */
	public static function get_marketplace_pages() {
		$discover_page = array(
			'id'       => 'woocommerce-marketplace',
			'title'    => __( 'Extensions', 'woocommerce' ),
			'path'     => '/marketplace/discover',
			'icon'     => 'dashicons-store',
			'position' => 57, // After WooCommerce & Product menu items.
		);

		$marketplace_pages = array(
			$discover_page,
			array(
				'id'       => 'woocommerce-marketplace-extensions',
				'title'    => __( 'Extensions', 'woocommerce' ),
				'parent'   => 'woocommerce-marketplace',
				'path'     => '/marketplace/extensions',
				'nav_args' => array(
					'order'  => 10,
					'parent' => 'woocommerce-marketplace',
				),
			),
			array(
				'id'       => 'woocommerce-marketplace-themes',
				'title'    => __( 'Themes', 'woocommerce' ),
				'parent'   => 'woocommerce-marketplace',
				'path'     => '/marketplace/themes',
				'nav_args' => array(
					'order'  => 20,
					'parent' => 'woocommerce-marketplace',
				),
			),
		);

		/**
		 * The marketplace items used in the menu.
		 */
		return apply_filters( 'woocommerce_marketplace_menu_items', $marketplace_pages );
	}
}
