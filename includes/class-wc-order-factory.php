<?php

/**
 * Order Factory Class
 *
 * The WooCommerce order factory creating the right order objects
 *
 * @class 		WC_Order_Factory
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Order_Factory {

	/**
	 * get_order function.
	 *
	 * @access public
	 * @param bool $the_order (default: false)
	 * @param array $args (default: array())
	 * @return WC_Order
	 */
	public function get_order( $the_order = false, $args = array() ) {
		global $post;

		if ( false === $the_order ) {
			$the_order = $post;
		} elseif ( is_numeric( $the_order ) ) {
			$the_order = get_post( $the_order );
		}

		if ( ! is_object( $the_order ) ) {
			return false;
		}

		$order_id  = absint( $the_order->ID );
		$post_type = $the_order->post_type;

		if ( 'shop_order' === $post_type ) {
			$classname  = 'WC_Order';
			$order_type = 'simple';
		} else {
			$classname  = false;
			$order_type = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $order_type, $post_type, $order_id );

		if ( ! class_exists( $classname ) ) {
			$classname = 'WC_Order';
		}

		return new $classname( $the_order, $args );
	}
}