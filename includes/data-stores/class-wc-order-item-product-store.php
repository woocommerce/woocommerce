<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Product Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Product_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface, WC_Order_Item_Product_Data_Store_Interface {
	/**
	 * Data stored in meta keys.
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array( '_product_id', '_variation_id', '_qty', '_tax_class', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' );

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'product_id'   => get_metadata( 'order_item', $item->get_id(), '_product_id', true ),
			'variation_id' => get_metadata( 'order_item', $item->get_id(), '_variation_id', true ),
			'quantity'     => get_metadata( 'order_item', $item->get_id(), '_qty', true ),
			'tax_class'    => get_metadata( 'order_item', $item->get_id(), '_tax_class', true ),
			'subtotal'     => get_metadata( 'order_item', $item->get_id(), '_line_subtotal', true ),
			'total'        => get_metadata( 'order_item', $item->get_id(), '_line_total', true ),
			'taxes'        => get_metadata( 'order_item', $item->get_id(), '_line_tax_data', true ),
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
		wc_update_order_item_meta( $item->get_id(), '_product_id', $item->get_product_id( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_variation_id', $item->get_variation_id( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_qty', $item->get_quantity( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_tax_class', $item->get_tax_class( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_line_subtotal', $item->get_subtotal( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_line_subtotal_tax', $item->get_subtotal_tax( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_line_total', $item->get_total( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_line_tax', $item->get_total_tax( 'edit' ) );
		wc_update_order_item_meta( $item->get_id(), '_line_tax_data', $item->get_taxes( 'edit' ) );
	}

	/**
	 * Get a list of download IDs for a specific item from an order.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @return array
	 */
	public function get_download_ids( $item, $order ) {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_email = %s AND order_key = %s AND product_id = %d ORDER BY permission_id",
				$order->get_billing_email(),
				$order->get_order_key(),
				$item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id()
			)
		);
	}
}
