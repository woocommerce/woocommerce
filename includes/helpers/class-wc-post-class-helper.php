<?php

return new WC_Post_Class_Helper();

class WC_Post_Class_Helper extends WC_Helper {
	/**
	 * Adds extra post classes for products
	 *
	 * @since 2.0
	 * @access public
	 * @param array $classes
	 * @param string|array $class
	 * @param int $post_id
	 * @return array
	 */
	public function post_class( $classes, $class = '', $post_id = '' ) {
		if ( ! $post_id )
			return $classes;

		$product = get_product( $post_id );

		if ( $product ) {
			if ( $product->is_on_sale() ) {
				$classes[] = 'sale';
			}
			if ( $product->is_featured() ) {
				$classes[] = 'featured';
			}
			if ( $product->is_downloadable() ) {
				$classes[] = 'downloadable';
			}
			if ( $product->is_virtual() ) {
				$classes[] = 'virtual';
			}
			if ( $product->is_sold_individually() ) {
				$classes[] = 'sold-individually';
			}
			if ( $product->is_taxable() ) {
				$classes[] = 'taxable';
			}
			if ( $product->is_shipping_taxable() ) {
				$classes[] = 'shipping-taxable';
			}
			if ( $product->is_purchasable() ) {
				$classes[] = 'purchasable';
			}
			if ( isset( $product->product_type ) ) {
				$classes[] = "product-type-".$product->product_type;
			}
			$classes[] = $product->stock_status;
		}

		return $classes;
	}
}