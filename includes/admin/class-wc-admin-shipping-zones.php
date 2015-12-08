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
		include_once( 'views/html-admin-page-shipping-zones.php' );
	}
}
