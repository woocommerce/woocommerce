<?php
/**
 * WooCommerce Integrations class
 * 
 * Loads Integrations into WooCommerce.
 *
 * @class 		WC_Integrations
 * @package		WooCommerce
 * @category	Integrations
 * @author		WooThemes
 */
class WC_Integrations {
	
	var $integrations = array();
	
    /**
     * init function.
	 *
     * @access public
     */
    function init() {
		
		do_action('woocommerce_integrations_init');
		
		$load_integrations = apply_filters('woocommerce_integrations', array() );
		
		// Load integration classes
		foreach ( $load_integrations as $integration ) {
			
			$load_integration = new $integration();
			
			$this->integrations[$load_integration->id] = $load_integration;
			
		}
		
	}
	
	function get_integrations() {
		return $this->integrations;
	}
    
}