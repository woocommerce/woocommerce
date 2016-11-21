<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Order Factory Class
 *
 * The WooCommerce order factory creating the right order objects.
 *
 * @class 		WC_Order_Factory
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Order_Factory {

	/**
	 * Get order.
	 *
	 * @param  mixed $order_id (default: false)
	 * @return WC_Order|bool
	 */
	public static function get_order( $order_id = false ) {
		$order_id = self::get_order_id( $order_id );

		if ( ! $order_id ) {
			return false;
		}

		$post_type = get_post_type( $order_id );

		if ( $order_type = wc_get_order_type( $post_type ) ) {
			$classname = $order_type['class_name'];
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $post_type, $order_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		return new $classname( $order_id );
	}

	/**
	 * Get order item.
	 * @param int
	 * @return WC_Order_Item|false if not found
	 */
	public static function get_order_item( $item_id = 0 ) {
		global $wpdb;

		if ( is_numeric( $item_id ) ) {
			$item_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item_id ) );
			$item_type = $item_data->order_item_type;
		} elseif ( $item_id instanceof WC_Order_Item ) {
			$item_data = $item_id->get_data();
			$item_type = $item_data->get_type();
		} elseif ( is_object( $item_id ) && ! empty( $item_id->order_item_type ) ) {
			$item_data = $item_id;
			$item_type = $item_id->order_item_type;
		} else {
			$item_data = false;
			$item_type = false;
		}

		if ( $item_data && $item_type ) {
			switch ( $item_type ) {
				case 'line_item' :
				case 'product' :
					return new WC_Order_Item_Product( $item_data );
				break;
				case 'coupon' :
					return new WC_Order_Item_Coupon( $item_data );
				break;
				case 'fee' :
					return new WC_Order_Item_Fee( $item_data );
				break;
				case 'shipping' :
					return new WC_Order_Item_Shipping( $item_data );
				break;
				case 'tax' :
					return new WC_Order_Item_Tax( $item_data );
				break;
			}
		}
		return false;
	}

	/**
	 * Get the order ID depending on what was passed.
	 *
	 * @since 2.7.0
	 * @param  mixed $order
	 * @return int|bool false on failure
	 */
	public static function get_order_id( $order ) {
		global $post;

		if ( false === $order && is_a( $post, 'WP_Post' ) && 'shop_order' === get_post_type( $post ) ) {
			return $post->ID;
		} elseif ( is_numeric( $order ) ) {
			return $order;
		} elseif ( $order instanceof WC_Abstract_Order ) {
			return $order->get_id();
		} elseif ( ! empty( $order->ID ) ) {
			return $order->ID;
		} else {
			return false;
		}
	}
}
