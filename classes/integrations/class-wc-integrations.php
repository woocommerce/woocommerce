<?php
/**
 * WooCommerce Integrations class
 *
 * Loads Integrations into WooCommerce.
 *
 * @class 		WC_Integrations
 * @version		1.6.4
 * @package		WooCommerce/Classes/Integrations
 * @author 		WooThemes
 */
class WC_Integrations {

	/** @var array Array of integration classes */
	var $integrations = array();

    /**
     * Load integration classes.
     *
     * @access public
     * @return void
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

	/**
	 * Return loaded integrations.
	 *
	 * @access public
	 * @return array
	 */
	function get_integrations() {
		return $this->integrations;
	}

}