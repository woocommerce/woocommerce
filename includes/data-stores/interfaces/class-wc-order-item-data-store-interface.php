<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store Interface
 *
 * Functions that must be defined by the order item data store (for functions).
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooCommerce
 */
interface WC_Order_Item_Data_Store_Interface {
	/**
	 * Add an order item to an order.
	 * @param  int   $order_id
	 * @param  array $item. order_item_name and order_item_type.
	 * @return int Order Item ID
	 */
	public function add_order_item( $order_id, $item );
}
