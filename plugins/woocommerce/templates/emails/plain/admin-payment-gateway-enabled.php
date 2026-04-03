<?php
/**
 * Admin payment gateway enabled email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-payment-gateway-enabled.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.7.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Username */
echo sprintf( esc_html__( 'Howdy %s,', 'woocommerce' ), esc_html( $username ) ) . "\n\n";

echo sprintf(
	/* translators: 1: gateway title, 2: site URL */
	esc_html__( 'The payment gateway "%1$s" was just enabled on this site: %2$s', 'woocommerce' ),
	esc_html( $gateway_title ),
	esc_html( home_url() )
) . "\n\n";

echo esc_html__( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', 'woocommerce' ) . "\n";
echo esc_url( $gateway_settings_url ) . "\n\n";

/* translators: %s: admin email address */
echo sprintf( esc_html__( 'This email has been sent to %s', 'woocommerce' ), esc_html( $admin_email ) ) . "\n\n";

echo "\n----------------------------------------\n\n";

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
 * @since 3.7.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
