<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store: Misc Order Item Data functions.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Data_Store implements WC_Order_Item_Data_Store_Interface {

	/**
	 * Add an order item to an order.
	 *
	 * @since  3.0.0
	 * @param  int   $order_id
	 * @param  array $item order_item_name and order_item_type.
	 * @return int Order Item ID
	 */
	public function add_order_item( $order_id, $item ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . "woocommerce_order_items",
			array(
				'order_item_name'       => $item['order_item_name'],
				'order_item_type'       => $item['order_item_type'],
				'order_id'              => $order_id,
			),
			array(
				'%s',
				'%s',
				'%d',
			)
		);

		return absint( $wpdb->insert_id );
	}

	/**
	 * Update an order item.
	 *
	 * @since  3.0.0
	 * @param  int   $item_id
	 * @param  array $item order_item_name or order_item_type.
	 * @return boolean
	 */
	public function update_order_item( $item_id, $item ) {
		global $wpdb;
		return $wpdb->update( $wpdb->prefix . 'woocommerce_order_items', $item, array( 'order_item_id' => $item_id ) );
	}

	/**
	 * Delete an order item.
	 *
	 * @since  3.0.0
	 * @param  int   $item_id
	 */
	public function delete_order_item( $item_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $item_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d", $item_id ) );
	}

	/**
	 * Update term meta.
	 *
	 * @since  3.0.0
	 * @param  int    $item_id
	 * @param  string $meta_key
	 * @param  mixed  $meta_value
	 * @param  string $prev_value (default: '')
	 * @return bool
	 */
	public function update_metadata( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'order_item', $item_id, $meta_key, is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $prev_value );
	}

	/**
	 * Add term meta.
	 *
	 * @since  3.0.0
	 * @param  int    $item_id
	 * @param  string $meta_key
	 * @param  mixed  $meta_value
	 * @param  bool   $unique (default: false)
	 * @return int    New row ID or 0
	 */
	public function add_metadata( $item_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'order_item', $item_id, $meta_key, is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $unique );
	}

	/**
	 * Delete term meta.
	 *
	 * @since  3.0.0
	 * @param  int    $item_id
	 * @param  string $meta_key
	 * @param  string $meta_value (default: '')
	 * @param  bool   $delete_all (default: false)
	 * @return bool
	 */
	public function delete_metadata( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_metadata( 'order_item', $item_id, $meta_key, is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $delete_all );
	}

	/**
	 * Get term meta.
	 *
	 * @since  3.0.0
	 * @param  int    $item_id
	 * @param  string $key
	 * @param  bool   $single (default: true)
	 * @return mixed
	 */
	public function get_metadata( $item_id, $key, $single = true ) {
		return get_metadata( 'order_item', $item_id, $key, $single );
	}

	/**
	 * Get order ID by order item ID.
	 *
	 * @since 3.0.0
	 * @param  int $item_id
	 * @return int
	 */
	function get_order_id_by_order_item_id( $item_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
			$item_id
		) );
	}

	/**
	 * Get the order item type based on Item ID.
	 *
	 * @since 3.0.0
	 * @param int $item_id
	 * @return string
	 */
	public function get_order_item_type( $item_id ) {
		global $wpdb;
		$item_data = $wpdb->get_row( $wpdb->prepare( "SELECT order_item_type FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item_id ) );
		return $item_data->order_item_type;
	}
}
