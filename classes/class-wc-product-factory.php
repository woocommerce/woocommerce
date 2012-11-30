<?php

/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object
 *
 * @class 		WC_Product_Factory
 * @version		1.7.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Product_Factory {

	/**
	 * get_product function.
	 *
	 * @access public
	 * @param bool $the_product (default: false)
	 * @param array $args (default: array())
	 * @return void
	 */
	public function get_product( $the_product = false, $args = array() ) {
		global $post;

		if ( false === $the_product ) {
			$the_product = $post;
		} elseif ( is_numeric( $the_product ) ) {
			$the_product = get_post( $the_product );
		}

		if ( ! $the_product )
			return false;

		$product_id = absint( $the_product->ID );
		$post_type  = $the_product->post_type;

		if ( 'product_variation' == $post_type ) {
			$product_type = 'variation';
		} else {
			$terms        = get_the_terms( $product_id, 'product_type' );
			$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_product_class', 'WC_Product_' . ucfirst( $product_type ), $product_type, $post_type, $product_id );

		if ( class_exists( $classname ) ) {
			return new $classname( $the_product, $args );
		} else {
			// Use simple
			return new WC_Product_Simple( $the_product, $args );
		}
	}
}