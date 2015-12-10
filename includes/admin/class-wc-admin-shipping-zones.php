<?php
/**
 * Shipping Zones Admin Page
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Shipping_Zones Class.
 */
class WC_Admin_Shipping_Zones {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
        $allowed_countries = WC()->countries->get_allowed_countries();
        $continents        = WC()->countries->get_continents();

        // Localize and enqueue our js.
		wp_localize_script( 'wc-shipping-zones', 'shippingZonesLocalizeScript', array(
            'zones'         => WC_Shipping_Zones::get_zones(),
            'default_zone'  => array(
				'zone_id'    => 0,
				'zone_name'  => '',
				'zone_order' => null,
			),
			'wc_shipping_zones_nonce'  => wp_create_nonce( 'wc_shipping_zones_nonce' ),
			'strings'       => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' )
			),
		) );
		wp_enqueue_script( 'wc-shipping-zones' );

		include_once( 'views/html-admin-page-shipping-zones.php' );
	}
}
