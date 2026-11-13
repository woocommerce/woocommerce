<?php
/**
 * Class WC_Order_Item_Product_Data_Store file.
 *
 * @package WooCommerce\DataStores
 */

use Automattic\WooCommerce\Enums\ProductTaxStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Product Data Store
 *
 * @version  3.0.0
 */
class WC_Order_Item_Product_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Product_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array( '_product_id', '_variation_id', '_qty', '_tax_class', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data', '_product_tax_status' );

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'product_id'   => get_metadata( 'order_item', $id, '_product_id', true ),
				'variation_id' => get_metadata( 'order_item', $id, '_variation_id', true ),
				'quantity'     => get_metadata( 'order_item', $id, '_qty', true ),
				'tax_class'    => get_metadata( 'order_item', $id, '_tax_class', true ),
				'subtotal'     => get_metadata( 'order_item', $id, '_line_subtotal', true ),
				'total'        => get_metadata( 'order_item', $id, '_line_total', true ),
				'taxes'        => get_metadata( 'order_item', $id, '_line_tax_data', true ),
			)
		);
		if ( $item instanceof WC_Order_Item_Product ) {
			$item->set_recorded_tax_status( get_metadata( 'order_item', $id, '_product_tax_status', true ) );
			if ( '' === $item->get_recorded_tax_status() ) {
				$item->set_recorded_tax_status( $this->get_tax_status_from_charged_tax( $item ) );
			}
		}
		$item->set_object_read( true );
	}

	/**
	 * Create a new order item in the database.
	 *
	 * A line built by code that sets its props itself records its tax status here, so that every line saved from now
	 * on has one.
	 *
	 * @since 11.4.0
	 * @param WC_Order_Item $item Order item object.
	 * @return void
	 */
	public function create( &$item ) {
		if ( $item instanceof WC_Order_Item_Product && '' === $item->get_recorded_tax_status() ) {
			$item->record_tax_status();
		}
		parent::create( $item );
	}

	/**
	 * The tax status of a line that was saved before statuses were recorded, from what the line was charged: taxable
	 * when it carries a tax amount, none otherwise.
	 *
	 * The product is not read: its status may have changed since the sale, or the product may be gone. The status is
	 * then saved with the line, so a later recalculation that brings its tax to zero does not change it.
	 *
	 * @param WC_Order_Item_Product $item Order item object, with its tax amounts read.
	 * @return string
	 */
	private function get_tax_status_from_charged_tax( WC_Order_Item_Product $item ): string {
		$was_charged_tax = 0.0 !== (float) $item->get_subtotal_tax( 'edit' ) || 0.0 !== (float) $item->get_total_tax( 'edit' );

		return $was_charged_tax ? ProductTaxStatus::TAXABLE : ProductTaxStatus::NONE;
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$changes           = $item->get_changes();
		$meta_key_to_props = array(
			'_product_id'        => 'product_id',
			'_variation_id'      => 'variation_id',
			'_qty'               => 'quantity',
			'_tax_class'         => 'tax_class',
			'_line_subtotal'     => 'subtotal',
			'_line_subtotal_tax' => 'subtotal_tax',
			'_line_total'        => 'total',
			'_line_tax'          => 'total_tax',
			'_line_tax_data'     => 'taxes',
		);
		$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'order_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'order_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}

		$recorded_tax_status = $item->get_recorded_tax_status();
		if ( '' !== $recorded_tax_status ) {
			update_metadata( 'order_item', $id, '_product_tax_status', $recorded_tax_status );
		} elseif ( metadata_exists( 'order_item', $id, '_product_tax_status' ) ) {
			delete_metadata( 'order_item', $id, '_product_tax_status' );
		}
	}

	/**
	 * Get a list of download IDs for a specific item from an order.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Product $item Product order item object.
	 * @param WC_Order              $order Order object.
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
