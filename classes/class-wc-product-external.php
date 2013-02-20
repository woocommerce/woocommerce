<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * External Product Class
 *
 * External products cannot be bought; they link offsite. Extends simple products.
 *
 * @class 		WC_Product_External
 * @version		2.0.0
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_External extends WC_Product {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		$this->product_type = 'external';
		parent::__construct( $product );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @access public
	 * @return cool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', false, $this );
	}

	/**
	 * get_product_url function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_product_url() {
		return $this->product_url;
	}

	/**
	 * get_button_text function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_button_text() {
		return $this->button_text ? $this->button_text : __( 'Buy product', 'woocommerce' );
	}
}