<?php
/**
 * WooCommerce Shipping Method Class
 * 
 * Extended by shipping methods to handle shipping calculations etc.
 *
 * @class 		woocommerce_shipping_method
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class woocommerce_shipping_method extends woocommerce_settings_api {
	
	var $id;
	var $method_title; 	// Method title
	var $title;			// User set title
	var $availability;
	var $countries;
	var $type;
	var $fee				= 0;
	var $min_amount			= null;
	var $enabled			= false;
	
	/**
	 * Rates
	 *
	 * This is an array of rates - methods must populate this array to register shipping costs
	 */
	var $rates 				= array(); // This is an array of rates - methods must populate this array to register shipping costs
	
	function add_rate( $args = array() ) {
		$defaults = array(
			'id' 		=> '',
			'label' 	=> '',
			'cost' 		=> '0',
			'taxes' 	=> array()
		);

		$args = wp_parse_args( $args, $defaults );
					
		extract( $args );
		
		// Id and label are required
		if (!$id || !$label) return;
		
		$rate = new stdClass;
		$rate->id = $id;
		$rate->label = $label;
		$rate->cost = $cost;
		$rate->taxes = $taxes;
		
		$this->rates[] = $rate;
	}
	
    function is_available() {
    	global $woocommerce;
    	
    	if ($this->enabled=="no") 
    		return false;
    	
		if (isset($woocommerce->cart->cart_contents_total) && isset($this->min_amount) && $this->min_amount && $this->min_amount > $woocommerce->cart->cart_contents_total) 
			return false;
		
		$ship_to_countries = '';
		
		if ($this->availability == 'specific') :
			$ship_to_countries = $this->countries;
		else :
			if (get_option('woocommerce_allowed_countries')=='specific') :
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
			endif;
		endif; 
		
		if (is_array($ship_to_countries)) :
			if (!in_array($woocommerce->customer->get_shipping_country(), $ship_to_countries)) return false;
		endif;
		
		return true;
    } 
    
    function get_fee( $fee, $total ) {
		if (strstr($fee, '%')) :
			return ($total/100) * str_replace('%', '', $fee);
		else :
			return $fee;
		endif;
	}
    	
}