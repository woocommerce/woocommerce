<?php
/**
 * Handles storage and retrieval of shipping zones
 *
 * @package WooCommerce\Classes
 * @version 3.3.0
 * @since   2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping zones class.
 */
class WC_Shipping_Zones {

	/**
	 * Get shipping zones from the database.
	 *
	 * @since 2.6.0
	 * @param string $context Getting shipping methods for what context. Valid values, admin, json.
	 * @return array Array of arrays.
	 */
	public static function get_zones( $context = 'admin' ) {
		$zone_objects = self::get_shipping_zones();
		$zones        = array();

		foreach ( $zone_objects as $zone_object ) {
			$zones[ $zone_object->get_id() ]                            = $zone_object->get_data();
			$zones[ $zone_object->get_id() ]['zone_id']                 = $zone_object->get_id();
			$zones[ $zone_object->get_id() ]['formatted_zone_location'] = $zone_object->get_formatted_location();
			$zones[ $zone_object->get_id() ]['shipping_methods']        = $zone_object->get_shipping_methods( false, $context );
		}

		return $zones;
	}

	/**
	 * Get shipping zones from the database with admin warnings for zones that may not match because of their order.
	 *
	 * @since 11.1.0
	 * @param string $context Getting shipping methods for what context. Valid values, admin, json.
	 * @return array Array of arrays.
	 */
	public static function get_zones_with_order_conflict_warnings( $context = 'admin' ) {
		$zones = self::get_zones( $context );

		return self::add_zone_order_conflict_warnings( $zones );
	}

	/**
	 * Add warnings to zones that are fully covered by a higher-priority zone.
	 *
	 * @since 11.1.0
	 * @param array $zones Shipping zones.
	 * @return array Shipping zones with order conflict warnings.
	 */
	private static function add_zone_order_conflict_warnings( $zones ) {
		$ordered_zones         = $zones;
		$higher_priority_zones = array();

		uasort(
			$ordered_zones,
			function ( $zone_a, $zone_b ) {
				$order_a = absint( $zone_a['zone_order'] ?? 0 );
				$order_b = absint( $zone_b['zone_order'] ?? 0 );

				if ( $order_a === $order_b ) {
					return absint( $zone_a['zone_id'] ?? 0 ) <=> absint( $zone_b['zone_id'] ?? 0 );
				}

				return $order_a <=> $order_b;
			}
		);

		foreach ( $ordered_zones as $zone ) {
			$shadowing_zone = self::get_shadowing_zone( $zone, $higher_priority_zones );

			if ( $shadowing_zone ) {
				$zones[ $zone['zone_id'] ]['zone_order_conflict_warning'] = sprintf(
					/* translators: %1$s: Higher-priority shipping zone name. */
					__( 'This zone will not be matched because "%1$s" covers the same region earlier in the list. Move this zone above "%1$s" to make it available.', 'woocommerce' ),
					$shadowing_zone['zone_name']
				);
			}

			$higher_priority_zones[] = $zone;
		}

		return $zones;
	}

	/**
	 * Get the first higher-priority zone that fully covers a zone.
	 *
	 * @since 11.1.0
	 * @param array $zone Zone to check.
	 * @param array $higher_priority_zones Higher-priority zones.
	 * @return array|null Shadowing zone, or null when none exists.
	 */
	private static function get_shadowing_zone( $zone, $higher_priority_zones ) {
		foreach ( $higher_priority_zones as $higher_priority_zone ) {
			if ( self::zone_covers_zone( $higher_priority_zone, $zone ) ) {
				return $higher_priority_zone;
			}
		}

		return null;
	}

	/**
	 * Check whether a possible broader zone fully covers another zone.
	 *
	 * @since 11.1.0
	 * @param array $possible_broader_zone Possible broader zone.
	 * @param array $zone Zone to check.
	 * @return bool Whether the possible broader zone fully covers the zone.
	 */
	private static function zone_covers_zone( $possible_broader_zone, $zone ) {
		if ( self::zone_has_postcode_locations( $possible_broader_zone ) ) {
			return false;
		}

		if (
			self::zone_has_unsupported_non_postcode_locations( $possible_broader_zone ) ||
			self::zone_has_unsupported_non_postcode_locations( $zone )
		) {
			return false;
		}

		$broader_locations = self::get_zone_locations_by_type( $possible_broader_zone, array( 'continent', 'country', 'state' ) );
		$zone_locations    = self::get_zone_locations_by_type( $zone, array( 'continent', 'country', 'state' ) );

		if ( empty( $zone_locations ) ) {
			return empty( $broader_locations );
		}

		if ( empty( $broader_locations ) ) {
			return true;
		}

		foreach ( $zone_locations as $zone_location ) {
			$is_location_covered = false;

			foreach ( $broader_locations as $broader_location ) {
				if ( self::location_covers_location( $broader_location, $zone_location ) ) {
					$is_location_covered = true;
					break;
				}
			}

			if ( ! $is_location_covered ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether a zone has postcode locations.
	 *
	 * @since 11.1.0
	 * @param array $zone Shipping zone.
	 * @return bool Whether the zone has postcode locations.
	 */
	private static function zone_has_postcode_locations( $zone ) {
		return ! empty( self::get_zone_locations_by_type( $zone, array( 'postcode' ) ) );
	}

	/**
	 * Check whether a zone has non-postcode locations not understood by this warning helper.
	 *
	 * @since 11.1.0
	 * @param array $zone Shipping zone.
	 * @return bool Whether the zone has unsupported non-postcode locations.
	 */
	private static function zone_has_unsupported_non_postcode_locations( $zone ) {
		$locations                = $zone['zone_locations'] ?? array();
		$supported_location_types = array( 'continent', 'country', 'state', 'postcode' );

		foreach ( $locations as $location ) {
			if ( isset( $location->type ) && ! in_array( $location->type, $supported_location_types, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get zone locations for the requested location types.
	 *
	 * @since 11.1.0
	 * @param array $zone Shipping zone.
	 * @param array $location_types Location types.
	 * @return array Zone locations.
	 */
	private static function get_zone_locations_by_type( $zone, $location_types ) {
		$locations = $zone['zone_locations'] ?? array();

		return array_values(
			array_filter(
				$locations,
				function ( $location ) use ( $location_types ) {
					return isset( $location->type ) && in_array( $location->type, $location_types, true );
				}
			)
		);
	}

	/**
	 * Check whether one location covers another location.
	 *
	 * @since 11.1.0
	 * @param object $possible_broader_location Possible broader location.
	 * @param object $location Location to check.
	 * @return bool Whether the possible broader location covers the location.
	 */
	private static function location_covers_location( $possible_broader_location, $location ) {
		$broader_type = $possible_broader_location->type ?? '';
		$broader_code = $possible_broader_location->code ?? '';
		$type         = $location->type ?? '';
		$code         = $location->code ?? '';

		if ( $broader_type === $type && $broader_code === $code ) {
			return true;
		}

		if ( 'continent' === $broader_type ) {
			return self::get_continent_code_for_location( $location ) === $broader_code;
		}

		if ( 'country' === $broader_type ) {
			return 'state' === $type && self::get_country_code_from_state_code( $code ) === $broader_code;
		}

		return false;
	}

	/**
	 * Get the continent code for a country or state location.
	 *
	 * @since 11.1.0
	 * @param object $location Location.
	 * @return string Continent code, or an empty string.
	 */
	private static function get_continent_code_for_location( $location ) {
		$type = $location->type ?? '';
		$code = $location->code ?? '';

		if ( 'continent' === $type ) {
			return $code;
		}

		$country_code = 'state' === $type ? self::get_country_code_from_state_code( $code ) : $code;

		return WC()->countries->get_continent_code_for_country( $country_code );
	}

	/**
	 * Get the country code from a state location code.
	 *
	 * @since 11.1.0
	 * @param string $state_code State location code.
	 * @return string Country code.
	 */
	private static function get_country_code_from_state_code( $state_code ) {
		$state_code_parts = explode( ':', $state_code );

		return $state_code_parts[0] ?? '';
	}

	/**
	 * Retrieve Shipping_Zone data objects for the given zone_ids.
	 *
	 * @param array|null $zone_ids The zone_ids of the zones to retrieve. An empty array will return no results. Use null for all zones.
	 *
	 * @return WC_Shipping_Zone[]
	 */
	public static function get_shipping_zones( ?array $zone_ids = null ) {
		$data_store = WC_Data_Store::load( 'shipping-zone' );
		if ( null === $zone_ids ) {
			$raw_zones = $data_store->get_zones();
			$zone_ids  = array_column( $raw_zones, 'zone_id' );
		} elseif ( empty( $zone_ids ) ) {
			return array();
		}

		$zones = array();
		foreach ( $zone_ids as $zone_id ) {
			$zone = new WC_Shipping_Zone();
			$zone->set_object_read( false );
			$zone->set_id( $zone_id );
			$zones[ $zone_id ] = $zone;
		}

		if ( ! empty( $zones ) ) {
			$data_store->read_multiple( $zones );
		}

		return $zones;
	}

	/**
	 * Get shipping zone using it's ID
	 *
	 * @since 2.6.0
	 * @param int $zone_id Zone ID.
	 * @return WC_Shipping_Zone|bool
	 */
	public static function get_zone( $zone_id ) {
		return self::get_zone_by( 'zone_id', $zone_id );
	}

	/**
	 * Get shipping zone by an ID.
	 *
	 * @since 2.6.0
	 * @param string $by Get by 'zone_id' or 'instance_id'.
	 * @param int    $id ID.
	 * @return WC_Shipping_Zone|bool
	 */
	public static function get_zone_by( $by = 'zone_id', $id = 0 ) {
		$zone_id = false;

		switch ( $by ) {
			case 'zone_id':
				$zone_id = $id;
				break;
			case 'instance_id':
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
	 * Get shipping zone using it's ID.
	 *
	 * @since 2.6.0
	 * @param int $instance_id Instance ID.
	 * @return bool|WC_Shipping_Method
	 */
	public static function get_shipping_method( $instance_id ) {
		$data_store          = WC_Data_Store::load( 'shipping-zone' );
		$raw_shipping_method = $data_store->get_method( $instance_id );
		$wc_shipping         = WC_Shipping::instance();
		$allowed_classes     = $wc_shipping->get_shipping_method_class_names();

		if ( ! empty( $raw_shipping_method ) && in_array( $raw_shipping_method->method_id, array_keys( $allowed_classes ), true ) ) {
			$class_name = $allowed_classes[ $raw_shipping_method->method_id ];
			if ( is_object( $class_name ) ) {
				$class_name = get_class( $class_name );
			}
			$instance               = new $class_name( $raw_shipping_method->instance_id );
			$instance->enabled      = $raw_shipping_method->is_enabled ? 'yes' : 'no';
			$instance->method_order = (int) $raw_shipping_method->method_order;
			return $instance;
		}
		return false;
	}

	/**
	 * Delete a zone using it's ID
	 *
	 * @param int $zone_id Zone ID.
	 * @since 2.6.0
	 */
	public static function delete_zone( $zone_id ) {
		$zone = new WC_Shipping_Zone( $zone_id );
		$zone->delete();
	}

	/**
	 * Find a matching zone for a given package.
	 *
	 * @since  2.6.0
	 * @uses   wc_make_numeric_postcode()
	 * @param  array $package Shipping package.
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
