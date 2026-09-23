<?php
/**
 * Customer cart recovery email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-cart-recovery.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.3.0
 */

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed -- Plain text output needs specific spacing without tabs

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'Hi,', 'woocommerce' ) . "\n\n";
echo esc_html__( 'You left these items in your cart. They\'re ready when you are.', 'woocommerce' ) . "\n\n";

foreach ( $items as $item ) {
	/* translators: 1: product name, 2: quantity */
	echo esc_html( wp_strip_all_tags( sprintf( __( '%1$s x %2$d', 'woocommerce' ), $item['product']->get_name(), $item['quantity'] ) ) ) . "\n";
}
echo "\n";

if ( ! empty( $recovery_url ) ) {
	echo esc_html__( 'Return to your cart:', 'woocommerce' ) . "\n";
	echo esc_url_raw( $recovery_url ) . "\n\n";
}

echo "----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
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

// phpcs:enable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
