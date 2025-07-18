<?php
/**
 * Email Fulfillment Items (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-fulfillment-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates\Emails\Plain
 * @version     10.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

foreach ( $items as $item_id => $item ) :
	/**
	 * Email Order Item Visibility hook.
	 *
	 * @since 2.5.0
	 * @param $visible Whether the item is visible in the email.
	 * @param WC_Order_Item_Product $item The order item object.
	 *
	 * @return bool
	 */
	if ( apply_filters(
		'woocommerce_order_item_visible',
		true,
		$item->item
	) ) {
		$product       = $item->item->get_product();
		$sku           = '';
		$purchase_note = '';

		if ( is_object( $product ) ) {
			$sku           = $product->get_sku();
			$purchase_note = $product->get_purchase_note();
		}

		/**
		 * Email Order Item Name hook.
		 *
		 * @since 2.1.0
		 * @since 2.4.0 Added $is_visible parameter.
		 * @param string        $product_name Product name.
		 * @param WC_Order_Item $item Order item object.
		 * @param bool          $is_visible Is item visible.
		 */
		$product_name = apply_filters( 'woocommerce_order_item_name', $item->item->get_name(), $item->item, false );
		/**
		 * Email Order Item Quantity hook.
		 *
		 * @since 2.4.0
		 * @param int           $quantity Item quantity.
		 * @param WC_Order_Item $item     Item object.
		 */
		$product_name .= ' × ' . apply_filters( 'woocommerce_email_order_item_quantity', $item->qty, $item->item );
		echo wp_kses_post( str_pad( wp_kses_post( $product_name ), 40 ) );
		echo ' ';
		echo esc_html( str_pad( wp_kses( $order->get_formatted_line_subtotal( $item->item ), array() ), 20, ' ', STR_PAD_LEFT ) ) . "\n";

		// SKU.
		if ( $show_sku && $sku ) {
			echo esc_html( '(#' . $sku . ")\n" );
		}
	}
	// Note.
	if ( $show_purchase_note && $purchase_note ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
endforeach;
