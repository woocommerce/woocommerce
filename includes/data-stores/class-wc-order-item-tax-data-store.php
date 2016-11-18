<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Tax Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Tax_Data_Store extends Abstract_WC_Order_Item_Data_Store implements WC_Object_Data_Store, WC_Order_Item_Data_Store_Interface {
	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'rate_id'            => get_metadata( 'order_item', $item->get_id(), 'rate_id', true ),
			'label'              => get_metadata( 'order_item', $item->get_id(), 'label', true ),
			'compound'           => get_metadata( 'order_item', $item->get_id(), 'compound', true ),
			'tax_total'          => get_metadata( 'order_item', $item->get_id(), 'tax_amount', true ),
			'shipping_tax_total' => get_metadata( 'order_item', $item->get_id(), 'shipping_tax_amount', true ),
		) );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function save_item_data( &$item ) {
		wc_update_order_item_meta( $item->get_id(), 'rate_id', $item->get_rate_id() );
		wc_update_order_item_meta( $item->get_id(), 'label', $item->get_label() );
		wc_update_order_item_meta( $item->get_id(), 'compound', $item->get_compound() );
		wc_update_order_item_meta( $item->get_id(), 'tax_amount', $item->get_tax_total() );
		wc_update_order_item_meta( $item->get_id(), 'shipping_tax_amount', $item->get_shipping_tax_total() );
	}
}
