<?php
/**
 * WooCommerce Shipping Class
 *
 * Handles shipping and loads shipping methods via hooks.
 *
 * @class 		WC_Shipping
 * @version		2.6.0
 * @package		WooCommerce/Classes/Shipping
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping
 */
class WC_Shipping {

	/** @var bool True if shipping is enabled. */
	public $enabled					 = false;

	/** @var array|null Stores methods loaded into woocommerce. */
	public $shipping_methods         = null;

	/** @var float Stores the cost of shipping */
	public $shipping_total 			 = 0;

	/** @var array Stores an array of shipping taxes. */
	public $shipping_taxes			 = array();

	/** @var array Stores the shipping classes. */
	public $shipping_classes		 = array();

	/** @var array Stores packages to ship and to get quotes for. */
	public $packages				 = array();

	/**
	 * @var WC_Shipping The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Shipping Instance.
	 *
	 * Ensures only one instance of WC_Shipping is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @return WC_Shipping Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Initialize shipping.
	 */
	public function __construct() {
		$this->enabled = wc_shipping_enabled();

		if ( $this->enabled ) {
			$this->init();
		}
	}

    /**
     * Initialize shipping.
     */
    public function init() {
		do_action( 'woocommerce_shipping_init' );
	}

	/**
	 * Shipping methods register themselves by returning their main class name through the woocommerce_shipping_methods filter.
	 * @return array
	 */
	public function get_shipping_method_class_names() {
		// Unique Method ID => Method Class name
		$shipping_methods = array(
			'flat_rate'     => 'WC_Shipping_Flat_Rate',
			'free_shipping' => 'WC_Shipping_Free_Shipping',
			'local_pickup'  => 'WC_Shipping_Local_Pickup',
		);

		// For backwards compatibility with 2.5.x we load any ENABLED legacy shipping methods here
		$maybe_load_legacy_methods = array( 'flat_rate', 'free_shipping', 'international_delivery', 'local_delivery', 'local_pickup' );

		foreach ( $maybe_load_legacy_methods as $method ) {
			$options = get_option( 'woocommerce_' . $method . '_settings' );
			if ( $options && isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
				$shipping_methods[ 'legacy_' . $method ] = 'WC_Shipping_Legacy_' . $method;
			}
		}

		return apply_filters( 'woocommerce_shipping_methods', $shipping_methods );
	}

	/**
	 * Loads all shipping methods which are hooked in. If a $package is passed some methods may add themselves conditionally.
	 *
	 * Loads all shipping methods which are hooked in.
	 * If a $package is passed some methods may add themselves conditionally and zones will be used.
	 *
	 * @param array $package
	 * @return array
	 */
	public function load_shipping_methods( $package = array() ) {
		if ( ! empty( $package ) ) {
			$status_options         = get_option( 'woocommerce_status_options', array() );
			$shipping_zone          = WC_Shipping_Zones::get_zone_matching_package( $package );
			$this->shipping_methods = $shipping_zone->get_shipping_methods( true );

			// Debug output
			if ( ! empty( $status_options['shipping_debug_mode'] ) && ! defined( 'WOOCOMMERCE_CHECKOUT' ) && ! wc_has_notice( 'Customer matched zone "' . $shipping_zone->get_zone_name() . '"' ) ) {
				wc_add_notice( 'Customer matched zone "' . $shipping_zone->get_zone_name() . '"' );
			}
		} else {
			$this->shipping_methods = array();
		}

		// For the settings in the backend, and for non-shipping zone methods, we still need to load any registered classes here.
		foreach ( $this->get_shipping_method_class_names() as $method_id => $method_class ) {
			$this->register_shipping_method( $method_class );
		}

		// Methods can register themselves manually through this hook if necessary.
		do_action( 'woocommerce_load_shipping_methods', $package );

		// Return loaded methods
		return $this->get_shipping_methods();
	}

	/**
	 * Register a shipping method.
	 *
	 * @param object|string $method Either the name of the method's class, or an instance of the method's class.
	 */
	public function register_shipping_method( $method ) {
		if ( ! is_object( $method ) ) {
			if ( ! class_exists( $method ) ) {
				return false;
			}
			$method = new $method();
		}
		if ( is_null( $this->shipping_methods ) ) {
			$this->shipping_methods = array();
		}
		$this->shipping_methods[ $method->id ] = $method;
	}

	/**
	 * Unregister shipping methods.
	 */
	public function unregister_shipping_methods() {
		$this->shipping_methods = null;
	}

	/**
	 * Returns all registered shipping methods for usage.
	 *
	 * @access public
	 * @return array
	 */
	public function get_shipping_methods() {
		if ( is_null( $this->shipping_methods ) ) {
			$this->load_shipping_methods();
		}
		return $this->shipping_methods;
	}

	/**
	 * Get an array of shipping classes.
	 *
	 * @access public
	 * @return array
	 */
	public function get_shipping_classes() {
		if ( empty( $this->shipping_classes ) ) {
			$classes                = get_terms( 'product_shipping_class', array( 'hide_empty' => '0', 'orderby' => 'name' ) );
			$this->shipping_classes = ! is_wp_error( $classes ) ? $classes : array();
		}
		return apply_filters( 'woocommerce_get_shipping_classes', $this->shipping_classes );
	}

	/**
	 * Get the default method.
	 * @param  array  $available_methods
	 * @param  boolean $current_chosen_method
	 * @return string
	 */
	private function get_default_method( $available_methods, $current_chosen_method = false ) {
		if ( ! empty( $available_methods ) ) {
			if ( ! empty( $current_chosen_method ) ) {
				if ( isset( $available_methods[ $current_chosen_method ] ) ) {
					return $available_methods[ $current_chosen_method ]->id;
				} else {
					foreach ( $available_methods as $method_key => $method ) {
						if ( strpos( $method->id, $current_chosen_method ) === 0 ) {
							return $method->id;
						}
					}
				}
			}
			return current( $available_methods )->id;
		}
		return '';
	}

	/**
	 * Calculate shipping for (multiple) packages of cart items.
	 *
	 * @param array $packages multi-dimensional array of cart items to calc shipping for
	 */
	public function calculate_shipping( $packages = array() ) {
		$this->shipping_total = null;
		$this->shipping_taxes = array();
		$this->packages       = array();

		if ( ! $this->enabled || empty( $packages ) ) {
			return;
		}

		// Calculate costs for passed packages
		foreach ( $packages as $package_key => $package ) {
			$this->packages[ $package_key ] = $this->calculate_shipping_for_package( $package, $package_key );
		}

		/**
		 * Allow packages to be reorganized after calculate the shipping.
		 *
		 * This filter can be used to apply some extra manipulation after the shipping costs are calculated for the packages
		 * but before Woocommerce does anything with them. A good example of usage is to merge the shipping methods for multiple
		 * packages for marketplaces.
		 *
		 * @since 2.6.0
		 *
		 * @param array $packages The array of packages after shipping costs are calculated.
		 */
		$this->packages = apply_filters( 'woocommerce_shipping_packages', $this->packages );

		if ( ! is_array( $this->packages ) || empty( $this->packages ) ) {
			return;
		}

		// Get all chosen methods
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$method_counts  = WC()->session->get( 'shipping_method_counts' );

		// Get chosen methods for each package
		foreach ( $this->packages as $i => $package ) {
			$chosen_method    = false;
			$method_count     = false;

			if ( ! empty( $chosen_methods[ $i ] ) ) {
				$chosen_method = $chosen_methods[ $i ];
			}

			if ( ! empty( $method_counts[ $i ] ) ) {
				$method_count = absint( $method_counts[ $i ] );
			}

			if ( sizeof( $package['rates'] ) > 0 ) {

				// If not set, not available, or available methods have changed, set to the DEFAULT option
				if ( empty( $chosen_method ) || ! isset( $package['rates'][ $chosen_method ] ) || $method_count !== sizeof( $package['rates'] ) ) {
					$chosen_method        = apply_filters( 'woocommerce_shipping_chosen_method', $this->get_default_method( $package['rates'], false ), $package['rates'], $chosen_method );
					$chosen_methods[ $i ] = $chosen_method;
					$method_counts[ $i ]  = sizeof( $package['rates'] );
					do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
				}

				// Store total costs
				if ( $chosen_method ) {
					$rate = $package['rates'][ $chosen_method ];

					// Merge cost and taxes - label and ID will be the same
					$this->shipping_total += $rate->cost;

					if ( ! empty( $rate->taxes ) && is_array( $rate->taxes ) ) {
						foreach ( array_keys( $this->shipping_taxes + $rate->taxes ) as $key ) {
							$this->shipping_taxes[ $key ] = ( isset( $rate->taxes[$key] ) ? $rate->taxes[$key] : 0 ) + ( isset( $this->shipping_taxes[$key] ) ? $this->shipping_taxes[$key] : 0 );
						}
					}
				}
			}
		}

		// Save all chosen methods (array)
		WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
		WC()->session->set( 'shipping_method_counts', $method_counts );
	}

	/**
	 * Calculate shipping rates for a package,
	 *
	 * Calculates each shipping methods cost. Rates are stored in the session based on the package hash to avoid re-calculation every page load.
	 *
	 * @param array $package cart items
	 * @param int   $package_key Index of the package being calculated. Used to cache multiple package rates.
	 * @return array
	 */
	public function calculate_shipping_for_package( $package = array(), $package_key = 0 ) {
		if ( ! $this->enabled || empty( $package ) ) {
			return false;
		}

		// Check if we need to recalculate shipping for this package
		$package_hash   = 'wc_ship_' . md5( json_encode( $package ) . WC_Cache_Helper::get_transient_version( 'shipping' ) );
		$status_options = get_option( 'woocommerce_status_options', array() );
		$session_key    = 'shipping_for_package_' . $package_key;
		$stored_rates   = WC()->session->get( $session_key );

		if ( ! is_array( $stored_rates ) || $package_hash !== $stored_rates['package_hash'] || ! empty( $status_options['shipping_debug_mode'] ) ) {
			// Calculate shipping method rates
			$package['rates'] = array();

			foreach ( $this->load_shipping_methods( $package ) as $shipping_method ) {
				// Shipping instances need an ID
				if ( ! $shipping_method->supports( 'shipping-zones' ) || $shipping_method->get_instance_id() ) {
					$package['rates'] = $package['rates'] + $shipping_method->get_rates_for_package( $package ); // + instead of array_merge maintains numeric keys
				}
			}

			// Filter the calculated rates
			$package['rates'] = apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			// Store in session to avoid recalculation
			WC()->session->set( $session_key, array(
				'package_hash' => $package_hash,
				'rates'        => $package['rates']
			) );
		} else {
			$package['rates'] = $stored_rates['rates'];
		}

		return $package;
	}

	/**
	 * Get packages.
	 * @return array
	 */
	public function get_packages() {
		return $this->packages;
	}

	/**
	 * Reset shipping.
	 *
	 * Reset the totals for shipping as a whole.
	 */
	public function reset_shipping() {
		unset( WC()->session->chosen_shipping_methods );
		$this->shipping_total = null;
		$this->shipping_taxes = array();
		$this->packages = array();
	}

	/**
	 * @deprecated 2.6.0 Was previously used to determine sort order of methods, but this is now controlled by zones and thus unused.
	 */
	public function sort_shipping_methods() {
		_deprecated_function( 'sort_shipping_methods', '2.6', '' );
		return $this->shipping_methods;
	}
}
