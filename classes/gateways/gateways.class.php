<?php
/**
 * WooCommerce Payment Gateways class
 * 
 * Loads payment gateways via hooks for use in the store.
 *
 * @class 		woocommerce_payment_gateways
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_payment_gateways {
	
	var $payment_gateways;
   
    function init() {
    	
    	$load_gateways = apply_filters('woocommerce_payment_gateways', array());
		
		foreach ($load_gateways as $gateway) :
		
			$this->payment_gateways[] = &new $gateway();
			
		endforeach;
    	
    }

    function payment_gateways() {
		
		$_available_gateways = array();
		
		if (sizeof($this->payment_gateways) > 0) :
			foreach ( $this->payment_gateways as $gateway ) :
				
				$_available_gateways[$gateway->id] = $gateway;
				
			endforeach;
		endif;

		return $_available_gateways;
	}
	
	function get_available_payment_gateways() {
		
		$_available_gateways = array();
	
		foreach ( $this->payment_gateways as $gateway ) :
			
			if ($gateway->is_available()) $_available_gateways[$gateway->id] = $gateway;
			
		endforeach;

		return $_available_gateways;
	}
	
}