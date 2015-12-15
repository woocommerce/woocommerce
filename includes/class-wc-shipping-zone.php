<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a single shipping zone
 *
 * @class 		WC_Shipping_Zone
 * @version		2.6.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Shipping_Zone {

	/**
	 * Zone Data
	 * @var array
	 */
    private $data = array(
		'zone_id'        => 0,
		'zone_name'      => '',
		'zone_order'     => 0,
		'zone_locations' => array()
	);

	/**
	 * True when location data needs to be re-saved
	 * @var bool
	 */
	private $_locations_changed = false;

	/**
	 * Constructor for zones
	 * @param int|object $zone Zone ID to load from the DB (optional) or already queried data.
	 */
    public function __construct( $zone = 0 ) {
		if ( is_numeric( $zone ) && ! empty( $zone ) ) {
        	$this->read( $zone );
		} elseif ( is_object( $zone ) ) {
			$this->set_zone_id( $zone->zone_id );
			$this->set_zone_name( $zone->zone_name );
			$this->set_zone_order( $zone->zone_order );
			$this->read_zone_locations( $zone->zone_id );
		} elseif ( 0 === $zone ) {
			$this->set_zone_name( __( 'Worldwide', 'woocommerce' ) );
			$this->read_zone_locations( 0 );
		}
    }

	/**
	 * Get class data array
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get zone ID
	 * @return int
	 */
    public function get_zone_id() {
        return absint( $this->data['zone_id'] );
    }

	/**
	 * Get zone name
	 * @return string
	 */
    public function get_zone_name() {
        return $this->data['zone_name'];
    }

	/**
	 * Get zone order
	 * @return int
	 */
	public function get_zone_order() {
        return absint( $this->data['zone_order'] );
    }

	/**
	 * Get zone locations
	 * @return array of zone objects
	 */
	public function get_zone_locations() {
        return $this->data['zone_locations'];
    }

	/**
	 * Return a text string representing what this zone is for.
	 * @return string
	 */
	public function get_formatted_location( $max = 10 ) {
		$location_parts = array();
		$all_continents = WC()->countries->get_continents();
		$all_countries  = WC()->countries->get_countries();
		$all_states     = WC()->countries->get_states();
		$locations      = $this->get_zone_locations();
		$continents     = array_filter( $locations, array( $this, 'location_is_continent' ) );
		$countries      = array_filter( $locations, array( $this, 'location_is_country' ) );
		$states         = array_filter( $locations, array( $this, 'location_is_state' ) );
		$postcodes      = array_filter( $locations, array( $this, 'location_is_postcode' ) );

		foreach ( $continents as $location ) {
			$location_parts[] = $all_continents[ $location->code ]['name'];
		}

		foreach ( $countries as $location ) {
			$location_parts[] = $all_countries[ $location->code ];
		}

		foreach ( $states as $location ) {
			$location_codes = explode( ':', $location->code );
			$location_parts[] = $all_states[ $location_codes[ 0 ] ][ $location_codes[ 1 ] ];
		}

		foreach ( $postcodes as $location ) {
			$location_parts[] = $location->code;
		}

		if ( sizeof( $location_parts ) > $max ) {
			$remaining = sizeof( $location_parts ) - $max;
			return sprintf( _n( '%s and %d other region', '%s and %d other regions', $remaining, 'woocommerce' ), implode( ', ', array_splice( $location_parts, 0, $max ) ), $remaining );
		} else {
			return implode( ', ', $location_parts );
		}
	}

	/**
	 * Get shipping methods linked to this zone
	 * @return array of objects
	 */
	public function get_shipping_methods() {
		global $wpdb;

        $raw_methods     = $wpdb->get_results( $wpdb->prepare( "SELECT method_id, method_order, instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d order by method_order ASC;", $this->get_zone_id() ) );
		$wc_shipping     = WC_Shipping::instance();
		$allowed_classes = $wc_shipping->get_shipping_method_class_names();
		$methods         = array();

		foreach ( $raw_methods as $raw_method ) {
			if ( in_array( $raw_method->method_id, array_keys( $allowed_classes ) ) ) {
				$class_name                          = $allowed_classes[ $raw_method->method_id ];
				$methods[ $raw_method->instance_id ] = new $class_name( $raw_method->instance_id );
				$methods[ $raw_method->instance_id ]->method_order = absint( $raw_method->method_order );
			}
		}

		return $methods;
	}

	/**
	 * Location type detection
	 * @param  object  $location
	 * @return boolean
	 */
	private function location_is_continent( $location ) {
		return 'continent' === $location->type;
	}

	/**
	 * Location type detection
	 * @param  object  $location
	 * @return boolean
	 */
	private function location_is_country( $location ) {
		return 'country' === $location->type;
	}

	/**
	 * Location type detection
	 * @param  object  $location
	 * @return boolean
	 */
	private function location_is_state( $location ) {
		return 'state' === $location->type;
	}

	/**
	 * Location type detection
	 * @param  object  $location
	 * @return boolean
	 */
	private function location_is_postcode( $location ) {
		return 'postcode' === $location->type;
	}

	/**
	 * Set zone ID
	 * @access private
	 * @param int $set
	 */
    private function set_zone_id( $set ) {
        $this->data['zone_id'] = absint( $set );
    }

	/**
	 * Set zone name
	 * @param string $set
	 */
    public function set_zone_name( $set ) {
		$this->data['zone_name'] = wc_clean( $set );
    }

	/**
	 * Set zone order
	 * @param int $set
	 */
	public function set_zone_order( $set ) {
        $this->data['zone_order'] = absint( $set );
    }

	/**
     * Insert zone into the database
     * @access private
     * @param int Read zone data from DB
     */
    private function read( $zone_id ) {
		global $wpdb;

		if ( $zone_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $zone_id ) ) ) {
			$this->set_zone_id( $zone_data->zone_id );
			$this->set_zone_name( $zone_data->zone_name );
			$this->set_zone_order( $zone_data->zone_order );
			$this->read_zone_locations( $zone_data->zone_id );
		}
	}

	/**
	 * Is passed location type valid?
	 * @param  string  $type
	 * @return boolean
	 */
	public function is_valid_location_type( $type ) {
		return in_array( $type, array( 'postcode', 'state', 'country', 'continent' ) );
	}

	/**
	 * Add location (state or postcode) to a zone.
	 * @param string $code
	 * @param string $type state or postcode
	 */
	public function add_location( $code, $type ) {
		if ( $this->is_valid_location_type( $type ) ) {
			$location = array(
				'code' => wc_clean( $code ),
				'type' => wc_clean( $type )
			);
			$this->data['zone_locations'][] = (object) $location;
			$this->_locations_changed = true;
		}
	}

	/**
	 * Clear all locations for this zone.
	 */
	public function clear_locations() {
		$this->data['zone_locations'] = array();
		$this->_locations_changed = true;
	}

	/**
	 * Set locations
	 * @param array $locations Array of locations
	 */
	public function set_locations( $locations = array() ) {
		$this->clear_locations();

		foreach ( $locations as $location ) {
			$this->add_location( $location['code'], $location['type'] );
		}

		$this->_locations_changed = true;
	}

	/**
	 * Read location data from the database
	 * @param  int $zone_id
	 */
	private function read_zone_locations( $zone_id ) {
		global $wpdb;

		if ( $locations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id = %d;", $zone_id ) ) ) {
			foreach ( $locations as $location ) {
				$this->add_location( $location->location_code, $location->location_type );
			}
		}
		$this->_locations_changed = false;
	}

	/**
     * Save zone data to the database
     * @param array data to save for this zone
     */
    public function save() {
		$data = array(
			'zone_name'  => $this->get_zone_name(),
			'zone_order' => $this->get_zone_order(),
		);

        if ( ! $this->get_zone_id() ) {
			$this->create( $data );
        } else {
            $this->update( $data );
        }

		$this->save_locations();
		WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
	}

	/**
	 * Save locations to the DB
	 *
	 * This function clears old locations, then re-inserts new if any changes are found.
	 */
	private function save_locations() {
		if ( ! $this->get_zone_id() || ! $this->_locations_changed ) {
			return false;
		}
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $this->get_zone_id() ) );

		foreach ( $this->get_zone_locations() as $location ) {
			$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array(
				'zone_id'       => $this->get_zone_id(),
				'location_code' => $location->code,
				'location_type' => $location->type
			) );
		}
	}

	/**
     * Insert zone into the database
     * @access private
     * @param array $zone_data data to save for this zone
     */
    private function create( $zone_data ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zones', $zone_data );
		$this->set_zone_id( $wpdb->insert_id );
	}

    /**
     * Update zone in the database
	 * @access private
     * @param array $zone_data data to save for this zone
     */
    public function update( $zone_data ) {
        global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zones', $zone_data, array( 'zone_id' => $this->get_zone_id() ) );
    }
}
