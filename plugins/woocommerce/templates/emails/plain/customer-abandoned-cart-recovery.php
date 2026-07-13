<?php
/**
 * Customer abandoned cart recovery email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-abandoned-cart-recovery.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.0.0
 */

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed -- Plain text output needs specific spacing without tabs

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $order instanceof WC_Order && ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi,', 'woocommerce' ) . "\n\n";
}

echo esc_html__( 'Your items are still in your cart. We\'ve saved everything, so come back when you\'re ready.', 'woocommerce' ) . "\n\n";

if ( ! empty( $recovery_url ) ) {
	echo esc_html__( 'Finish checking out:', 'woocommerce' ) . "\n";
	echo esc_url( $recovery_url ) . "\n\n";
}

if ( $order instanceof WC_Order ) {
	$date_created = $order->get_date_created();
	printf(
		/* translators: 1: order number, 2: order date */
		esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		esc_html( $date_created ? wc_format_datetime( $date_created ) : '' )
	);
	echo "\n\n";
}

echo "----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

if ( ! empty( $unsubscribe_url ) ) {
	echo esc_html__( 'Unsubscribe from checkout recovery emails:', 'woocommerce' ) . "\n";
	echo esc_url( $unsubscribe_url ) . "\n\n";
}

/**
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

// phpcs:enable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
