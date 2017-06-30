<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Shipping Rate Class.
 *
 * Simple Class for storing rates.
 *
 * @class 		WC_Shipping_Rate
 * @version		2.6.0
 * @package		WooCommerce/Classes/Shipping
 * @category	Class
 * @author 		WooThemes
 */
class WC_Shipping_Rate {

	/** @var string Rate ID. */
	public $id        = '';

	/** @var string Label for the rate. */
	public $label     = '';

	/** @var float Cost for the rate. */
	public $cost      = 0;

	/** @var array Array of taxes for the rate. */
	public $taxes     = array();

	/** @var string Label for the rate. */
	public $method_id = '';

	/**
	 * Stores meta data for this rate
	 * @since 2.6.0
	 * @var array
	 */
	private $meta_data = array();

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param integer $cost
	 * @param array $taxes
	 * @param string $method_id
	 */
	public function __construct( $id = '', $label = '', $cost = 0, $taxes = array(), $method_id = '' ) {
		$this->id        = $id;
		$this->label     = $label;
		$this->cost      = $cost;
		$this->taxes     = ! empty( $taxes ) && is_array( $taxes ) ? $taxes : array();
		$this->method_id = $method_id;
	}

	/**
	 * Get shipping tax.
	 *
	 * @return array
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_get_shipping_tax', sizeof( $this->taxes ) > 0 && ! WC()->customer->get_is_vat_exempt() ? array_sum( $this->taxes ) : 0, $this );
	}

	/**
	 * Get label.
	 *
	 * @return string
	 */
	public function get_label() {
		return apply_filters( 'woocommerce_shipping_rate_label', $this->label, $this );
	}

	/**
	 * Add some meta data for this rate.
	 * @since 2.6.0
	 * @param string $key
	 * @param string $value
	 */
	public function add_meta_data( $key, $value ) {
		$this->meta_data[ wc_clean( $key ) ] = wc_clean( $value );
	}

	/**
	 * Get all meta data for this rate.
	 * @since 2.6.0
	 */
	public function get_meta_data() {
		return $this->meta_data;
	}
}
