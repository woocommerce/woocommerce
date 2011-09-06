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
class woocommerce_shipping_method {
	
	var $id;
	var $method_title;
	var $title;
	var $availability;
	var $countries;
	var $type;
	var $fee				= 0;
	var $min_amount			= null;
	var $enabled			= false;
	var $shipping_total 	= 0;
	var $shipping_tax 		= 0;
	var $cost				= 0; // Stores cost if theres only one
	var $multiple_rates		= false;
	var $rates 				= array(); // When a method has more than one cost/choice it will be in this array of titles/costs
	
    function is_available() {
    	global $woocommerce;
    	
    	if ($this->enabled=="no") return false;
    	
		if (isset($woocommerce->cart->cart_contents_total) && isset($this->min_amount) && $this->min_amount && $this->min_amount > $woocommerce->cart->cart_contents_total) return false;
		
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
	    
    function admin_options() {}
    
    function process_admin_options() {}
    	
}