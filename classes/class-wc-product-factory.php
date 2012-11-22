<?php

/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object
 *
 * @class 		WC_Product_Factory
 * @version		1.7
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */

class WC_Product_Factory {
	public function __construct() {
	}

	public function get_product( $the_product = false, $args ) {
		global $post;
	
		if ( false === $the_product )
			$the_product = $post;
		elseif ( is_numeric( $the_product ) )
			$the_product = get_post( $the_product );
			
		$product_id 	= absint( $the_product->ID );
		$terms 			= get_the_terms( $product_id, 'product_type' );
		$product_type 	= isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
		$post_type 		= $the_product->post_type;
		
		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_product_class', 'WC_Product_' . $product_type, $product_type, $post_type, $product_id );
		
		if ( class_exists( $classname ) ) {
			return new $classname( $the_product, $args );
		} else {
			// Use simple
			return new WC_Product_Simple( $the_product );
		}
	}
}