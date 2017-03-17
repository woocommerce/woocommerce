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
interface WC_Order_Item_Product_Data_Store_Interface {
	/**
	 * Get a list of download IDs for a specific item from an order.
	 *
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @return array
	 */
	 public function get_download_ids( $item, $order );
}
