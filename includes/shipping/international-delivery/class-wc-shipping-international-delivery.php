<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * International Delivery - Based on the Flat Rate Shipping Method
 *
 * @class 		WC_Shipping_Flat_Rate
 * @version		2.4.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_International_Delivery extends WC_Shipping_Flat_Rate {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'international_delivery';
		$this->method_title       = __( 'International Flat Rate', 'woocommerce' );
		$this->method_description = __( 'International Flat Rate Shipping lets you charge a fixed rate for shipping.', 'woocommerce' );
		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise settings form fields
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields['availability'] = array(
			'title'			=> __( 'Availability', 'woocommerce' ),
			'type'			=> 'select',
			'class'         => 'wc-enhanced-select',
			'description'	=> '',
			'default'		=> 'including',
			'options'		=> array(
				'including' => __( 'Selected countries', 'woocommerce' ),
				'excluding' => __( 'Excluding selected countries', 'woocommerce' ),
			)
		);
	}

	/**
	 * is_available function.
	 *
	 * @param array $package
	 * @return bool
	 */
	public function is_available( $package ) {
		if ( "no" === $this->enabled ) {
			return false;
		}
		if ( 'including' === $this->availability ) {
			if ( is_array( $this->countries ) && ! in_array( $package['destination']['country'], $this->countries ) ) {
				return false;
			}
		} else {
			if ( is_array( $this->countries ) && ( in_array( $package['destination']['country'], $this->countries ) || ! $package['destination']['country'] ) ) {
				return false;
			}
		}
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true, $package );
	}
}
