<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Shipping Zone Data Store: Custom Table.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Shipping_Zone_Data_Store_Table implements WC_Shipping_Zone_Data_Store_Interface, WC_Object_Data_Store {

	/**
	 * Method to create a new shipping zone.
	 *
	 * @since 2.7.0
	 * @param WC_Shipping_Zone
	 */
	public function create( &$zone ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zones', array(
			'zone_name'  => $zone->get_zone_name(),
			'zone_order' => $zone->get_zone_order(),
		) );
		$zone->set_id( $wpdb->insert_id );
		$zone->save_meta_data();
		$this->save_locations( $zone );
		$zone->apply_changes();
		WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Update zone in the database
	 *
	 * @since 2.7.0
	 * @param WC_Shipping_Zone
	 */
	public function update( &$zone ) {
		global $wpdb;
		if ( $zone->get_id() ) {
			$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zones', array(
				'zone_name'  => $zone->get_zone_name(),
				'zone_order' => $zone->get_zone_order(),
			), array( 'zone_id' => $zone->get_id() ) );
		}
		$zone->save_meta_data();
		$this->save_locations( $zone );
		$zone->apply_changes();
		WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}


	/**
	 * Method to read a shipping zone from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Shipping_Zone
	 */
	public function read( &$zone ) {
		global $wpdb;
		if ( 0 === $zone->get_id() || "0" === $zone->get_id() ) {
			$this->read_zone_locations( $zone );
			$zone->set_zone_name( __( 'Rest of the World', 'woocommerce' ) );
			$zone->read_meta_data();
			$zone->set_object_read( true );
			do_action( 'woocommerce_shipping_zone_loaded', $zone );
		} elseif ( $zone_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $zone->get_id() ) ) ) {
			$zone->set_zone_name( $zone_data->zone_name );
			$zone->set_zone_order( $zone_data->zone_order );
			$this->read_zone_locations( $zone );
			$zone->read_meta_data();
			$zone->set_object_read( true );
			do_action( 'woocommerce_shipping_zone_loaded', $zone );
		}
	}

	/**
	 * Deletes a shipping zone from the database.
	 *
	 * @since 2.7.0
	 * @param  WC_Shipping_Zone
	 * @param  array $args Array of args to pass to the delete method.
	 * @return bool result
	 */
	public function delete( &$zone, $args = array() ) {
		if ( $zone->get_id() ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'zone_id' => $zone->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $zone->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zones', array( 'zone_id' => $zone->get_id() ) );
			WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
			$zone->set_id( null );
		}
	}

	/**
	 * Read location data from the database.
	 *
	 * @param WC_Shipping_Zone
	 */
	private function read_zone_locations( &$zone ) {
		global $wpdb;
		if ( $locations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id = %d;", $zone->get_id() ) ) ) {
			foreach ( $locations as $location ) {
				$zone->add_location( $location->location_code, $location->location_type );
			}
		}
	}

	/**
	 * Save locations to the DB.
	 * This function clears old locations, then re-inserts new if any changes are found.
	 *
	 * @since 2.7.0
	 * @param WC_Shipping_Zone
	 */
	private function save_locations( &$zone ) {
		$updated_props = array();
		$changed_props = array_keys( $zone->get_changes() );
		if ( ! in_array( 'zone_locations', $changed_props ) ) {
			return false;
		}

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $zone->get_id() ) );

		foreach ( $zone->get_zone_locations( 'edit' ) as $location ) {
			$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array(
				'zone_id'       => $zone->get_id(),
				'location_code' => $location->code,
				'location_type' => $location->type,
			) );
		}
	}

}
