<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.1.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

if ( $email_improvements_enabled ) {
	add_filter( 'woocommerce_order_shipping_to_display_shipped_via', '__return_false' );
}

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order ID. %2$s: Order date */
	echo wp_kses_post( sprintf( esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' ), $order->get_order_number(), wc_format_datetime( $order->get_date_created() ) ) ) . "\n";
	echo "\n==========\n";
} else {
	/* translators: %1$s: Order ID. %2$s: Order date */
	echo wp_kses_post( wc_strtoupper( sprintf( esc_html__( '[Order #%1$s] (%2$s)', 'woocommerce' ), $order->get_order_number(), wc_format_datetime( $order->get_date_created() ) ) ) ) . "\n";
}
echo "\n" . wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$order,
	array(
		'show_sku'      => $sent_to_admin,
		'show_image'    => false,
		'image_size'    => array( 32, 32 ),
		'plain_text'    => true,
		'sent_to_admin' => $sent_to_admin,
	)
);

echo "==========\n\n";

$item_totals = $order->get_order_item_totals();

if ( $item_totals ) {
	foreach ( $item_totals as $total ) {
		if ( $email_improvements_enabled ) {
			$label = $total['label'];
			if ( isset( $total['meta'] ) ) {
				$label .= ' ' . $total['meta'];
			}
			echo wp_kses_post( str_pad( wp_kses_post( $label ), 40 ) );
			echo ' ';
			echo esc_html( str_pad( wp_kses( $total['value'], array() ), 20, ' ', STR_PAD_LEFT ) ) . "\n";
		} else {
			echo wp_kses_post( $total['label'] . "\t " . $total['value'] ) . "\n";
		}
	}
}

if ( $order->get_customer_note() ) {
	if ( $email_improvements_enabled ) {
		echo "\n" . esc_html__( 'Note:', 'woocommerce' ) . "\n" . wp_kses( wc_wptexturize_order_note( $order->get_customer_note() ), array() ) . "\n";
	} else {
		echo esc_html__( 'Note:', 'woocommerce' ) . "\t " . wp_kses( wc_wptexturize_order_note( $order->get_customer_note() ), array() ) . "\n";
	}
}

if ( $sent_to_admin ) {
	/* translators: %s: Order link. */
	echo "\n" . sprintf( esc_html__( 'View order: %s', 'woocommerce' ), esc_url( $order->get_edit_order_url() ) ) . "\n";
}

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
