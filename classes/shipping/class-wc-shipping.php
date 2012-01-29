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
	
    function init() {

		if (get_option('woocommerce_calc_shipping')!='no') $this->enabled = true; 
		
		do_action('woocommerce_shipping_init');
		
		$load_methods = apply_filters('woocommerce_shipping_methods', array());
		
		// Get order option
		$ordering 	= (array) get_option('woocommerce_shipping_method_order');
		$order_end 	= 999;
		
		// Load gateways in order
		foreach ($load_methods as $method) :
			
			$load_method = new $method();
			
			if (isset($ordering[$load_method->id]) && is_numeric($ordering[$load_method->id])) :
				// Add in position
				$this->shipping_methods[$ordering[$load_method->id]] = $load_method;
			else :
				// Add to end of the array
				$this->shipping_methods[$order_end] = $load_method;
				$order_end++;
			endif;
			
		endforeach;
		
		ksort($this->shipping_methods);
		
		add_action('woocommerce_update_options_shipping', array(&$this, 'process_admin_options'));
	}
	
	function get_shipping_classes() {
		if (!is_array($this->shipping_classes)) :
			
			$args = array(
				'hide_empty' => '0'
			);
			$classes = get_terms( 'product_shipping_class', $args );
			
			$this->shipping_classes = ($classes) ? $classes : array();
				
		endif;
		
		return (array) $this->shipping_classes;
	}

	function get_available_shipping_methods() {
		if ($this->enabled=='yes') :
		
			$_available_methods = array();
			
			foreach ( $this->shipping_methods as $shipping_method ) :
				
				if ($shipping_method->is_available()) :
					
					$shipping_method->calculate_shipping();
					
					// If available, put available methods/rates in the array
					if (is_array($shipping_method->rates) && sizeof($shipping_method->rates) > 0) :
						foreach ($shipping_method->rates as $rate) $_available_methods[$rate->id] = $rate;
					endif;
					
				endif;
				
			endforeach;
			
			return $_available_methods;
			
		endif;
	}
	
	function reset_shipping_methods() {
		foreach ( $this->shipping_methods as $shipping_method ) :
			$shipping_method->rates = array();
		endforeach;
	}
	
	function calculate_shipping() {
		if ($this->enabled=='yes') :
		
			$this->shipping_total = 0;
			$this->shipping_taxes = array();
			$this->shipping_label = null;
			$_cheapest_cost = '';
			$_cheapest_method = '';
			if (isset($_SESSION['_chosen_shipping_method'])) $chosen_method = $_SESSION['_chosen_shipping_method']; else $chosen_method = '';
			
			$this->reset_shipping_methods();
			
			$_available_methods = $this->get_available_shipping_methods();
			
			if (sizeof($_available_methods)>0) :
			
				// If not set, set a default
				if (!$chosen_method || empty($chosen_method) || !isset($_available_methods[$chosen_method])) :
					
					$chosen_method = get_option('woocommerce_default_shipping_method');
					
					if (!$chosen_method || empty($chosen_method) || !isset($_available_methods[$chosen_method])) :
						// Default to cheapest
						foreach ($_available_methods as $method_id => $method) :
							if ($method->cost < $_cheapest_cost || !is_numeric($_cheapest_cost)) :
								$_cheapest_cost 	= $method->cost;
								$_cheapest_method 	= $method_id;
							endif;
						endforeach;
						
						$chosen_method = $_cheapest_method;
					endif;
				
				endif;

				if ($chosen_method) :
					$_SESSION['_chosen_shipping_method'] = $chosen_method;
					$this->shipping_total 	= $_available_methods[$chosen_method]->cost;
					$this->shipping_taxes 	= $_available_methods[$chosen_method]->taxes;
					$this->shipping_label 	= $_available_methods[$chosen_method]->label;
				endif;
			endif;

		endif;
	}
	
	function reset_shipping() {
		unset($_SESSION['_chosen_shipping_method']);
		$this->shipping_total = 0;
		$this->shipping_taxes = array();
		$this->shipping_label = null;
	}
	
	function process_admin_options() {
	
		$default_shipping_method = (isset($_POST['default_shipping_method'])) ? esc_attr($_POST['default_shipping_method']) : '';
		$method_order = (isset($_POST['method_order'])) ? $_POST['method_order'] : '';
		
		$order = array();
		
		if (is_array($method_order) && sizeof($method_order)>0) :
			$loop = 0;
			foreach ($method_order as $method_id) :
				$order[$method_id] = $loop;
				$loop++;
			endforeach;
		endif;
		
		update_option( 'woocommerce_default_shipping_method', $default_shipping_method );
		update_option( 'woocommerce_shipping_method_order', $order );
	}
}