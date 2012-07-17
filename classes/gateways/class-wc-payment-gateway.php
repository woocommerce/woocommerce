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
	var $supports		= array( 'products' ); // Array of supported features
	
	/**
	 * Get the return url (thank you page)
	 *
	 * @since 1.1.2
	 */
	function get_return_url( $order = '' ) {
		
		$thanks_page_id = woocommerce_get_page_id('thanks');
		if ( $thanks_page_id ) :
			$return_url = get_permalink($thanks_page_id);
		else :
			$return_url = home_url();
		endif;
		
		if ( $order ) :
			$return_url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order->id, $return_url ) );
		endif;
		
		if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) 
			$return_url = str_replace( 'http:', 'https:', $return_url );
		
		return apply_filters( 'woocommerce_get_return_url', $return_url );
	}
	
	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @since 1.0.0
	 */
	function is_available() {
		if ( $this->enabled == "yes" ) 
			return true;
	}
	
	/**
	 * has_fields function.
	 * 
	 * @access public
	 * @return void
	 */
	function has_fields() {
		return $this->has_fields ? true : false;
	}
	
	/**
	 * Return the gateways title
	 * 
	 * @access public
	 * @return void
	 */
	function get_title() {
		return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return the gateways description
	 * 
	 * @access public
	 * @return void
	 */
	function get_description() {
		return apply_filters( 'woocommerce_gateway_description', $this->description, $this->id );
	}
	
	/**
	 * get_icon function.
	 * 
	 * @access public
	 * @return void
	 */
	function get_icon() {
		global $woocommerce;
		
		$icon = $this->icon ? '<img src="' . $woocommerce->force_ssl( $this->icon ) . '" alt="' . $this->title . '" />' : '';
			
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
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
		_deprecated_function( __FUNCTION__, '1.6.0', 'get_icon()' );
		return $this->get_icon();
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
        if ( $description = $this->get_description() ) 
        	echo wpautop( wptexturize( $description ) );
    }
    
	/**
	 * Check if a gateway supports a given feature.
	 * 
	 * Gateways should override this to declare support (or lack of support) for a feature. 
	 * For backward compatibility, gateways support 'products' by default, but nothing else. 
	 * 
	 * @param $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise. 
	 * @since 1.5.7
	 */
	function supports( $feature ) {
		return apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

}

/** Depreciated */
class woocommerce_payment_gateway extends WC_Payment_Gateway {
	public function __construct() { 
		parent::__construct(); 
	} 
}