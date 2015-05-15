<?php
/**
 * Update WC to 2.4.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Maintain the old coupon logic for upgrades
update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );

// Update fully refunded orders to ensure they have a refund line item so reports add up
$refunded_orders = get_posts( array(
	'posts_per_page' => -1,
	'post_type'      => 'shop_order',
	'post_status'    => array( 'wc-refunded' )
) );

foreach ( $refunded_orders as $refunded_order ) {
	$order_total    = get_post_meta( $refunded_order->ID, '_order_total', true );
	$refunded_total = $wpdb->get_var( $wpdb->prepare( "
		SELECT SUM( postmeta.meta_value )
		FROM $wpdb->postmeta AS postmeta
		INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
		WHERE postmeta.meta_key = '_refund_amount'
		AND postmeta.post_id = posts.ID
	", $refunded_order->ID ) );

	if ( $order_total > $refunded_total ) {
		$refund = wc_create_refund( array(
			'amount'     => $order_total - $refunded_total,
			'reason'     => __( 'Order Fully Refunded', 'woocommerce' ),
			'order_id'   => $refunded_order->ID,
			'line_items' => array(),
			'date'       => $refunded_order->post_modified
		) );
	}
}

wc_delete_shop_order_transients();