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

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 */
	final public function init() {
		add_action( 'admin_menu', array( $this, 'register_pages' ), 45 );
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		$marketplace_page = self::get_marketplace_page();
		wc_admin_register_page( $marketplace_page );
	}

	/**
	 * Get report pages.
	 */
	public static function get_marketplace_page() {
		$marketplace_page = array(
			array(
				'id'     => 'woocommerce-marketplace',
				'parent' => 'woocommerce',
				'title'  => __( 'Marketplace', 'woocommerce' ),
				'path'   => '/marketplace',
			),
		);

		/**
		 * The marketplace items used in the menu.
		 *
		 * @since 8.0
		 */
		return apply_filters( 'woocommerce_marketplace_menu_items', $marketplace_page );
	}
}
