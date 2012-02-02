<?php
/**
 * WooCommerce Payment Gateway class
 * 
 * Extended by individual payment gateways to handle payments.
 *
 * @class 		WC_Payment_Gateway
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class WC_Payment_Gateway extends WC_Settings_API {
	
	var $id;
	var $title;
	var $chosen;
	var $has_fields;
	var $countries;
	var $availability;
	var $enabled;
	var $icon;
	var $description;
	
	/**
	 * Get the return url (thank you page)
	 *
	 * @since 1.1.2
	 */
	function get_return_url( $order = '' ) {
		
		$thanks_page_id = woocommerce_get_page_id('thanks');
		if ($thanks_page_id) :
			$return_url = get_permalink($thanks_page_id);
		else :
			$return_url = home_url();
		endif;
		
		if ( $order ) :
			$return_url = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, $return_url));
		endif;
		
		if (is_ssl() || get_option('woocommerce_force_ssl_checkout')=='yes') $return_url = str_replace('http:', 'https:', $return_url);
		
		return apply_filters('woocommerce_get_return_url', $return_url);
	}
	
	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @since 1.0.0
	 */
	function is_available() {
		
		if ($this->enabled=="yes") :
			
			return true;
			
		endif;	
		
		return false;
	}
	
	/**
	 * Set As Current Gateway.
	 *
	 * Set this as the current gateway.
	 *
	 * @since 1.0.0
	 */
	function set_current() {
		$this->chosen = true;
	}
	
	/**
	 * The Gateway Icon
	 *
	 * Display the gateway's icon.
	 *
	 * @since 1.0.0
	 */
	function icon() {
		global $woocommerce;
		if ($this->icon) :
			return '<img src="'. $woocommerce->force_ssl($this->icon).'" alt="'.$this->title.'" />';
		endif;
	}
	
	/**
	 * Process Payment
	 *
	 * Process the payment. Override this in your gateway.
	 *
	 * @since 1.0.0
	 */
	function process_payment() {}
	
	/**
	 * Validate Frontend Fields
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @since 1.0.0
	 */
	function validate_fields() { return true; }
	
    /**
    * If There are no payment fields show the description if set.
    * Override this in your gateway if you have some.
    */
    function payment_fields() {
        if ($this->description) echo wpautop(wptexturize($this->description));
    }
    
}

/** Depreciated */
class woocommerce_payment_gateway extends WC_Payment_Gateway {
	public function __construct() { 
		// _deprecated_function( 'woocommerce_payment_gateway', '1.4', 'WC_Payment_Gateway()' ); Depreciated, but leaving uncommented until all gateways are updated
		parent::__construct(); 
	} 
}