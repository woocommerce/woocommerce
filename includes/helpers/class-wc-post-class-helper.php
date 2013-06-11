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
	public function post_class( $classes, $class, $post_id ) {
		$product = get_product( $post_id );

		if ( $product ) {
			if ( $product->is_on_sale() ) {
				$classes[] = 'sale';
			}
			if ( $product->is_featured() ) {
				$classes[] = 'featured';
			}
			$classes[] = $product->stock_status;
		}

		return $classes;
	}
}