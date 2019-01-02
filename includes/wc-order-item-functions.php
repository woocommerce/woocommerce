<?php
/**
 * WooCommerce Order Item Functions
 *
 * Functions for order specific things.
 *
 * @package WooCommerce/Functions
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add a item to an order (for example a line item).
 *
 * @param int   $order_id   Order ID.
 * @param array $item_array Items list.
 *
 * @throws Exception        When `WC_Data_Store::load` validation fails.
 * @return int|bool         Item ID or false
 */
function wc_add_order_item( $order_id, $item_array ) {
	$order_id = absint( $order_id );

	if ( ! $order_id ) {
		return false;
	}

	$defaults = array(
		'order_item_name' => '',
		'order_item_type' => 'line_item',
	);

	$item_array = wp_parse_args( $item_array, $defaults );
	$data_store = WC_Data_Store::load( 'order-item' );
	$item_id    = $data_store->add_order_item( $order_id, $item_array );
	$item       = WC_Order_Factory::get_order_item( $item_id );

	do_action( 'woocommerce_new_order_item', $item_id, $item, $order_id );

	return $item_id;
}

/**
 * Update an item for an order.
 *
 * @param int   $item_id Item ID.
 * @param array $args    Either `order_item_type` or `order_item_name`.
 *
 * @since  2.2
 * @throws Exception     When `WC_Data_Store::load` validation fails.
 * @return bool          True if successfully updated, false otherwise.
 */
function wc_update_order_item( $item_id, $args ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	$update     = $data_store->update_order_item( $item_id, $args );

	if ( false === $update ) {
		return false;
	}

	do_action( 'woocommerce_update_order_item', $item_id, $args );

	return true;
}

/**
 * Delete an item from the order it belongs to based on item id.
 *
 * @param int $item_id       Item ID.
 * @param bool $adjust_stock True or false. If the item had it's stock reduced, this will restore the stock and make a note.
 *
 * @throws Exception    When `WC_Data_Store::load` validation fails.
 * @return bool
 */
function wc_delete_order_item( $item_id, $adjust_stock = false ) {
	$item_id = absint( $item_id );

	if ( ! $item_id ) {
		return false;
	}

	$data_store = WC_Data_Store::load( 'order-item' );

	do_action( 'woocommerce_before_delete_order_item', $item_id );

	if ( $adjust_stock ) {
		$item = WC_Order_Factory::get_order_item( $item_id );

		if ( $item->is_type( 'line_item' ) ) {
			$order_id           = wc_get_order_id_by_order_item_id( $item_id );
			$order              = wc_get_order( $order_id );
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

			if ( $order && $product && $item_stock_reduced && $product->managing_stock() ) {
				$item_name = $product->get_formatted_name();
				$new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'increase' );

				if ( is_wp_error( $new_stock ) ) {
					/* translators: %s item name. */
					$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
				} else {
					/* translators: %1$s: item name, %2$s stock change */
					$order->add_order_note( sprintf( __( '%1$s stock increased %2$s.', 'woocommerce' ), $item_name, ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock ) );
				}
			}
		}
	}

	$data_store->delete_order_item( $item_id );

	do_action( 'woocommerce_delete_order_item', $item_id );

	return true;
}

/**
 * WooCommerce Order Item Meta API - Update term meta.
 *
 * @param int    $item_id    Item ID.
 * @param string $meta_key   Meta key.
 * @param string $meta_value Meta value.
 * @param string $prev_value Previous value (default: '').
 *
 * @throws Exception         When `WC_Data_Store::load` validation fails.
 * @return bool
 */
function wc_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	if ( $data_store->update_metadata( $item_id, $meta_key, $meta_value, $prev_value ) ) {
		WC_Cache_Helper::incr_cache_prefix( 'object_' . $item_id ); // Invalidate cache.
		return true;
	}
	return false;
}

/**
 * WooCommerce Order Item Meta API - Add term meta.
 *
 * @param int    $item_id    Item ID.
 * @param string $meta_key   Meta key.
 * @param string $meta_value Meta value.
 * @param bool   $unique     If meta data should be unique (default: false).
 *
 * @throws Exception         When `WC_Data_Store::load` validation fails.
 * @return int               New row ID or 0.
 */
function wc_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique = false ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	$meta_id    = $data_store->add_metadata( $item_id, $meta_key, $meta_value, $unique );

	if ( $meta_id ) {
		WC_Cache_Helper::incr_cache_prefix( 'object_' . $item_id ); // Invalidate cache.
		return $meta_id;
	}
	return 0;
}

/**
 * WooCommerce Order Item Meta API - Delete term meta.
 *
 * @param int    $item_id    Item ID.
 * @param string $meta_key   Meta key.
 * @param string $meta_value Meta value (default: '').
 * @param bool   $delete_all Delete all meta data, defaults to `false`.
 *
 * @throws Exception         When `WC_Data_Store::load` validation fails.
 * @return bool
 */
function wc_delete_order_item_meta( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	if ( $data_store->delete_metadata( $item_id, $meta_key, $meta_value, $delete_all ) ) {
		WC_Cache_Helper::incr_cache_prefix( 'object_' . $item_id ); // Invalidate cache.
		return true;
	}
	return false;
}

/**
 * WooCommerce Order Item Meta API - Get term meta.
 *
 * @param int    $item_id Item ID.
 * @param string $key     Meta key.
 * @param bool   $single  Whether to return a single value. (default: true).
 *
 * @throws Exception      When `WC_Data_Store::load` validation fails.
 * @return mixed
 */
function wc_get_order_item_meta( $item_id, $key, $single = true ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	return $data_store->get_metadata( $item_id, $key, $single );
}

/**
 * Get order ID by order item ID.
 *
 * @param int $item_id Item ID.
 *
 * @throws Exception    When `WC_Data_Store::load` validation fails.
 * @return int
 */
function wc_get_order_id_by_order_item_id( $item_id ) {
	$data_store = WC_Data_Store::load( 'order-item' );
	return $data_store->get_order_id_by_order_item_id( $item_id );
}
