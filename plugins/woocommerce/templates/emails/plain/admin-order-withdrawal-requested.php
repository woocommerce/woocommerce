<?php
/**
 * Admin order withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.1.0
 *
 * @var string               $email_heading             Email heading.
 * @var string               $additional_content        Additional content below the body.
 * @var array<string,string> $withdrawal_data           Withdrawal request data.
 * @var array<string,string> $detail_rows               Withdrawal request detail rows.
 * @var \WC_Order|null       $matched_order             Matched order, if found.
 * @var bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
 * @var string               $withdrawal_window_warning Withdrawal window warning message.
 * @var bool                 $sent_to_admin             Whether sent to admin.
 * @var bool                 $plain_text                Whether plain-text variant.
 * @var \WC_Email            $email                     Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'A customer submitted an order withdrawal request.', 'woocommerce' ) . "\n\n";

if ( $matched_order instanceof WC_Order ) {
	echo esc_html__( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ) . "\n\n";
} else {
	echo esc_html__( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ) . "\n\n";
}

foreach ( $detail_rows as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
}

if ( $matched_order instanceof WC_Order ) {
	echo "\n";

	if ( $outside_withdrawal_window ) {
		echo esc_html( $withdrawal_window_warning ) . "\n\n";
	}

	printf(
		/* translators: %d: order ID. */
		esc_html__( 'Matched order ID: %d', 'woocommerce' ),
		absint( $matched_order->get_id() )
	);
	echo "\n";

	if ( '' !== $matched_order->get_edit_order_url() ) {
		echo esc_url( $matched_order->get_edit_order_url() ) . "\n";
	}
}

echo "\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
