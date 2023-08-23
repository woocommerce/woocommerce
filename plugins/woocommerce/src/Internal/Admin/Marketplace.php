<?php
/**
 * WooCommerce Marketplace.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Contains backend logic for the Marketplace feature.
 */
class Marketplace {

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 */
	final public function init() {
		if ( FeaturesUtil::feature_is_enabled( 'marketplace' ) ) {
			add_action( 'admin_menu', array( $this, 'register_pages' ), 70 );
		}
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
		$marketplace_pages = array(
			array(
				'id'     => 'woocommerce-marketplace',
				'parent' => 'woocommerce',
				'title'  => __( 'Extensions', 'woocommerce' ),
				'path'   => '/extensions',
			),
		);

		/**
		 * The marketplace items used in the menu.
		 *
		 * @since 8.0
		 */
		return apply_filters( 'woocommerce_marketplace_menu_items', $marketplace_pages );
	}
}
