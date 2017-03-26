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

		$raw_zone = false;

		switch ( $by ) {
			case 'zone_id' :
				if ( 0 === $id ) {
					return new WC_Shipping_Zone( 0 );
				} else {
					$raw_zone = $wpdb->get_row( $wpdb->prepare( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $id ) );
				}
			break;
			case 'instance_id' :
				$zone_id = $wpdb->get_var( $wpdb->prepare( "SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods as methods WHERE methods.instance_id = %d LIMIT 1;", $id ) );

				if ( false !== $zone_id ) {
					return self::get_zone_by( 'zone_id', absint( $zone_id ) );
				}
			break;
		}

		return $raw_zone ? new WC_Shipping_Zone( $raw_zone ) : false;
	}

	/**
	 * Get shipping zone using it's ID
	 * @since 2.6.0
	 * @return WC_Shipping_Meethod|bool
	 */
	public static function get_shipping_method( $instance_id ) {
		global $wpdb;
		$raw_shipping_method = $wpdb->get_row( $wpdb->prepare( "SELECT instance_id, method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1;", $instance_id ) );
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
		global $wpdb;

		$country          = strtoupper( wc_clean( $package['destination']['country'] ) );
		$state            = strtoupper( wc_clean( $package['destination']['state'] ) );
		$continent        = strtoupper( wc_clean( WC()->countries->get_continent_code_for_country( $country ) ) );
		$postcode         = wc_normalize_postcode( wc_clean( $package['destination']['postcode'] ) );
		$cache_key        = WC_Cache_Helper::get_cache_prefix( 'shipping_zones' ) . 'wc_shipping_zone_' . md5( sprintf( '%s+%s+%s', $country, $state, $postcode ) );
		$matching_zone_id = wp_cache_get( $cache_key, 'shipping_zones' );

		if ( false === $matching_zone_id ) {

			// Work out criteria for our zone search
			$criteria = array();
			$criteria[] = $wpdb->prepare( "( ( location_type = 'country' AND location_code = %s )", $country );
			$criteria[] = $wpdb->prepare( "OR ( location_type = 'state' AND location_code = %s )", $country . ':' . $state );
			$criteria[] = $wpdb->prepare( "OR ( location_type = 'continent' AND location_code = %s )", $continent );
			$criteria[] = "OR ( location_type IS NULL ) )";

			// Postcode range and wildcard matching
			$postcode_locations = $wpdb->get_results( "SELECT zone_id, location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_type = 'postcode';" );

			if ( $postcode_locations ) {
				$zone_ids_with_postcode_rules = array_map( 'absint', wp_list_pluck( $postcode_locations, 'zone_id' ) );
				$matches                      = wc_postcode_location_matcher( $postcode, $postcode_locations, 'zone_id', 'location_code', $country );
				$do_not_match                 = array_unique( array_diff( $zone_ids_with_postcode_rules, array_keys( $matches ) ) );

				if ( ! empty( $do_not_match ) ) {
					$criteria[] = "AND zones.zone_id NOT IN (" . implode( ',', $do_not_match ) . ")";
				}
			}

			// Get matching zones
			$matching_zone_id = $wpdb->get_var( "
				SELECT zones.zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones as zones
				LEFT OUTER JOIN {$wpdb->prefix}woocommerce_shipping_zone_locations as locations ON zones.zone_id = locations.zone_id AND location_type != 'postcode'
				WHERE " . implode( ' ', $criteria ) . "
				ORDER BY zone_order ASC LIMIT 1
			" );

			wp_cache_set( $cache_key, $matching_zone_id, 'shipping_zones' );
		}

		return new WC_Shipping_Zone( $matching_zone_id ? $matching_zone_id : 0 );
	}
}
