<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * International Shipping Method based on Flat Rate shipping
 *
 * A simple shipping method for a flat fee per item or per order.
 *
 * @class 		WC_Shipping_International_Delivery
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_International_Delivery extends WC_Shipping_Flat_Rate {

	var $id = 'international_delivery';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

        $this->id 						= 'international_delivery';
		$this->flat_rate_option	 		= 'woocommerce_international_delivery_flat_rates';
		$this->method_title      		= __( 'International Delivery', 'woocommerce' );
		$this->method_description   	= __( 'International delivery based on flat rate shipping.', 'woocommerce' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_flat_rates' ) );

    	$this->init();
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	global $woocommerce;

    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
							'default' 		=> 'no'
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'International Delivery', 'woocommerce' ),
							'desc_tip'      => true,
						),
			'availability' => array(
							'title' 		=> __( 'Availability', 'woocommerce' ),
							'type' 			=> 'select',
							'description' 	=> '',
							'default' 		=> 'including',
							'options' 		=> array(
								'including' 	=> __( 'Selected countries', 'woocommerce' ),
								'excluding' 	=> __( 'Excluding selected countries', 'woocommerce' ),
							)
						),
			'countries' => array(
							'title' 		=> __( 'Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						),
			'tax_status' => array(
							'title' 		=> __( 'Tax Status', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'taxable',
							'options'		=> array(
								'taxable' 	=> __( 'Taxable', 'woocommerce' ),
								'none' 		=> __( 'None', 'woocommerce' )
							)
						),
			'type' => array(
							'title' 		=> __( 'Cost Added...', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'order',
							'options' 		=> array(
								'order' 	=> __( 'Per Order - charge shipping for the entire order as a whole', 'woocommerce' ),
								'item' 		=> __( 'Per Item - charge shipping for each item individually', 'woocommerce' ),
								'class' 	=> __( 'Per Class - charge shipping for each shipping class in an order', 'woocommerce' ),
							),
						),
			'cost' => array(
							'title' 		=> __( 'Cost', 'woocommerce' ),
							'type' 			=> 'number',
							'custom_attributes' => array(
								'step'	=> 'any',
								'min'	=> '0'
							),
							'description'	=> __( 'Cost excluding tax. Enter an amount, e.g. 2.50.', 'woocommerce' ),
							'default' 		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'fee' => array(
							'title' 		=> __( 'Handling Fee', 'woocommerce' ),
							'type' 			=> 'text',
							'description'	=> __( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			'minimum_fee' => array(
							'title' 		=> __( 'Minimum Handling Fee', 'woocommerce' ),
							'type' 			=> 'number',
							'custom_attributes' => array(
								'step'	=> 'any',
								'min'	=> '0'
							),
							'description'	=> __( 'Enter a minimum fee amount. Fee\'s less than this will be increased. Leave blank to disable.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> '0.00'
						),
			);

    }


    /**
     * is_available function.
     *
     * @access public
     * @param mixed $package
     * @return bool
     */
    function is_available( $package ) {
    	global $woocommerce;

    	if ($this->enabled=="no") return false;

		if ($this->availability=='including') :

			if (is_array($this->countries)) :
				if ( ! in_array( $package['destination']['country'], $this->countries) ) return false;
			endif;

		else :

			if (is_array($this->countries)) :
				if ( in_array( $package['destination']['country'], $this->countries) ) return false;
			endif;

		endif;

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true );
    }

}