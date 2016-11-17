<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Coupon Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Item_Coupon_Data_Store extends WC_Order_Item_Data_Store implements WC_Object_Data_Store {

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'discount'     => get_metadata( 'order_item', $item->get_id(), 'discount_amount', true ),
			'discount_tax' => get_metadata( 'order_item', $item->get_id(), 'discount_amount_tax', true ),
		) );
	}

	public function save_item_data( &$item ) {
		wc_update_order_item_meta( $item->get_id(), 'discount_amount', $item->get_discount() );
		wc_update_order_item_meta( $item->get_id(), 'discount_amount_tax', $item->get_discount_tax() );
	}

}
