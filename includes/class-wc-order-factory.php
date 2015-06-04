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
	 * @param bool $the_order (default: false)
	 * @return WC_Order|bool
	 */
	public function get_order( $the_order = false ) {
		global $post;

		if ( false === $the_order ) {
			$the_order = $post;
		} elseif ( is_numeric( $the_order ) ) {
			$the_order = get_post( $the_order );
		} elseif ( $the_order instanceof WC_Order ) {
			$the_order = get_post( $the_order->id );
		}

		if ( ! $the_order || ! is_object( $the_order ) ) {
			return false;
		}

		$order_id  = absint( $the_order->ID );
		$post_type = $the_order->post_type;

		if ( $order_type = wc_get_order_type( $post_type ) ) {
			$classname = $order_type['class_name'];
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $post_type, $order_id, $the_order );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		return new $classname( $the_order );
	}
}
