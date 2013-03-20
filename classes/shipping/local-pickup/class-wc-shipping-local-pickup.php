<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Local Pickup Shipping Method
 *
 * A simple shipping method allowing free pickup as a shipping method
 *
 * @class 		WC_Shipping_Local_Pickup
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Local_Pickup extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id 			= 'local_pickup';
		$this->method_title = __( 'Local Pickup', 'woocommerce' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled		= $this->get_option( 'enabled' );
		$this->title		= $this->get_option( 'title' );
		$this->codes		= $this->get_option( 'codes' );
		$this->availability	= $this->get_option( 'availability' );
		$this->countries	= $this->get_option( 'countries' );

		// Actions
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'taxable_address' ) );
		add_action( 'woocommerce_shipping_method_chosen', array( $this, 'method_chosen' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @return void
	 */
	function calculate_shipping() {
		$rate = array(
			'id' 		=> $this->id,
			'label' 	=> $this->title,
		);
		$this->add_rate($rate);
	}

	/**
	 * init_form_fields function.
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
    	global $woocommerce;
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable local pickup', 'woocommerce' ),
				'default' 		=> 'no'
			),
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'		=> __( 'Local Pickup', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'codes' => array(
				'title' 		=> __( 'Zip/Post Codes', 'woocommerce' ),
				'type' 			=> 'textarea',
				'description' 	=> __( 'What zip/post codes would you like to offer delivery to? Separate codes with a comma. Accepts wildcards, e.g. P* will match a postcode of PE30.', 'woocommerce' ),
				'default'		=> '',
				'desc_tip'      => true,
				'placeholder'	=> '12345, 56789 etc'
			),
			'availability' => array(
				'title' 		=> __( 'Method availability', 'woocommerce' ),
				'type' 			=> 'select',
				'default' 		=> 'all',
				'class'			=> 'availability',
				'options'		=> array(
					'all' 		=> __( 'All allowed countries', 'woocommerce' ),
					'specific' 	=> __( 'Specific Countries', 'woocommerce' )
				)
			),
			'countries' => array(
				'title' 		=> __( 'Specific Countries', 'woocommerce' ),
				'type' 			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default' 		=> '',
				'options'		=> $woocommerce->countries->countries
			),
			'apply_base_tax' => array(
				'title' 		=> __( 'Apply base tax rate', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'When this shipping method is chosen, apply the base tax rate rather than for the customer\'s given address.', 'woocommerce' ),
				'default' 		=> 'no'
			),
		);
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		global $woocommerce; ?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e( 'Local pickup is a simple method which allows the customer to pick up their order themselves.', 'woocommerce' ); ?></p>
		<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
    	</table> <?php
	}

	/**
	 * is_available function.
	 *
	 * @access public
	 * @param array $package
	 * @return bool
	 */
	function is_available( $package ) {
		global $woocommerce;

		$is_available = true;

    	if ( $this->enabled == "no" ) {

	    	$is_available = false;

    	} else {

			// If post codes are listed, let's use them.
			$codes = '';
			if ( $this->codes != '' ) {
				foreach( explode( ',', $this->codes ) as $code ) {
					$codes[] = $this->clean( $code );
				}
			}

			if ( is_array( $codes ) ) {

				$found_match = false;

				if ( in_array( $this->clean( $package['destination']['postcode'] ), $codes ) )
					$found_match = true;

				// Wildcard search
				if ( ! $found_match ) {

					$customer_postcode = $this->clean( $package['destination']['postcode'] );
					$customer_postcode_length = strlen( $customer_postcode );

					for ( $i = 0; $i <= $customer_postcode_length; $i++ ) {

						if ( in_array( $customer_postcode, $codes ) )
							$found_match = true;

						$customer_postcode = substr( $customer_postcode, 0, -2 ) . '*';
					}
				}

				if ( ! $found_match ) {

					$is_available = false;

				} else {

					$ship_to_countries = '';

					if ( $this->availability == 'specific' ) {
						$ship_to_countries = $this->countries;
					} elseif ( get_option( 'woocommerce_allowed_countries' ) == 'specific' ) {
						$ship_to_countries = get_option( 'woocommerce_specific_allowed_countries' );
					}

					if ( is_array( $ship_to_countries ) && ! in_array( $package['destination']['country'], $ship_to_countries ) ) {
						$is_available = false;
					}

				}
			}

		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}


	/**
	 * taxable_address function.
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	function taxable_address( $address ) {
		global $woocommerce;

		if ( ! empty( $woocommerce->session->chosen_shipping_method ) && $woocommerce->session->chosen_shipping_method == 'local_pickup' ) {
			if ( $this->get_option( 'apply_base_tax' ) == 'yes' ) {

				$country 	= $woocommerce->countries->get_base_country();
				$state 		= $woocommerce->countries->get_base_state();

				$address = array( $country, $state, '', '' );
			}
		}
		return $address;
	}

	/**
	 * Refresh totals when chosen so we can refresh the tax if we are using local pickup.
	 *
	 * @access public
	 * @return void
	 */
	function method_chosen( $method ) {
		global $woocommerce;

		if ( $method == 'local_pickup' && $this->get_option( 'apply_base_tax' ) == 'yes' ) {
			$woocommerce->cart->calculate_totals();
		}
	}

    /**
     * clean function.
     *
     * @access public
     * @param mixed $code
     * @return string
     */
    function clean( $code ) {
    	return str_replace( '-', '', sanitize_title( $code ) ) . ( strstr( $code, '*' ) ? '*' : '' );
    }

}