<?php
/**
 * WooCommerce Payment Gateway class
 * 
 * Extended by individual payment gateways to handle payments.
 *
 * @class 		woocommerce_payment_gateway
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_payment_gateway {
	
	var $id;
	var $title;
	var $chosen;
	var $has_fields;
	var $countries;
	var $availability;
	var $enabled;
	var $icon;
	var $description;
	
	function is_available() {
		
		if ($this->enabled=="yes") :
			
			return true;
			
		endif;	
		
		return false;
	}
	
	function set_current() {
		$this->chosen = true;
	}
	
	function icon() {
		if ($this->icon) :
			return '<img src="'. woocommerce::force_ssl($this->icon).'" alt="'.$this->title.'" />';
		endif;
	}
	
	function admin_options() {}
	
	function process_payment() {}
	
	function validate_fields() { return true; }
}