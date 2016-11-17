<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Shipping Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Item_Shipping_Data_Store extends WC_Order_Item_Data_Store implements WC_Object_Data_Store {

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'method_id' => get_metadata( 'order_item', $item->get_id(), 'method_id', true ),
			'total'     => get_metadata( 'order_item', $item->get_id(), 'cost', true ),
			'taxes'     => get_metadata( 'order_item', $item->get_id(), 'taxes', true ),
		) );
	}

	/**
	 * Save properties specific to this order item.
	 */
	public function save_item_data( &$item ) {
		wc_update_order_item_meta( $item->get_id(), 'method_id', $item->get_method_id() );
		wc_update_order_item_meta( $item->get_id(), 'cost', $item->get_total() );
		wc_update_order_item_meta( $item->get_id(), 'total_tax', $item->get_total_tax() );
		wc_update_order_item_meta( $item->get_id(), 'taxes', $item->get_taxes() );
	}
}
