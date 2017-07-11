<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Shipping Zone Data Store.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Shipping_Zone_Data_Store extends WC_Data_Store_WP implements WC_Shipping_Zone_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Method to create a new shipping zone.
	 *
	 * @since 3.0.0
	 * @param WC_Shipping_Zone $zone
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
	 * Update zone in the database.
	 *
	 * @since 3.0.0
	 * @param WC_Shipping_Zone $zone
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
	 * @since 3.0.0
	 * @param WC_Shipping_Zone $zone
	 * @throws Exception
	 */
	public function read( &$zone ) {
		global $wpdb;
		if ( 0 === $zone->get_id() || "0" === $zone->get_id() ) {
			$this->read_zone_locations( $zone );
			$zone->set_zone_name( __( 'Locations not covered by your other zones', 'woocommerce' ) );
			$zone->read_meta_data();
			$zone->set_object_read( true );
			do_action( 'woocommerce_shipping_zone_loaded', $zone );
		} elseif ( $zone_data = $wpdb->get_row( $wpdb->prepare( "SELECT zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $zone->get_id() ) ) ) {
			$zone->set_zone_name( $zone_data->zone_name );
			$zone->set_zone_order( $zone_data->zone_order );
			$this->read_zone_locations( $zone );
			$zone->read_meta_data();
			$zone->set_object_read( true );
			do_action( 'woocommerce_shipping_zone_loaded', $zone );
		} else {
			throw new Exception( __( 'Invalid data store.', 'woocommerce' ) );
		}
	}

	/**
	 * Deletes a shipping zone from the database.
	 *
	 * @since  3.0.0
	 * @param  WC_Shipping_Zone $zone
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
			$id = $zone->get_id();
			$zone->set_id( null );
			WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
			WC_Cache_Helper::get_transient_version( 'shipping', true );
			do_action( 'woocommerce_delete_shipping_zone', $id );
		}
	}

	/**
	 * Get a list of shipping methods for a specific zone.
	 *
	 * @since  3.0.0
	 * @param  int   $zone_id      Zone ID
	 * @param  bool  $enabled_only True to request enabled methods only.
	 * @return array               Array of objects containing method_id, method_order, instance_id, is_enabled
	 */
	public function get_methods( $zone_id, $enabled_only ) {
		global $wpdb;
		$raw_methods_sql = $enabled_only ? "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND is_enabled = 1;" : "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d;";
		return $wpdb->get_results( $wpdb->prepare( $raw_methods_sql, $zone_id ) );
	}

	/**
	 * Get count of methods for a zone.
	 *
	 * @since  3.0.0
	 * @param  int Zone ID
	 * @return int Method Count
	 */
	public function get_method_count( $zone_id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d", $zone_id ) );
	}

	/**
	 * Add a shipping method to a zone.
	 *
	 * @since  3.0.0
	 * @param  int    $zone_id Zone ID
	 * @param  string $type    Method Type/ID
	 * @param  int    $order   Method Order
	 * @return int             Instance ID
	 */
	public function add_method( $zone_id, $type, $order ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_shipping_zone_methods',
			array(
				'method_id'    => $type,
				'zone_id'      => $zone_id,
				'method_order' => $order,
			),
			array(
				'%s',
				'%d',
				'%d',
			)
		);
		return $wpdb->insert_id;
	}

	/**
	 * Delete a method instance.
	 *
	 * @since 3.0.0
	 * @param int $instance_id
	 */
	public function delete_method( $instance_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'instance_id' => $instance_id ) );
		do_action( 'woocommerce_delete_shipping_zone_method', $instance_id );
	}

	/**
	 * Get a shipping zone method instance.
	 *
	 * @since  3.0.0
	 * @param  int
	 * @return object
	 */
	public function get_method( $instance_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1;", $instance_id ) );
	}

	/**
	 * Find a matching zone ID for a given package.
	 *
	 * @since  3.0.0
	 * @param  object $package
	 * @return int
	 */
	public function get_zone_id_from_package( $package ) {
		global $wpdb;

		$country          = strtoupper( wc_clean( $package['destination']['country'] ) );
		$state            = strtoupper( wc_clean( $package['destination']['state'] ) );
		$continent        = strtoupper( wc_clean( WC()->countries->get_continent_code_for_country( $country ) ) );
		$postcode         = wc_normalize_postcode( wc_clean( $package['destination']['postcode'] ) );

		// Work out criteria for our zone search
		$criteria   = array();
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
		return $wpdb->get_var( "
			SELECT zones.zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones as zones
			LEFT OUTER JOIN {$wpdb->prefix}woocommerce_shipping_zone_locations as locations ON zones.zone_id = locations.zone_id AND location_type != 'postcode'
			WHERE " . implode( ' ', $criteria ) . "
			ORDER BY zone_order ASC LIMIT 1
		" );
	}

	/**
	 * Return an ordered list of zones.
	 *
	 * @since 3.0.0
	 * @return array An array of objects containing a zone_id, zone_name, and zone_order.
	 */
	public function get_zones() {
		global $wpdb;
		return $wpdb->get_results( "SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC;" );
	}


	/**
	 * Return a zone ID from an instance ID.
	 *
	 * @since  3.0.0
	 * @param  int
	 * @return int
	 */
	public function get_zone_id_by_instance_id( $id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods as methods WHERE methods.instance_id = %d LIMIT 1;", $id ) );
	}

	/**
	 * Read location data from the database.
	 *
	 * @param WC_Shipping_Zone
	 */
	private function read_zone_locations( &$zone ) {
		global $wpdb;
		if ( $locations = $wpdb->get_results( $wpdb->prepare( "SELECT location_code, location_type FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id = %d;", $zone->get_id() ) ) ) {
			foreach ( $locations as $location ) {
				$zone->add_location( $location->location_code, $location->location_type );
			}
		}
	}

	/**
	 * Save locations to the DB.
	 * This function clears old locations, then re-inserts new if any changes are found.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Shipping_Zone
	 *
	 * @return bool|void
	 */
	private function save_locations( &$zone ) {
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
