<?php
/**
 * Fraud Protection OTP email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/fraud-protection-otp.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'We received a request to verify your email address for fraud protection purposes.', 'woocommerce' ) . "\n\n";
echo esc_html__( 'Use the following verification code to complete your purchase:', 'woocommerce' ) . "\n\n";

echo "----------------------------------------\n";
echo "    " . esc_html( $otp_code ) . "\n";
echo "----------------------------------------\n\n";

/* translators: %d: expiration time in minutes */
echo sprintf( esc_html__( 'This code will expire in %d minutes.', 'woocommerce' ), (int) $expiration_minutes ) . "\n\n";

echo esc_html__( 'For security reasons, do not share this code with anyone.', 'woocommerce' ) . "\n\n";

echo "----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
