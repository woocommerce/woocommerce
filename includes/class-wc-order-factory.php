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

		if ( ! $the_order )
			return false;

		if ( is_object ( $the_order ) ) {
			$order_id = absint( $the_order->ID );
			$post_type  = $the_order->post_type;
		}

		if ( 'shop_order' == $post_type ) {
			// For now this is the only order type, this conditional needs to be extended over time
			$order_type = 'simple';

			// Create a WC coding standards compliant class name e.g. WC_Order_Type_Class instead of WC_order_type-class
			$classname = 'WC_Order_' . implode( '_', array_map( 'ucfirst', explode( '-', $order_type ) ) );
		} else {
			$classname = false;
			$order_type = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $order_type, $post_type, $order_id );

		if ( ! class_exists( $classname ) )
			$classname = 'WC_Order_Simple';

		return new $classname( $the_order, $args );
	}
}
