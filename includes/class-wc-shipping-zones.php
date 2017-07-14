<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles storage and retrieval of shipping zones
 *
 * @class 		WC_Shipping_Zones
 * @since 		2.6.0
 * @version		3.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooCommerce
 */
class WC_Shipping_Zones {

	/**
	 * Get shipping zones from the database
	 * @since 2.6.0
	 * @return array of arrays
	 */
	public static function get_zones() {
		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		$zones      = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zone                                                     = new WC_Shipping_Zone( $raw_zone );
			$zones[ $zone->get_id() ]                            = $zone->get_data();
			$zones[ $zone->get_id() ]['zone_id']                 = $zone->get_id();
			$zones[ $zone->get_id() ]['formatted_zone_location'] = $zone->get_formatted_location();
			$zones[ $zone->get_id() ]['shipping_methods']        = $zone->get_shipping_methods();
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
		switch ( $by ) {
			case 'zone_id' :
				$zone_id = $id;
			break;
			case 'instance_id' :
				$data_store = WC_Data_Store::load( 'shipping-zone' );
				$zone_id    = $data_store->get_zone_id_by_instance_id( $id );
			break;
		}

		if ( false !== $zone_id ) {
			try {
				return new WC_Shipping_Zone( $zone_id );
			} catch ( Exception $e ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Get shipping zone using it's ID
	 * @since 2.6.0
	 *
	 * @param $instance_id
	 *
	 * @return bool|WC_Shipping_Meethod
	 */
	public static function get_shipping_method( $instance_id ) {
		$data_store          = WC_Data_Store::load( 'shipping-zone' );
		$raw_shipping_method = $data_store->get_method( $instance_id );
		$wc_shipping         = WC_Shipping::instance();
		$allowed_classes     = $wc_shipping->get_shipping_method_class_names();

		if ( ! empty( $raw_shipping_method ) && in_array( $raw_shipping_method->method_id, array_keys( $allowed_classes ) ) ) {
			$class_name = $allowed_classes[ $raw_shipping_method->method_id ];
			if ( is_object( $class_name ) ) {
				$class_name = get_class( $class_name );
			}
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
		$zone = new WC_Shipping_Zone( $zone_id );
		$zone->delete();
	}

	/**
	 * Find a matching zone for a given package.
	 * @since  2.6.0
	 * @uses   wc_make_numeric_postcode()
	 * @param  object $package
	 * @return WC_Shipping_Zone
	 */
	public static function get_zone_matching_package( $package ) {
		$country          = strtoupper( wc_clean( $package['destination']['country'] ) );
		$state            = strtoupper( wc_clean( $package['destination']['state'] ) );
		$postcode         = wc_normalize_postcode( wc_clean( $package['destination']['postcode'] ) );
		$cache_key        = WC_Cache_Helper::get_cache_prefix( 'shipping_zones' ) . 'wc_shipping_zone_' . md5( sprintf( '%s+%s+%s', $country, $state, $postcode ) );
		$matching_zone_id = wp_cache_get( $cache_key, 'shipping_zones' );

		if ( false === $matching_zone_id ) {
			$data_store       = WC_Data_Store::load( 'shipping-zone' );
			$matching_zone_id = $data_store->get_zone_id_from_package( $package );
			wp_cache_set( $cache_key, $matching_zone_id, 'shipping_zones' );
		}

		return new WC_Shipping_Zone( $matching_zone_id ? $matching_zone_id : 0 );
	}
}
