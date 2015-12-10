<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles storage and retrieval of shipping zones
 *
 * @class 		WC_Shipping_Zones
 * @version		2.5.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Shipping_Zones {

	/**
	 * Get shipping zones from the database
	 * @return array of arrays
	 */
    public static function get_zones() {
		global $wpdb;

        $raw_zones = $wpdb->get_results( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC;" );
		$zones     = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zone    = new WC_Shipping_Zone( $raw_zone );
			$zones[] = $zone->get_data();
		}

		return $zones;
    }

    public static function delete_zone( $zone_id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $zone_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zones', array( 'zone_id' => $zone_id ) );
    }
}
