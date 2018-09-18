<?php

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
	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_items() as $order_item ) {
		$wpdb->replace(
			$wpdb->prefix . 'wc_admin_order_product_lookup',
			array(
				'order_item_id'         => $order_item->get_id(),
				'order_id'              => $order->get_id(),
				'product_id'            => $order_item->get_product_id( 'edit' ),
				'customer_id'           => ( 0 < $order->get_customer_id( 'edit' ) ) ? $order->get_customer_id( 'edit' ) : null,
				'product_qty'           => $order_item->get_quantity( 'edit' ),
				'product_gross_revenue' => $order_item->get_subtotal( 'edit' ),
				'date_created'          => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
			),
			array(
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
add_action( 'save_post', 'wc_admin_order_product_lookup_entry', 10, 1 );

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
				'order_id'              => $order_id,
				'coupon_id'             => $coupon_item->get_id(),
				'coupon_gross_discount' => $coupon_item->get_discount(),
				'date_created'          => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
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
