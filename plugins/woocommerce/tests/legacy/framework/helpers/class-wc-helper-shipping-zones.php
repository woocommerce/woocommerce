<?php

/**
 * Class WC_Helper_Shipping_Zones.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Shipping_Zones {

	/**
	 * Create some mock shipping zones to test against.
	 *
	 * @return int[] Zone IDs keyed by fixture name.
	 */
	public static function create_mock_zones() {
		self::remove_mock_zones();
		$zone_ids = array();

		// Local zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Local' );
		$zone->set_zone_order( 1 );
		$zone->add_location( 'GB', 'country' );
		$zone->add_location( 'CB*', 'postcode' );
		$zone->save();
		$zone_ids['local'] = $zone->get_id();

		// Europe zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Europe' );
		$zone->set_zone_order( 2 );
		$zone->add_location( 'EU', 'continent' );
		$zone->save();
		$zone_ids['europe'] = $zone->get_id();

		// US california zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'California' );
		$zone->set_zone_order( 3 );
		$zone->add_location( 'US:CA', 'state' );
		$zone->save();
		$zone_ids['california'] = $zone->get_id();

		// US zone
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'US' );
		$zone->set_zone_order( 4 );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone_ids['us'] = $zone->get_id();

		return $zone_ids;
	}

	/**
	 * Remove all zones
	 */
	public static function remove_mock_zones() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_shipping_zone_methods;" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_shipping_zone_locations;" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_shipping_zones;" );
		WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );
	}
}
