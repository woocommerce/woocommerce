<?php
/**
 * WooCommerce Payment Gateways class
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @class 		WC_Payment_Gateways
 * @version		1.6.4
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Payment_Gateways {

	/** @var array Array of payment gateway classes. */
	var $payment_gateways;

    /**
     * Load gateways and hook in functions.
     *
     * @access public
     * @return void
     */
    function init() {

    	$load_gateways = apply_filters('woocommerce_payment_gateways', array());

		// Get order option
		$ordering 	= (array) get_option('woocommerce_gateway_order');
		$order_end 	= 999;

		// Load gateways in order
		foreach ($load_gateways as $gateway) :

			$load_gateway = new $gateway();

			if (isset($ordering[$load_gateway->id]) && is_numeric($ordering[$load_gateway->id])) :
				// Add in position
				$this->payment_gateways[$ordering[$load_gateway->id]] = $load_gateway;
			else :
				// Add to end of the array
				$this->payment_gateways[$order_end] = $load_gateway;
				$order_end++;
			endif;

		endforeach;

		ksort($this->payment_gateways);

		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    }


    /**
     * Get gateways.
     *
     * @access public
     * @return array
     */
    function payment_gateways() {

		$_available_gateways = array();

		if (sizeof($this->payment_gateways) > 0) :
			foreach ( $this->payment_gateways as $gateway ) :

				$_available_gateways[$gateway->id] = $gateway;

			endforeach;
		endif;

		return $_available_gateways;
	}


	/**
	 * Get available gateways.
	 *
	 * @access public
	 * @return array
	 */
	function get_available_payment_gateways() {

		$_available_gateways = array();

		foreach ( $this->payment_gateways as $gateway ) :

			if ($gateway->is_available()) $_available_gateways[$gateway->id] = $gateway;

		endforeach;

		return apply_filters( 'woocommerce_available_payment_gateways', $_available_gateways );
	}


	/**
	 * Save options in admin.
	 *
	 * @access public
	 * @return void
	 */
	function process_admin_options() {

		$default_gateway = (isset($_POST['default_gateway'])) ? esc_attr($_POST['default_gateway']) : '';
		$gateway_order = (isset($_POST['gateway_order'])) ? $_POST['gateway_order'] : '';

		$order = array();

		if (is_array($gateway_order) && sizeof($gateway_order)>0) :
			$loop = 0;
			foreach ($gateway_order as $gateway_id) :
				$order[$gateway_id] = $loop;
				$loop++;
			endforeach;
		endif;

		update_option( 'woocommerce_default_gateway', $default_gateway );
		update_option( 'woocommerce_gateway_order', $order );
	}
}