<?php
/**
 * Customer plain email about a renewal payment requiring authentication.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/plain/failed-renewal-authentication.php.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

echo esc_html( $email_heading ) . "\n\n";

printf(
	/* translators: %1$s: site title, %2$s: authorization URL. */
	esc_html_x( 'The automatic payment to renew your subscription with %1$s has failed. To reactivate the subscription, please log in and authorize the renewal from your account page: %2$s', 'In failed renewal authentication email', 'woocommerce' ),
	esc_html( get_bloginfo( 'name' ) ),
	esc_url( $authorization_url )
);

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook for the woocommerce_subscriptions_email_order_details.
 *
 * @since 11.0.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Filters the email footer text.
 *
 * @since 3.7.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
