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
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $items as $item_id => $item ) :
	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {

		$product = $item->get_product();

		echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );

		if ( $show_sku && $product->get_sku() ) {
			echo ' (#' . $product->get_sku() . ')';
		}

		echo ' X ' . apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item );
		echo ' = ' . $order->get_formatted_line_subtotal( $item ) . "\n";

		// allow other plugins to add additional product information here
		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

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
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
	}

	// Note
	if ( $show_purchase_note && ( $purchase_note = get_post_meta( $product->id, '_purchase_note', true ) ) ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}

	echo "\n\n";

endforeach;
