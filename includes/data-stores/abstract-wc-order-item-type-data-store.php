<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
abstract class Abstract_WC_Order_Item_Type_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {
	/**
	 * Create a new order item in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $item->get_name(),
			'order_item_type' => $item->get_type(),
			'order_id'        => $item->get_order_id(),
		) );
		$item->set_id( $wpdb->insert_id );

		do_action( 'woocommerce_new_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Update a order item in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function update( &$item ) {
		global $wpdb;

		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $item->get_name(),
			'order_item_type' => $item->get_type(),
			'order_id'        => $item->get_order_id(),
		), array( 'order_item_id' => $item->get_id() ) );

		do_action( 'woocommerce_update_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Remove an order item from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;
			do_action( 'woocommerce_before_delete_order_item', $item->get_id() );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', array( 'order_item_id' => $item->get_id() ) );
			do_action( 'woocommerce_delete_order_item', $item->get_id() );
		}
	}

	/**
	 * Read a order item from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item->get_id() ) );

		if ( ! $data ) {
			throw new Exception( __( 'Invalid order item.', 'woocommerce' ) );
		}

		$item->set_props( array(
			'order_id' => $data->order_id,
			'name'     => $data->order_item_name,
			'type'     => $data->order_item_type,
		) );
		$item->read_meta_data();
		$item->set_object_read( true );
	}
}
