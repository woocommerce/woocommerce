<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Local Pickup Shipping Method.
 *
 * A simple shipping method allowing free pickup as a shipping method.
 *
 * @class 		WC_Shipping_Local_Pickup
 * @version		2.3.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Local_Pickup extends WC_Shipping_Method {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'local_pickup';
		$this->method_title       = __( 'Local Pickup', 'woocommerce' );
		$this->method_description = __( 'Local pickup is a simple method which allows customers to pick up orders themselves.', 'woocommerce' );
		$this->init();
	}

	/**
	 * Initialize local pickup.
	 */
	public function init() {

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
	}

	/**
	 * calculate_shipping function.
	 * Calculate local pickup shipping.
	 */
	public function calculate_shipping() {
		$rate = array(
			'id' 		=> $this->id,
			'label' 	=> $this->title,
		);
		$this->add_rate( $rate );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable local pickup', 'woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Local Pickup', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'codes' => array(
				'title'       => __( 'Allowed ZIP/Post Codes', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => __( 'What ZIP/post codes are available for local pickup?', 'woocommerce' ),
				'default'     => '',
				'description' => __( 'Separate codes with a comma. Accepts wildcards, e.g. <code>P*</code> will match a postcode of PE30. Also accepts a pattern, e.g. <code>NG1___</code> would match NG1 1AA but not NG10 1AA', 'woocommerce' ),
				'placeholder' => 'e.g. 12345, 56789'
			),
			'availability' => array(
				'title'    => __( 'Method availability', 'woocommerce' ),
				'type'     => 'select',
				'default'  => 'all',
				'class'    => 'availability wc-enhanced-select',
				'options'  => array(
					'all'      => __( 'All allowed countries', 'woocommerce' ),
					'specific' => __( 'Specific Countries', 'woocommerce' )
				)
			),
			'countries' => array(
				'title'             => __( 'Specific Countries', 'woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'options'           => WC()->countries->get_shipping_countries(),
				'custom_attributes' => array(
					'data-placeholder'  => __( 'Select some countries', 'woocommerce' )
				)
			)
		);
	}

	/**
	 * Get postcodes for this method.
	 * @return array
	 */
	public function get_valid_postcodes() {
		$codes = array();

		if ( $this->codes != '' ) {
			foreach( explode( ',', $this->codes ) as $code ) {
				$codes[] = strtoupper( trim( $code ) );
			}
		}

		return $codes;
	}

	/**
	 * See if a given postcode matches valid postcodes.
	 * @param  string postcode
	 * @param  string country code
	 * @return boolean
	 */
	public function is_valid_postcode( $postcode, $country ) {
		$codes              = $this->get_valid_postcodes();
		$postcode           = $this->clean( $postcode );
		$formatted_postcode = wc_format_postcode( $postcode, $country );

		if ( in_array( $postcode, $codes ) || in_array( $formatted_postcode, $codes ) ) {
			return true;
		}

		// Pattern matching
		foreach ( $codes as $c ) {
			$pattern = '/^' . str_replace( '_', '[0-9a-zA-Z]', preg_quote( $c ) ) . '$/i';
			if ( preg_match( $pattern, $postcode ) ) {
				return true;
			}
		}

		// Wildcard search
		$wildcard_postcode = $formatted_postcode . '*';
		$postcode_length   = strlen( $formatted_postcode );

		for ( $i = 0; $i < $postcode_length; $i++ ) {
			if ( in_array( $wildcard_postcode, $codes ) ) {
				return true;
			}
			$wildcard_postcode = substr( $wildcard_postcode, 0, -2 ) . '*';
		}

		return false;
	}

	/**
	 * See if the method is available.
	 *
	 * @param array $package
	 * @return bool
	 */
	public function is_available( $package ) {
		$is_available = "yes" === $this->enabled;

		if ( $is_available && $this->get_valid_postcodes() ) {
			$is_available = $this->is_valid_postcode( $package['destination']['postcode'], $package['destination']['country'] );
		}

		if ( $is_available ) {
			if ( $this->availability === 'specific' ) {
				$ship_to_countries = $this->countries;
			} else {
				$ship_to_countries = array_keys( WC()->countries->get_shipping_countries() );
			}
			if ( is_array( $ship_to_countries ) && ! in_array( $package['destination']['country'], $ship_to_countries ) ) {
				$is_available = false;
			}
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}

	/**
	 * Clean code string.
	 *
	 * @access public
	 * @param mixed $code
	 * @return string
	 */
	public function clean( $code ) {
		return str_replace( '-', '', sanitize_title( $code ) ) . ( strstr( $code, '*' ) ? '*' : '' );
	}
}
