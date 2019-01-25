<?php

/**
 * Class WC_Helper_Shipping_Zones.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Shipping_Zones {

	/**
	 * Create some mock shipping zones to test against
	 */
	public static function create_mock_zones() {
		self::remove_mock_zones();

		// Local zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Local' );
		$zone->set_zone_order( 1 );
		$zone->add_location( 'GB', 'country' );
		$zone->add_location( 'CB*', 'postcode' );
		$zone->save();

		// Europe zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Europe' );
		$zone->set_zone_order( 2 );
		$zone->add_location( 'EU', 'continent' );
		$zone->save();

		// US california zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'California' );
		$zone->set_zone_order( 3 );
		$zone->add_location( 'US:CA', 'state' );
		$zone->save();

		// US zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'US' );
		$zone->set_zone_order( 4 );
		$zone->add_location( 'US', 'country' );
		$zone->save();
	}

	/**
	 * Remove all zones
	 */
	public static function remove_mock_zones() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_locations;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zones;" );
		WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
	}
}
