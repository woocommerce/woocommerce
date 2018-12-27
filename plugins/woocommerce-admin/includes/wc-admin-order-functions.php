<?php
/**
 * Order Functions
 *
 * @package  WooCommerce Admin
 */

/**
 * Make an entry in the wc_admin_order_product_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_admin_order_product_lookup_entry( $order_id ) {
	global $wpdb;

	$order = wc_get_order( $order_id );

	// This hook gets called on refunds as well, so return early to avoid errors.
	if ( ! $order || 'shop_order_refund' === $order->get_type() ) {
		return;
	}

	if ( 'refunded' === $order->get_status() ) {
		$wpdb->delete(
			$wpdb->prefix . 'wc_order_product_lookup',
			array( 'order_id' => $order->get_id() ),
			array( '%d' )
		);
		return;
	}

	$refunds = wc_admin_get_order_refund_items( $order );

	foreach ( $order->get_items() as $order_item ) {
		$order_item_id     = $order_item->get_id();
		$quantity_refunded = isset( $refunds[ $order_item_id ] ) ? $refunds[ $order_item_id ]['quantity'] : 0;
		$amount_refunded   = isset( $refunds[ $order_item_id ] ) ? $refunds[ $order_item_id ]['subtotal'] : 0;
		if ( $quantity_refunded >= $order_item->get_quantity( 'edit' ) ) {
			$wpdb->delete(
				$wpdb->prefix . 'wc_order_product_lookup',
				array( 'order_item_id' => $order_item_id ),
				array( '%d' )
			);
		} else {
			$wpdb->replace(
				$wpdb->prefix . 'wc_order_product_lookup',
				array(
					'order_item_id'       => $order_item_id,
					'order_id'            => $order->get_id(),
					'product_id'          => $order_item->get_product_id( 'edit' ),
					'variation_id'        => $order_item->get_variation_id( 'edit' ),
					'customer_id'         => ( 0 < $order->get_customer_id( 'edit' ) ) ? $order->get_customer_id( 'edit' ) : null,
					'product_qty'         => $order_item->get_quantity( 'edit' ) - $quantity_refunded,
					'product_net_revenue' => $order_item->get_subtotal( 'edit' ) - $amount_refunded,
					'date_created'        => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				),
				array(
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%f',
					'%s',
				)
			);
		}
	}
}
// TODO: maybe replace these with woocommerce_create_order, woocommerce_update_order, woocommerce_trash_order, woocommerce_delete_order, as clean_post_cache might be called in other circumstances and trigger too many updates?
add_action( 'save_post', 'wc_admin_order_product_lookup_entry' );
add_action( 'woocommerce_order_refunded', 'wc_admin_order_product_lookup_entry' );
add_action( 'clean_post_cache', 'wc_admin_order_product_lookup_entry' );

/**
 * Get total refund amount and line items refunded.
 *
 * @param object $order WC_Order.
 * @return array Refunded line items with line item ID as key.
 */
function wc_admin_get_order_refund_items( $order ) {
	$refunds             = $order->get_refunds();
	$refunded_line_items = array();
	foreach ( $refunds as $refund ) {
		foreach ( $refund->get_items() as $refunded_item ) {
			$line_item_id                          = wc_get_order_item_meta( $refunded_item->get_id(), '_refunded_item_id', true );
			if ( ! isset( $refunded_line_items[ $line_item_id ] ) ) {
				$refunded_line_items[ $line_item_id ]['quantity'] = 0;
				$refunded_line_items[ $line_item_id ]['subtotal'] = 0;
			}
			$refunded_line_items[ $line_item_id ]['quantity'] += absint( $refunded_item['quantity'] );
			$refunded_line_items[ $line_item_id ]['subtotal'] += abs( $refunded_item['subtotal'] );
		}
	}
	return $refunded_line_items;
}

/**
 * Runs the sync function for order product lookup table on refund delete.
 *
 * @param int $refund_id Refund ID.
 * @param int $order_id Order ID.
 */
function wc_admin_order_product_sync_on_refund_delete( $refund_id, $order_id ) {
	wc_admin_order_product_lookup_entry( $order_id );
}
add_action( 'woocommerce_refund_deleted', 'wc_admin_order_product_sync_on_refund_delete', 10, 2 );

/**
 * Make an entry in the wc_order_tax_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_order_tax_lookup_entry( $order_id ) {
	global $wpdb;
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	foreach ( $order->get_items( 'tax' ) as $tax_item ) {
		$wpdb->replace(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order->get_id(),
				'date_created' => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				'tax_rate_id'  => $tax_item->get_rate_id(),
				'shipping_tax' => $tax_item->get_shipping_tax_total(),
				'order_tax'    => $tax_item->get_tax_total(),
				'total_tax'    => $tax_item->get_tax_total() + $tax_item->get_shipping_tax_total(),
			),
			array(
				'%d',
				'%s',
				'%d',
				'%f',
				'%f',
				'%f',
			)
		);
	}
}
add_action( 'save_post', 'wc_order_tax_lookup_entry', 10, 1 );
add_action( 'clean_post_cache', 'wc_order_tax_lookup_entry', 10, 1 );

/**
 * Make an entry in the wc_order_coupon_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_order_coupon_lookup_entry( $order_id ) {
	global $wpdb;

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$coupon_items = $order->get_items( 'coupon' );
	foreach ( $coupon_items as $coupon_item ) {
		$wpdb->replace(
			$wpdb->prefix . 'wc_order_coupon_lookup',
			array(
				'order_id'        => $order_id,
				'coupon_id'       => wc_get_coupon_id_by_code( $coupon_item->get_code() ),
				'discount_amount' => $coupon_item->get_discount(),
				'date_created'    => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
			),
			array(
				'%d',
				'%d',
				'%f',
				'%s',
			)
		);
	}
}
add_action( 'save_post', 'wc_order_coupon_lookup_entry', 10, 1 );
add_action( 'clean_post_cache', 'wc_order_coupon_lookup_entry', 10, 1 );
