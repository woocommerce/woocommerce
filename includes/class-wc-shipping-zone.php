<?php
include_once( 'legacy/class-wc-legacy-shipping-zone.php' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a single shipping zone
 *
 * @class 		WC_Shipping_Zone
 * @since		2.6.0
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooCommerce
 */
class WC_Shipping_Zone extends WC_Legacy_Shipping_Zone {

	protected $id = null;

	/**
	 * Zone Data
	 * @var array
	 */
	protected $data = array(
		'zone_name'      => '',
		'zone_order'     => 0,
		'zone_locations' => array(),
	);

	/**
	 * Constructor for zones
	 * @param int|object $zone Zone ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $zone = null ) {
		if ( is_numeric( $zone ) && ! empty( $zone ) ) {
			$this->set_id( $zone );
		} elseif ( is_object( $zone ) ) {
			$this->set_id( $zone->zone_id );
		} elseif ( 0 === $zone || "0" === $zone ) {
			$this->set_id( 0 );
		} else {
			$this->set_zone_name( __( 'Zone', 'woocommerce' ) );
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'shipping-zone' );
		if ( ! is_null( $zone ) ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Returns all data for this object.
	 * @return array
	 */
	public function get_data() {
		return array_merge( array( 'id' => $this->get_id(), 'zone_id' => $this->get_id() ), $this->data, array( 'meta_data' => $this->get_meta_data() ) );
	}

	/**
	 * Save zone data to the database.
	 */
	public function save() {
		$name = $this->get_zone_name();
		if ( empty( $name ) ) {
			$this->set_zone_name( $this->generate_zone_name() );
		}
		if ( $this->data_store ) {
			if ( null === $this->get_id() ) {
				$this->data_store->create( $this );
			} else {
				$this->data_store->update( $this );
			}
			return $this->get_id();
		}
	}

	/**
	 * Get zone name
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_zone_name( $context = 'view' ) {
		return $this->get_prop( 'zone_name', $context );
	}

	/**
	 * Get zone order
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_zone_order( $context = 'view' ) {
		return $this->get_prop( 'zone_order', $context );
	}

	/**
	 * Get zone locations
	 *
	 * @param  string $context
	 * @return array of zone objects
	 */
	public function get_zone_locations( $context = 'view' ) {
		return $this->get_prop( 'zone_locations', $context );
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
	 *
	 * @param  int $max
	 * @param  string $context
	 * @return string
	 */
	public function get_formatted_location( $max = 10, $context = 'view' ) {
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
			$location_parts[] = $all_states[ $location_codes[0] ][ $location_codes[1] ];
		}

		foreach ( $postcodes as $location ) {
			$location_parts[] = $location->code;
		}

		// Fix display of encoded characters.
		$location_parts = array_map( 'html_entity_decode', $location_parts );

		if ( sizeof( $location_parts ) > $max ) {
			$remaining = sizeof( $location_parts ) - $max;
			// @codingStandardsIgnoreStart
			return sprintf( _n( '%s and %d other region', '%s and %d other regions', $remaining, 'woocommerce' ), implode( ', ', array_splice( $location_parts, 0, $max ) ), $remaining );
			// @codingStandardsIgnoreEnd
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

		if ( null === $this->get_id() ) {
			return array();
		}

		$raw_methods_sql = $enabled_only ? "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND is_enabled = 1;" : "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d;";
		$raw_methods     = $wpdb->get_results( $wpdb->prepare( $raw_methods_sql, $this->get_id() ) );
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

		uasort( $methods, 'wc_shipping_zone_method_order_uasort_comparison' );

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
	 * Set zone name
	 * @param string $set
	 */
	public function set_zone_name( $set ) {
		$this->set_prop( 'zone_name', wc_clean( $set ) );
	}

	/**
	 * Set zone order
	 * @param int $set
	 */
	public function set_zone_order( $set ) {
		$this->set_prop( 'zone_order', absint( $set ) );
	}

	/**
	 * Set zone locations
	 *
	 * @since 2.7.0
	 * @param array
	 */
	public function set_zone_locations( $locations ) {
		$this->set_prop( 'zone_locations', $locations );
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
				'type' => wc_clean( $type ),
			);
			$zone_locations = $this->get_prop( 'zone_locations', 'edit' );
			$zone_locations[] = (object) $location;
			$this->set_prop( 'zone_locations', $zone_locations );
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
		$zone_locations = $this->get_prop( 'zone_locations', 'edit' );
		foreach ( $zone_locations as $key => $values ) {
			if ( in_array( $values->type, $types ) ) {
				unset( $zone_locations[ $key ] );
			}
		}
		$this->set_prop( 'zone_locations', $zone_locations );
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
	}

	/**
	 * Add a shipping method to this zone.
	 * @param string $type shipping method type
	 * @return int new instance_id, 0 on failure
	 */
	public function add_shipping_method( $type ) {
		global $wpdb;

		if ( null === $this->get_id() ) {
			$this->save();
		}

		$instance_id     = 0;
		$wc_shipping     = WC_Shipping::instance();
		$allowed_classes = $wc_shipping->get_shipping_method_class_names();
		$count           = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d", $this->get_id() ) );

		if ( in_array( $type, array_keys( $allowed_classes ) ) ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array(
					'method_id'    => $type,
					'zone_id'      => $this->get_id(),
					'method_order' => ( $count + 1 ),
				),
				array(
					'%s',
					'%d',
					'%d',
				)
			);
			$instance_id = $wpdb->insert_id;
		}

		if ( $instance_id ) {
			do_action( 'woocommerce_shipping_zone_method_added', $instance_id, $type, $this->get_id() );
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		return $instance_id;
	}

	/**
	 * Delete a shipping method from a zone.
	 * @param int $instance_id
	 * @return True on success, false on failure
	 */
	public function delete_shipping_method( $instance_id ) {
		global $wpdb;

		if ( null === $this->get_id() ) {
			return false;
		}

		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'instance_id' => $instance_id ) );
		do_action( 'woocommerce_shipping_zone_method_deleted', $instance_id, $this->get_id() );

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		return true;
	}
}
