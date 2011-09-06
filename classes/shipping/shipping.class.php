<?php    
/**
 * WooCommerce Shipping Class
 * 
 * Handles shipping and loads shipping methods via hooks.
 *
 * @class 		woocommerce_shipping
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class woocommerce_shipping {
	
	var $enabled			= false;
	var $shipping_methods 	= array();
	var $chosen_method		= null;
	var $shipping_total 	= 0;
	var $shipping_tax 		= 0;
	var $shipping_label		= null;
	
    function init() {
		
		if (get_option('woocommerce_calc_shipping')!='no') $this->enabled = true; 
		
		do_action('woocommerce_shipping_init');
		
		$load_methods = apply_filters('woocommerce_shipping_methods', array());
		
		foreach ($load_methods as $method) :
		
			$this->shipping_methods[] = &new $method();
			
		endforeach;
		
	}

	function get_available_shipping_methods() {

		if ($this->enabled=='yes') :
		
			$_available_methods = array();
			
			foreach ( $this->shipping_methods as $shipping_method ) :
				
				if ($shipping_method->is_available()) :
					
					$shipping_method->calculate_shipping();
					
					// If available, put available methods/rates in the array
					if ($shipping_method->multiple_rates) :
							
							foreach ($shipping_method->rates as $rate) :
								
								$method = $rate;
								
								$_available_methods[$method->id] = $method;
								
							endforeach;
							
					else :
						
						$method = $shipping_method;
						
						$_available_methods[$method->id] = $method;

					endif;
					
				endif;
				
			endforeach;
			
			return $_available_methods;
			
		endif;
	}
	
	function reset_shipping_methods() {
		foreach ( $this->shipping_methods as $shipping_method ) :
			$shipping_method->shipping_total = 0;
			$shipping_method->shipping_tax = 0;
			$shipping_method->rates = array();
		endforeach;
	}
	
	function calculate_shipping() {
		
		if ($this->enabled=='yes') :
		
			$this->shipping_total = 0;
			$this->shipping_tax = 0;
			$this->shipping_label = null;
			$_cheapest_fee = '';
			$_cheapest_method = '';
			if (isset($_SESSION['_chosen_shipping_method'])) $chosen_method = $_SESSION['_chosen_shipping_method']; else $chosen_method = '';
			$calc_cheapest = false;
			
			if (!$chosen_method || empty($chosen_method)) $calc_cheapest = true;
			
			$this->reset_shipping_methods();
			
			$_available_methods = $this->get_available_shipping_methods();
			
			if (sizeof($_available_methods)>0) :
			
				foreach ($_available_methods as $method_id => $method) :
					
					$fee = $method->shipping_total;
					if ($fee < $_cheapest_fee || !is_numeric($_cheapest_fee)) :
						$_cheapest_fee 		= $fee;
						$_cheapest_method 	= $method_id;
					endif;

				endforeach;
				
				// Default to cheapest
				if ($calc_cheapest || !isset($_available_methods[$chosen_method])) :
					$chosen_method = $_cheapest_method;
				endif;
				
				if ($chosen_method) :
					
					$_SESSION['_chosen_shipping_method'] = $chosen_method;
					$this->shipping_total 	= $_available_methods[$chosen_method]->shipping_total;
					$this->shipping_tax 	= $_available_methods[$chosen_method]->shipping_tax;
					$this->shipping_label 	= $_available_methods[$chosen_method]->title;
					
				endif;
			endif;

		endif;
		
	}
	
	function reset_shipping() {
		unset($_SESSION['_chosen_shipping_method']);
		$this->shipping_total = 0;
		$this->shipping_tax = 0;
		$this->shipping_label = null;
	}
	
}