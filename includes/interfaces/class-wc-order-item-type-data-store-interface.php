<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store Interface
 *
 * Functions that must be defined by order item store classes.
 *
 * @version  3.0.0
 * @category Interface
 * @author   WooCommerce
 */
interface WC_Order_Item_Type_Data_Store_Interface {
	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param WC_Order_Item $item
	 */
	public function save_item_data( &$item );
}
