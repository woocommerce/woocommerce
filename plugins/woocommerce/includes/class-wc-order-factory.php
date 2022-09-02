<?php
/**
 * Order Factory
 *
 * The WooCommerce order factory creating the right order objects.
 *
 * @version 3.0.0
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order factory class
 */
class WC_Order_Factory {

	/**
	 * Get order.
	 *
	 * @param  mixed $order_id (default: false) Order ID to get.
	 * @return \WC_Order|bool
	 */
	public static function get_order( $order_id = false ) {
		$order_id = self::get_order_id( $order_id );

		if ( ! $order_id ) {
			return false;
		}

		$classname = self::get_class_name_for_order_id( $order_id );
		if ( ! $classname ) {
			return false;
		}

		try {
			return new $classname( $order_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $order_id ) );
			return false;
		}
	}

	/**
	 * Get multiple orders (by ID).
	 *
	 * @param array[mixed] $order_ids                     Array of order IDs to get.
	 * @param boolean      $skip_invalid (default: false) TRUE if invalid IDs or orders should be ignored.
	 * @return array[\WC_Order]
	 *
	 * @throws \Exception When an invalid order is found.
	 */
	public static function get_orders( $order_ids = array(), $skip_invalid = false ) {
		$result    = array();
		$order_ids = array_filter( array_map( array( __CLASS__, 'get_order_id' ), $order_ids ) );

		// We separate order list by class, since their datastore might be different.
		$order_list_by_class = array();
		foreach ( $order_ids as $order_id ) {
			$classname = self::get_class_name_for_order_id( $order_id );
			if ( ! $classname && ! $skip_invalid ) {
				// translators: %d is an order ID.
				throw new \Exception( sprintf( __( 'Could not find classname for order ID %d', 'woocommerce' ), $order_id ) );
			}
			if ( ! isset( $order_list_by_class[ $classname ] ) ) {
				$order_list_by_class[ $classname ] = array();
			}
			try {
				$obj = new $classname();
				$obj->set_defaults();
				$obj->set_id( $order_id );

				$order_list_by_class[ $classname ][ $order_id ] = $obj;
			} catch ( \Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, array( $order_id ) );

				if ( ! $skip_invalid ) {
					throw $e;
				}
			}
		}

		foreach ( $order_list_by_class as $classname => $order_list ) {
			$data_store = ( new $classname() )->get_data_store();
			try {
				$data_store->read_multiple( $order_list );
			} catch ( \Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, $order_ids );

				if ( ! $skip_invalid ) {
					throw $e;
				}
			}
			foreach ( $order_list as $order ) {
				$result[ $order->get_id() ] = $order;
			}
		}

		// restore the sort order.
		$result = array_replace( array_flip( $order_ids ), $result );

		return array_values( $result );
	}

	/**
	 * Get order item.
	 *
	 * @param int $item_id Order item ID to get.
	 * @return WC_Order_Item|false if not found
	 */
	public static function get_order_item( $item_id = 0 ) {
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
			$item_type = false;
			$id        = false;
		}

		if ( $id && $item_type ) {
			$classname = false;
			switch ( $item_type ) {
				case 'line_item':
				case 'product':
					$classname = 'WC_Order_Item_Product';
					break;
				case 'coupon':
					$classname = 'WC_Order_Item_Coupon';
					break;
				case 'fee':
					$classname = 'WC_Order_Item_Fee';
					break;
				case 'shipping':
					$classname = 'WC_Order_Item_Shipping';
					break;
				case 'tax':
					$classname = 'WC_Order_Item_Tax';
					break;
			}

			$classname = apply_filters( 'woocommerce_get_order_item_classname', $classname, $item_type, $id );

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
	 * @param  mixed $order Order data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_order_id( $order ) {
		global $post;

		if ( false === $order && is_a( $post, 'WP_Post' ) && 'shop_order' === get_post_type( $post ) ) {
			return absint( $post->ID );
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

	/**
	 * Gets the class name an order instance should have based on its ID.
	 *
	 * @since 6.9.0
	 * @param int $order_id The order ID.
	 * @return string The class name or FALSE if the class does not exist.
	 */
	private static function get_class_name_for_order_id( $order_id ) {
		$order_type      = WC_Data_Store::load( 'order' )->get_order_type( $order_id );
		$order_type_data = wc_get_order_type( $order_type );
		if ( $order_type_data ) {
			$classname = $order_type_data['class_name'];
		} else {
			$classname = false;
		}

		/**
		 * Filter classname so that the class can be overridden if extended.
		 *
		 * @param $classname  Order classname.
		 * @param $order_type Order type.
		 * @param $order_id   Order ID.
		 */
		$classname = apply_filters( 'woocommerce_order_class', $classname, $order_type, $order_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		return $classname;
	}

}
