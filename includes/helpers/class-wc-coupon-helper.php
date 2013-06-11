<?php

class WC_Coupon_Helper extends WC_Helper {
	/**
	 * Get coupon types.
	 *
	 * @access public
	 * @return array
	 */
	public function get_coupon_discount_types() {
		if ( ! isset( $this->coupon_discount_types ) ) {
			$this->coupon_discount_types = apply_filters( 'woocommerce_coupon_discount_types', array(
    			'fixed_cart' 	=> __( 'Cart Discount', 'woocommerce' ),
    			'percent' 		=> __( 'Cart % Discount', 'woocommerce' ),
    			'fixed_product'	=> __( 'Product Discount', 'woocommerce' ),
    			'percent_product'	=> __( 'Product % Discount', 'woocommerce' )
    		) );
		}
		return $this->coupon_discount_types;
	}


	/**
	 * Get a coupon type's name.
	 *
	 * @access public
	 * @param string $type (default: '')
	 * @return string
	 */
	public function get_coupon_discount_type( $type = '' ) {
		$types = (array) $this->get_coupon_discount_types();
		if ( isset( $types[$type] ) ) return $types[$type];
	}
}