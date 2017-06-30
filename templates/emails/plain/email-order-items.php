<?php
/**
 * Email Order Items (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( $items as $item_id => $item ) :
	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		$product = $item->get_product();
		echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
		if ( $show_sku && $product->get_sku() ) {
			echo ' (#' . $product->get_sku() . ')';
		}
		echo ' X ' . apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item );
		echo ' = ' . $order->get_formatted_line_subtotal( $item ) . "\n";
		// allow other plugins to add additional product information here
		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );
		echo strip_tags( wc_display_item_meta( $item, array(
			'before'    => "\n- ",
			'separator' => "\n- ",
			'after'     => "",
			'echo'      => false,
			'autop'     => false,
		) ) );
		if ( $show_download_links ) {
			echo strip_tags( wc_display_item_downloads( $item, array(
				'before'    => "\n- ",
				'separator' => "\n- ",
				'after'     => "",
				'echo'      => false,
				'show_url'  => true,
			) ) );
		}
		// allow other plugins to add additional product information here
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
	}
	// Note
	if ( $show_purchase_note && is_object( $product ) && ( $purchase_note = $product->get_purchase_note() ) ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
	echo "\n\n";
endforeach;
