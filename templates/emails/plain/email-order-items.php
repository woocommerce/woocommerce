<?php
/**
 * Email Order Items (plain)
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

foreach ( $items as $item ) :
	$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
	$item_meta    = new WC_Order_Item_Meta( $item['item_meta'] );

	// Title
	echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item );

	// SKU
	if ( $show_sku && $_product->get_sku() )
		echo ' (#' . $_product->get_sku() . ')';

	// Variation
	echo $item_meta->meta ? "\n" . nl2br( $item_meta->display( true, true ) ) : '';

	// Quantity
	echo "\n" . sprintf( __( 'Quantity: %s', 'woocommerce' ), $item['qty'] );

	// Cost
	echo "\n" . sprintf( __( 'Cost: %s', 'woocommerce' ), $order->get_formatted_line_subtotal( $item ) );

	// Download URLs
	if ( $show_download_links && $_product->exists() && $_product->is_downloadable() )
		echo "\n" . implode( "\n", $order->get_downloadable_file_urls( $item['product_id'], $item['variation_id'], $item ) );

	// Note
	if ( $show_purchase_note && $purchase_note = get_post_meta( $_product->id, '_purchase_note', true ) )
		echo "\n" . nl2br( $purchase_note );

	echo "\n\n";

endforeach;