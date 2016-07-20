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
class WC_Shipping_Zone extends WC_Data {

	/**
	 * Zone Data
	 * @var array
	 */
	protected $_data = array(
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
			$this->set_zone_name( __( 'Rest of the World', 'woocommerce' ) );
			$this->read_zone_locations( 0 );
		} else {
			$this->set_zone_name( __( 'Zone', 'woocommerce' ) );
		}
	}

	/**
	 * Get ID
	 * @return int
	 */
	public function get_id() {
		return $this->get_zone_id();
	}

	/**
	 * Insert zone into the database
	 */
	public function create() {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zones', array(
			'zone_name'  => $this->get_zone_name(),
			'zone_order' => $this->get_zone_order(),
		) );
		$this->set_zone_id( $wpdb->insert_id );
	}

	/**
	 * Read zone.
	 * @param int ID to read from DB
	 */
	public function read( $id ) {
		global $wpdb;

		if ( $zone_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id = %d LIMIT 1;", $id ) ) ) {
			$this->set_zone_id( $zone_data->zone_id );
			$this->set_zone_name( $zone_data->zone_name );
			$this->set_zone_order( $zone_data->zone_order );
			$this->read_zone_locations( $zone_data->zone_id );
		}
	}

	/**
	 * Update zone in the database
	 */
	public function update() {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zones', array(
			'zone_name'  => $this->get_zone_name(),
			'zone_order' => $this->get_zone_order(),
		), array( 'zone_id' => $this->get_zone_id() ) );
	}

	/**
	 * Delete a zone.
	 * @since 2.6.0
	 */
	public function delete() {
		if ( $this->get_id() ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'zone_id' => $this->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $this->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zones', array( 'zone_id' => $this->get_id() ) );
			WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );
			$this->set_zone_id( 0 );
		}
	}

	/**
	 * Save zone data to the database.
	 */
	public function save() {
		$name = $this->get_zone_name();

		if ( empty( $name ) ) {
			$this->set_zone_name( $this->generate_zone_name() );
		}

		if ( ! $this->get_zone_id() ) {
			$this->create();
		} else {
			$this->update();
		}

		$this->save_locations();
		WC_Cache_Helper::incr_cache_prefix( 'shipping_zones' );

		// Increments the transient version to invalidate cache.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Get zone ID
	 * @return int
	 */
	public function get_zone_id() {
		return absint( $this->_data['zone_id'] );
	}

	/**
	 * Get zone name
	 * @return string
	 */
	public function get_zone_name() {
		return $this->_data['zone_name'];
	}

	/**
	 * Get zone order
	 * @return int
	 */
	public function get_zone_order() {
		return absint( $this->_data['zone_order'] );
	}

	/**
	 * Get zone locations
	 * @return array of zone objects
	 */
	public function get_zone_locations() {
		return $this->_data['zone_locations'];
	}

	/**
	 * Generate a zone name based on location.
	 * @return string
	 */
	protected function generate_zone_name() {
		$zone_name = $this->get_formatted_location();

		if ( empty( $zone_name ) ) {
			$zone_name = __( 'Zone', 'woocommerce' );
		}

		return $zone_name;
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

		// Fix display of encoded characters.
		$location_parts = array_map( 'html_entity_decode', $location_parts );

		if ( sizeof( $location_parts ) > $max ) {
			$remaining = sizeof( $location_parts ) - $max;
			return sprintf( _n( '%s and %d other region', '%s and %d other regions', $remaining, 'woocommerce' ), implode( ', ', array_splice( $location_parts, 0, $max ) ), $remaining );
		} elseif ( ! empty( $location_parts ) ) {
			return implode( ', ', $location_parts );
		} else {
			return __( 'Everywhere', 'woocommerce' );
		}
	}

	/**
	 * Get shipping methods linked to this zone
	 * @param bool Only return enabled methods.
	 * @return array of objects
	 */
	public function get_shipping_methods( $enabled_only = false ) {
		global $wpdb;

		$raw_methods_sql = $enabled_only ? "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND is_enabled = 1 order by method_order ASC;" : "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d order by method_order ASC;";
		$raw_methods     = $wpdb->get_results( $wpdb->prepare( $raw_methods_sql, $this->get_zone_id() ) );
		$wc_shipping     = WC_Shipping::instance();
		$allowed_classes = $wc_shipping->get_shipping_method_class_names();
		$methods         = array();

		foreach ( $raw_methods as $raw_method ) {
			if ( in_array( $raw_method->method_id, array_keys( $allowed_classes ), true ) ) {
				$class_name = $allowed_classes[ $raw_method->method_id ];

				// The returned array may contain instances of shipping methods, as well
				// as classes. If the "class" is an instance, just use it. If not,
				// create an instance.
				if ( is_object( $class_name ) ) {
					$class_name_of_instance = get_class( $class_name );
					$methods[ $raw_method->instance_id ] = new $class_name_of_instance( $raw_method->instance_id );
				} else {
					// If the class is not an object, it should be a string. It's better
					// to double check, to be sure (a class must be a string, anything)
					// else would be useless
					if ( is_string( $class_name ) && class_exists( $class_name ) ) {
						$methods[ $raw_method->instance_id ] = new $class_name( $raw_method->instance_id );
					}
				}

				// Let's make sure that we have an instance before setting its attributes
				if ( is_object( $methods[ $raw_method->instance_id ] ) ) {
					$methods[ $raw_method->instance_id ]->method_order  = absint( $raw_method->method_order );
					$methods[ $raw_method->instance_id ]->enabled       = $raw_method->is_enabled ? 'yes' : 'no';
					$methods[ $raw_method->instance_id ]->has_settings  = $methods[ $raw_method->instance_id ]->has_settings();
					$methods[ $raw_method->instance_id ]->settings_html = $methods[ $raw_method->instance_id ]->supports( 'instance-settings-modal' ) ? $methods[ $raw_method->instance_id ]->get_admin_options_html() : false;
				}
			}
		}

		return apply_filters( 'woocommerce_shipping_zone_shipping_methods', $methods, $raw_methods, $allowed_classes, $this );
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
		$this->_data['zone_id'] = absint( $set );
	}

	/**
	 * Set zone name
	 * @param string $set
	 */
	public function set_zone_name( $set ) {
		$this->_data['zone_name'] = wc_clean( $set );
	}

	/**
	 * Set zone order
	 * @param int $set
	 */
	public function set_zone_order( $set ) {
		$this->_data['zone_order'] = absint( $set );
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
			if ( 'postcode' === $type ) {
				$code = trim( strtoupper( str_replace( chr( 226 ) . chr( 128 ) . chr( 166 ), '...', $code ) ) ); // No normalization - postcodes are matched against both normal and formatted versions to support wildcards.
			}
			$location = array(
				'code' => wc_clean( $code ),
				'type' => wc_clean( $type )
			);
			$this->_data['zone_locations'][] = (object) $location;
			$this->_locations_changed = true;
		}
	}

	/**
	 * Clear all locations for this zone.
	 * @param array|string $types of location to clear
	 */
	public function clear_locations( $types = array( 'postcode', 'state', 'country', 'continent' ) ) {
		if ( ! is_array( $types ) ) {
			$types = array( $types );
		}
		foreach ( $this->_data['zone_locations'] as $key => $values ) {
			if ( in_array( $values->type, $types ) ) {
				unset( $this->_data['zone_locations'][ $key ] );
				$this->_locations_changed = true;
			}
		}
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
	 * Save locations to the DB.
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
	 * Add a shipping method to this zone.
	 * @param string $type shipping method type
	 * @return int new instance_id, 0 on failure
	 */
	public function add_shipping_method( $type ) {
		global $wpdb;

		$instance_id     = 0;
		$wc_shipping     = WC_Shipping::instance();
		$allowed_classes = $wc_shipping->get_shipping_method_class_names();
		$count           = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d", $this->get_zone_id() ) );

		if ( in_array( $type, array_keys( $allowed_classes ) ) ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array(
					'method_id'    => $type,
					'zone_id'      => $this->get_zone_id(),
					'method_order' => ( $count + 1 )
				),
				array(
					'%s',
					'%d',
					'%d'
				)
			);
			$instance_id = $wpdb->insert_id;
		}

		if ( $instance_id ) {
			do_action( 'woocommerce_shipping_zone_method_added', $instance_id, $type, $this->get_zone_id() );
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		return $instance_id;
	}
}
