<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Factory Class
 *
 * The WooCommerce order factory creating the right order objects.
 *
 * @class 		WC_Order_Factory
 * @version		3.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooCommerce
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

		$order_type = WC_Data_Store::load( 'order' )->get_order_type( $order_id );
		if ( $order_type_data = wc_get_order_type( $order_type ) ) {
			$classname = $order_type_data['class_name'];
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $order_type, $order_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $order_id );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get order item.
	 * @param int
	 * @return WC_Order_Item|false if not found
	 */
	public static function get_order_item( $item_id = 0 ) {
		global $wpdb;

		if ( is_numeric( $item_id ) ) {
			$item_type = WC_Data_Store::load( 'order-item' )->get_order_item_type( $item_id );
			$id        = $item_id;
		} elseif ( $item_id instanceof WC_Order_Item ) {
			$item_type = $item_id->get_type();
			$id        = $item_id->get_id();
		} elseif ( is_object( $item_id ) && ! empty( $item_id->order_item_type ) ) {
			$id        = $item_id->order_item_id;
			$item_type = $item_id->order_item_type;
		} else {
			$item_data = false;
			$item_type = false;
			$id        = false;
		}

		if ( $id && $item_type ) {
			$classname = false;
			switch ( $item_type ) {
				case 'line_item' :
				case 'product' :
					$classname = 'WC_Order_Item_Product';
				break;
				case 'coupon' :
					$classname = 'WC_Order_Item_Coupon';
				break;
				case 'fee' :
					$classname = 'WC_Order_Item_Fee';
				break;
				case 'shipping' :
					$classname = 'WC_Order_Item_Shipping';
				break;
				case 'tax' :
					$classname = 'WC_Order_Item_Tax';
				break;
				default :
					$classname = apply_filters( 'woocommerce_get_order_item_classname', $classname, $item_type, $id );
				break;
			}

			if ( $classname && class_exists( $classname ) ) {
				try {
					return new $classname( $id );
				} catch ( Exception $e ) {
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Get the order ID depending on what was passed.
	 *
	 * @since 3.0.0
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
