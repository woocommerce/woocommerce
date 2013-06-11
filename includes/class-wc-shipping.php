<?php
/**
 * WooCommerce Shipping Class
 *
 * Handles shipping and loads shipping methods via hooks.
 *
 * @class 		WC_Shipping
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Shipping {

	/** @var bool True if shipping is enabled. */
	var $enabled					= false;

	/** @var array Stores methods loaded into woocommerce. */
	var $shipping_methods 			= array();

	/** @var float Stores the cost of shipping */
	var $shipping_total 			= 0;

	/**  @var array Stores an array of shipping taxes. */
	var $shipping_taxes				= array();

	/**  @var string Stores the label for the chosen method. */
	var $shipping_label				= null;

	/** @var array Stores the shipping classes. */
	var $shipping_classes			= array();

	/** @var array Stores packages to ship and to get quotes for. */
	var $packages					= array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     */
    function init() {
		do_action( 'woocommerce_shipping_init' );

		$this->enabled = ( get_option('woocommerce_calc_shipping') == 'no' ) ? false : true;
	}

	/**
	 * load_shipping_methods function.
	 *
	 * Loads all shipping methods which are hooked in. If a $package is passed some methods may add themselves conditionally.
	 *
	 * Methods are sorted into their user-defined order after being loaded.
	 *
	 * @access public
	 * @return array
	 */
	function load_shipping_methods( $package = false ) {

		$this->unregister_shipping_methods();

		// Methods can register themselves through this hook
		do_action( 'woocommerce_load_shipping_methods', $package );

		// Register methods through a filter
		$shipping_methods_to_load = apply_filters( 'woocommerce_shipping_methods', array() );

		foreach ( $shipping_methods_to_load as $method )
			$this->register_shipping_method( $method );

		$this->sort_shipping_methods();

		return $this->shipping_methods;
	}

	/**
	 * Register a shipping method for use in calculations.
	 *
	 * @access public
	 * @return void
	 */
	function register_shipping_method( $method ) {

		if ( ! is_object( $method ) )
			$method = new $method();

		$id = empty( $method->instance_id ) ? $method->id : $method->instance_id;

		$this->shipping_methods[ $id ] = $method;
	}

	/**
	 * unregister_shipping_methods function.
	 *
	 * @access public
	 * @return void
	 */
	function unregister_shipping_methods() {
		unset( $this->shipping_methods );
	}

	/**
	 * sort_shipping_methods function.
	 *
	 * Sorts shipping methods into the user defined order.
	 *
	 * @access public
	 * @return array
	 */
	function sort_shipping_methods() {

		$sorted_shipping_methods = array();

		// Get order option
		$ordering 	= (array) get_option('woocommerce_shipping_method_order');
		$order_end 	= 999;

		// Load shipping methods in order
		foreach ( $this->shipping_methods as $method ) {

			if ( isset( $ordering[ $method->id ] ) && is_numeric( $ordering[ $method->id ] ) ) {
				// Add in position
				$sorted_shipping_methods[ $ordering[ $method->id ] ][] = $method;
			} else {
				// Add to end of the array
				$sorted_shipping_methods[ $order_end ][] = $method;
			}
		}

		ksort( $sorted_shipping_methods );

		$this->shipping_methods = array();

		foreach ( $sorted_shipping_methods as $methods )
			foreach ( $methods as $method ) {
				$id = empty( $method->instance_id ) ? $method->id : $method->instance_id;
				$this->shipping_methods[ $id ] = $method;
			}

		return $this->shipping_methods;
	}

	/**
	 * get_shipping_methods function.
	 *
	 * Returns all registered shipping methods for usage.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	function get_shipping_methods() {
		return $this->shipping_methods;
	}

	/**
	 * get_shipping_classes function.
	 *
	 * Load shipping classes taxonomy terms.
	 *
	 * @access public
	 * @return array
	 */
	function get_shipping_classes() {
		if ( empty( $this->shipping_classes ) )
			$this->shipping_classes = ( $classes = get_terms( 'product_shipping_class', array( 'hide_empty' => '0' ) ) ) ? $classes : array();

		return $this->shipping_classes;
	}

	/**
	 * calculate_shipping function.
	 *
	 * Calculate shipping for (multiple) packages of cart items.
	 *
	 * @access public
	 * @param array $packages multi-dimensional array of cart items to calc shipping for
	 */
	function calculate_shipping( $packages = array() ) {
		global $woocommerce;

		if ( ! $this->enabled || empty( $packages ) )
			return;

		$this->shipping_total 	= 0;
		$this->shipping_taxes 	= array();
		$this->shipping_label 	= null;
		$this->packages 		= array();
		$_cheapest_cost = $_cheapest_method = $chosen_method = '';

		// Calculate costs for passed packages
		$package_keys 		= array_keys( $packages );
		$package_keys_size 	= sizeof( $package_keys );

		for ( $i = 0; $i < $package_keys_size; $i ++ )
			$this->packages[ $package_keys[ $i ] ] = $this->calculate_shipping_for_package( $packages[ $package_keys[ $i ] ] );

		// Get available methods (in this case methods for all packages)
		$_available_methods = $this->get_available_shipping_methods();

		// Get chosen method
		if ( ! empty( $woocommerce->session->chosen_shipping_method ) )
			$chosen_method = $woocommerce->session->chosen_shipping_method;

		// Set method if we have mehtods available
		if ( sizeof( $_available_methods ) > 0 ) {

			// If not set, not available, or available methods have changed, set to the default option
			if ( empty( $chosen_method ) || ! isset( $_available_methods[ $chosen_method ] ) || $woocommerce->session->available_methods_count != sizeof( $_available_methods ) ) {

				$chosen_method = apply_filters( 'woocommerce_shipping_chosen_method', get_option( 'woocommerce_default_shipping_method' ), $_available_methods );

				// Loops methods and find a match
				if ( ! empty( $chosen_method ) && ! isset( $_available_methods[ $chosen_method ] ) ) {
					foreach ( $_available_methods as $method_id => $method ) {
						if ( strpos( $method->id, $chosen_method ) === 0 ) {
							$chosen_method = $method->id;
							break;
						}
					}
				}

				if ( empty( $chosen_method ) || ! isset( $_available_methods[$chosen_method] ) ) {

					// Default to cheapest
					foreach ( $_available_methods as $method_id => $method ) {
						if ( $method->cost < $_cheapest_cost || ! is_numeric( $_cheapest_cost ) ) {
							$_cheapest_cost 	= $method->cost;
							$_cheapest_method 	= $method_id;
						}
					}
					$chosen_method = $_cheapest_method;
				}

				// Store chosen method
				$woocommerce->session->chosen_shipping_method = $chosen_method;

				// Do action for this chosen method
				do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
			}

			if ( $chosen_method ) {
				$this->shipping_total 	= $_available_methods[ $chosen_method ]->cost;
				$this->shipping_taxes 	= $_available_methods[ $chosen_method ]->taxes;
				$this->shipping_label 	= $_available_methods[ $chosen_method ]->label;
			}
		}

		$woocommerce->session->available_methods_count = sizeof( $_available_methods );
	}

	/**
	 * calculate_shipping_for_package function.
	 *
	 * Calculates each shipping methods cost. Rates are cached based on the package to speed up calculations.
	 *
	 * @access public
	 * @param array $package cart items
	 */
	function calculate_shipping_for_package( $package = array() ) {
		if ( ! $this->enabled ) return false;
		if ( ! $package ) return false;

		// Check if we need to recalculate shipping for this package
		$package_hash = 'wc_ship_' . md5( json_encode( $package ) );

		if ( false === ( $stored_rates = get_transient( $package_hash ) ) ) {

			// Calculate shipping method rates
			$package['rates'] = array();

			foreach ( $this->load_shipping_methods( $package ) as $shipping_method ) {

				if ( $shipping_method->is_available( $package ) ) {

					// Reset Rates
					$shipping_method->rates = array();

					// Calculate Shipping for package
					$shipping_method->calculate_shipping( $package );

					// Place rates in package array
					if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) )
						foreach ( $shipping_method->rates as $rate )
							$package['rates'][$rate->id] = $rate;
				}

			}

			// Filter the calculated rates
			$package['rates'] = apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			// Store
			set_transient( $package_hash, $package['rates'], 60 * 60 ); // Cached for an hour

		} else {

			$package['rates'] = $stored_rates;

		}

		return $package;
	}

	/**
	 * get_available_shipping_methods function.
	 *
	 * Gets all available shipping methods which have rates.
	 *
	 * @todo Currently we support 1 shipping method per order so this function merges rates - in the future we should offer
	 * 1 rate per package and list them accordingly for user selection
	 *
	 * @access public
	 * @return array
	 */
	function get_available_shipping_methods() {
		if ( ! $this->enabled ) return;
		if ( empty( $this->packages ) ) return;

		// Loop packages and merge rates to get a total for each shipping method
		$available_methods = array();

		foreach ( $this->packages as $package ) {
			if ( ! $package['rates'] ) continue;

			foreach ( $package['rates'] as $id => $rate ) {

				if ( isset( $available_methods[$id] ) ) {
					// Merge cost and taxes - label and ID will be the same
					$available_methods[$id]->cost += $rate->cost;

					foreach ( array_keys( $available_methods[$id]->taxes + $rate->taxes ) as $key ) {
					    $available_methods[$id]->taxes[$key] = ( isset( $rate->taxes[$key] ) ? $rate->taxes[$key] : 0 ) + ( isset( $available_methods[$id]->taxes[$key] ) ? $available_methods[$id]->taxes[$key] : 0 );
					}
				} else {
					$available_methods[$id] = $rate;
				}

			}

		}

		return apply_filters( 'woocommerce_available_shipping_methods', $available_methods );
	}


	/**
	 * reset_shipping function.
	 *
	 * Reset the totals for shipping as a whole.
	 *
	 * @access public
	 * @return void
	 */
	function reset_shipping() {
		global $woocommerce;
		unset( $woocommerce->session->chosen_shipping_method );
		$this->shipping_total = 0;
		$this->shipping_taxes = array();
		$this->shipping_label = null;
		$this->packages = array();
	}


	/**
	 * process_admin_options function.
	 *
	 * Saves options on the shipping setting page.
	 *
	 * @access public
	 * @return void
	 */
	function process_admin_options() {

		$default_shipping_method = ( isset( $_POST['default_shipping_method'] ) ) ? esc_attr( $_POST['default_shipping_method'] ) : '';
		$method_order = ( isset( $_POST['method_order'] ) ) ? $_POST['method_order'] : '';

		$order = array();

		if ( is_array( $method_order ) && sizeof( $method_order ) > 0 ) {
			$loop = 0;
			foreach ($method_order as $method_id) {
				$order[$method_id] = $loop;
				$loop++;
			}
		}

		update_option( 'woocommerce_default_shipping_method', $default_shipping_method );
		update_option( 'woocommerce_shipping_method_order', $order );
	}

}

/**
 * Register a shipping method
 *
 * Registers a shipping method ready to be loaded. Accepts a class name (string) or a class object.
 *
 * @package		WooCommerce/Classes/Shipping
 * @since 1.5.7
 */
function woocommerce_register_shipping_method( $shipping_method ) {
	$GLOBALS['woocommerce']->shipping->register_shipping_method( $shipping_method );
}