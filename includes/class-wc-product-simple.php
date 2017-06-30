<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simple Product Class.
 *
 * The default product type kinda product.
 *
 * @class 		WC_Product_Simple
 * @package		WooCommerce/Classes/Products
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Simple extends WC_Product {

	/**
	 * Initialize simple product.
	 *
	 * @param mixed $product
	 */
	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'simple';
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}
}
