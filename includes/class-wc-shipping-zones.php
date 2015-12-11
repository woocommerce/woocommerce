<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles storage and retrieval of shipping zones
 *
 * @class 		WC_Shipping_Zones
 * @since 		2.6.0
 * @version		2.6.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Shipping_Zones {

	/**
	 * Get shipping zones from the database
	 * @since 2.6.0
	 * @return array of arrays
	 */
    public static function get_zones() {
		global $wpdb;

        $raw_zones = $wpdb->get_results( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC;" );
		$zones     = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zone                                                     = new WC_Shipping_Zone( $raw_zone );
			$zones[ $zone->get_zone_id() ]                            = $zone->get_data();
			$zones[ $zone->get_zone_id() ]['formatted_zone_location'] = $zone->get_formatted_location();
			$zones[ $zone->get_zone_id() ]['shipping_methods']        = $zone->get_shipping_methods();
		}

		return $zones;
    }

	/**
	 * Get shipping zone using it's ID
	 * @since 2.6.0
	 * @param int $zone_id
	 * @return WC_Shipping_Zone|bool
	 */
	public static function get_zone( $zone_id ) {
		return self::get_zone_by( 'zone_id', $zone_id );
	}

	/**
	 * Get shipping zone by an ID.
	 * @since 2.6.0
	 * @param string $by zone_id or instance_id
	 * @param int $id
	 * @return WC_Shipping_Zone|bool
	 */
	public static function get_zone_by( $by = 'zone_id', $id = 0 ) {
		global $wpdb;

		switch ( $by ) {
			case 'zone_id' :
				$raw_zone = $wpdb->get_row( $wpdb->prepare( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $id ) );
			break;
			case 'instance_id' :
				$raw_zone = $wpdb->get_row( $wpdb->prepare( "
					SELECT zones.zone_id, zones.zone_name, zones.zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones as zones
					LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zone_methods as methods ON zones.zone_id = methods.zone_id
					WHERE methods.instance_id = %d LIMIT 1;
					", $id ) );
			break;
			default :
				$raw_zone = false;
			break;
		}

		return $raw_zone ? new WC_Shipping_Zone( $raw_zone ) : false;
	}

	/**
	 * Get shipping zone using it's ID
	 * @since 2.6.0
	 * @param int $zone_id
	 * @return WC_Shipping_Meethod|bool
	 */
	public static function get_shipping_method( $instance_id ) {
		global $wpdb;
        $raw_shipping_method = $wpdb->get_row( $wpdb->prepare( "SELECT instance_id, method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1;", $instance_id ) );
		$wc_shipping         = WC_Shipping::instance();
		$allowed_classes     = $wc_shipping->get_shipping_method_class_names();

		if ( in_array( $raw_shipping_method->method_id, array_keys( $allowed_classes ) ) ) {
			$class_name = $allowed_classes[ $raw_shipping_method->method_id ];
			return new $class_name( $raw_shipping_method->instance_id );
		}
		return false;
	}

	/**
	 * Delete a zone using it's ID
	 * @param int $zone_id
	 * @since 2.6.0
	 */
    public static function delete_zone( $zone_id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $zone_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zones', array( 'zone_id' => $zone_id ) );
    }
}
