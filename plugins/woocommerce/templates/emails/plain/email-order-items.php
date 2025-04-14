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
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates\Emails\Plain
 * @version     9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

foreach ( $items as $item_id => $item ) :
	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		$product       = $item->get_product();
		$sku           = '';
		$purchase_note = '';

		if ( is_object( $product ) ) {
			$sku           = $product->get_sku();
			$purchase_note = $product->get_purchase_note();
		}

		if ( $email_improvements_enabled ) {
			/**
			 * Email Order Item Name hook.
			 *
			 * @since 2.1.0
			 * @since 2.4.0 Added $is_visible parameter.
			 * @param string        $product_name Product name.
			 * @param WC_Order_Item $item Order item object.
			 * @param bool          $is_visible Is item visible.
			 */
			$product_name = apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
			/**
			 * Email Order Item Quantity hook.
			 *
			 * @since 2.4.0
			 * @param int           $quantity Item quantity.
			 * @param WC_Order_Item $item     Item object.
			 */
			$product_name .= ' × ' . apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item );
			echo wp_kses_post( str_pad( wp_kses_post( $product_name ), 40 ) );
			echo ' ';
			echo esc_html( str_pad( wp_kses( $order->get_formatted_line_subtotal( $item ), array() ), 20, ' ', STR_PAD_LEFT ) ) . "\n";
			if ( $show_sku && $sku ) {
				echo esc_html( '(#' . $sku . ")\n" );
			}
		} else {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			/**
			 * Email Order Item Name hook.
			 *
			 * @since 2.1.0
			 * @since 2.4.0 Added $is_visible parameter.
			 * @param string        $product_name Product name.
			 * @param WC_Order_Item $item Order item object.
			 * @param bool          $is_visible Is item visible.
			 */
			echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
			if ( $show_sku && $sku ) {
				echo ' (#' . $sku . ')';
			}
			/**
			 * Email Order Item Quantity hook.
			 *
			 * @since 2.4.0
			 * @param int           $quantity Item quantity.
			 * @param WC_Order_Item $item     Item object.
			 */
			echo ' X ' . apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item );
			echo ' = ' . $order->get_formatted_line_subtotal( $item ) . "\n";
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo strip_tags(
			wc_display_item_meta(
				$item,
				array(
					'before'    => "\n- ",
					'separator' => "\n- ",
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		);

		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
	}
	// Note.
	if ( $show_purchase_note && $purchase_note ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
	echo "\n\n";
endforeach;
