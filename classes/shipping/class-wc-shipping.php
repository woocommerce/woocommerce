<?php    
/**
 * WooCommerce Shipping Class
 * 
 * Handles shipping and loads shipping methods via hooks.
 *
 * @class 		WC_Shipping
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class WC_Shipping {
	
	var $enabled			= false;
	var $shipping_methods 	= array();
	var $chosen_method		= null;
	var $shipping_total 	= 0;
	var $shipping_taxes		= array();
	var $shipping_label		= null;
	var $shipping_classes;
	var $packages			= array();

    /**
     * init function.
     * 
     * Init the shipping - load methods and reorder based on settings.
     *
     * @access public
     */
    function init() {
		
		$this->enabled = ( get_option('woocommerce_calc_shipping') == 'no' ) ? false : true;
		
		do_action('woocommerce_shipping_init');
		
		$load_methods = apply_filters('woocommerce_shipping_methods', array() );
		
		// Get order option
		$ordering 	= (array) get_option('woocommerce_shipping_method_order');
		$order_end 	= 999;
		
		// Load gateways in order
		foreach ( $load_methods as $method ) {
			
			$load_method = new $method();
			
			if ( isset( $ordering[$load_method->id] ) && is_numeric( $ordering[$load_method->id] ) ) {
				// Add in position
				$this->shipping_methods[$ordering[$load_method->id]] = $load_method;
			} else {
				// Add to end of the array
				$this->shipping_methods[$order_end] = $load_method;
				$order_end++;
			}
		}
		
		ksort($this->shipping_methods);
		
		add_action( 'woocommerce_update_options_shipping', array( &$this, 'process_admin_options' ) );
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
		if ( ! is_array( $this->shipping_classes ) ) :
			
			$classes = get_terms( 'product_shipping_class', array(
				'hide_empty' => '0'
			) );
			
			$this->shipping_classes = ($classes) ? $classes : array();
				
		endif;
		
		return (array) $this->shipping_classes;
	}	
	
	/**
	 * calculate_shipping function.
	 *
	 * Calculate shipping for (multiple) packages of cart items. 
	 * 
	 * @access public
	 * @param array $packages multi-dimentional array of cart items to calc shipping for
	 */
	function calculate_shipping( $packages = array() ) {
		if ( ! $this->enabled ) return;
		if ( empty( $packages ) ) return;
		
		$this->shipping_total 	= 0;
		$this->shipping_taxes 	= array();
		$this->shipping_label 	= null;
		$this->packages 		= array();
		$_cheapest_cost = $_cheapest_method = $chosen_method = '';
						
		// Calculate costs for passed packages
		$package_keys 		= array_keys( $packages );
		$package_keys_size 	= sizeof( $package_keys );
		for ( $i=0; $i < $package_keys_size; $i++ ) 
			$this->packages[$package_keys[$i]] = $this->calculate_shipping_for_package( $packages[$package_keys[$i]] );
		
		// Get available methods (in this case methods for all packages)
		$_available_methods = $this->get_available_shipping_methods();
		
		// Get chosen method
		if ( ! empty( $_SESSION['_chosen_shipping_method'] ) && isset( $_SESSION['_available_methods_count'] ) && $_SESSION['_available_methods_count'] == sizeof( $_available_methods ) ) 
			$chosen_method = $_SESSION['_chosen_shipping_method'];
		
		$_SESSION['_available_methods_count'] = sizeof( $_available_methods );

		if ( sizeof( $_available_methods ) > 0 ) {
		
			// If not set, set a default
			if ( empty( $chosen_method ) || ! isset( $_available_methods[$chosen_method] ) ) {
				
				$chosen_method = get_option('woocommerce_default_shipping_method');
				
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
			
			}

			if ( $chosen_method ) {
				$_SESSION['_chosen_shipping_method'] = $chosen_method;
				$this->shipping_total 	= $_available_methods[$chosen_method]->cost;
				$this->shipping_taxes 	= $_available_methods[$chosen_method]->taxes;
				$this->shipping_label 	= $_available_methods[$chosen_method]->label;
			}
		}
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
			
			foreach ( $this->shipping_methods as $shipping_method ) {
					
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
	 * 1 rate per package and list them accordinly for user selection
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
					$available_methods[$id] = new WC_Shipping_Rate( $rate->id, $rate->label, $rate->cost, $rate->taxes );
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
		unset($_SESSION['_chosen_shipping_method']);
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
		
		if ( is_array( $method_order ) && sizeof( $method_order ) > 0 ) :
			$loop = 0;
			foreach ($method_order as $method_id) {
				$order[$method_id] = $loop;
				$loop++;
			}
		endif;
		
		update_option( 'woocommerce_default_shipping_method', $default_shipping_method );
		update_option( 'woocommerce_shipping_method_order', $order );
	}
	
}