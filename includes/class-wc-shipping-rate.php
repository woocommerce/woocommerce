<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Shipping Rate Class
 *
 * Simple Class for storing rates.
 *
 * @class 		WC_Shipping_Rate
 * @version		2.3.0
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
	 * Constructor
	 *
	 * @param string $id
	 * @param string $label
	 * @param float $cost
	 * @param array $taxes
	 * @param string $method_id
	 */
	public function __construct( $id, $label, $cost, $taxes, $method_id ) {
		$this->id 			= $id;
		$this->label 		= $label;
		$this->cost 		= $cost;
		$this->taxes 		= $taxes ? $taxes : array();
		$this->method_id 	= $method_id;
	}

	/**
	 * get_shipping_tax function.
	 *
	 * @return array
	 */
	public function get_shipping_tax() {
		$taxes = 0;
		if ( $this->taxes && sizeof( $this->taxes ) > 0 && ! WC()->customer->is_vat_exempt() ) {
			$taxes = array_sum( $this->taxes );
		}
		return apply_filters( 'woocommerce_get_shipping_tax', $taxes, $this );
	}
}
