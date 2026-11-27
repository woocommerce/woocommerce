<?php
/**
 * Customer Stock Notification Confirm template.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-stock-notification-verified.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_intro_content', $intro_content, $notification ) ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

echo "\n\n----------------------------------------\n\n";

/**
 * Hook: woocommerce_email_stock_notification_product.
 *
 * @since 10.2.0
 */
do_action( 'woocommerce_email_stock_notification_product', $product, $notification, true, $email );

$footer_lines = array();
// The sign-up is still active, so guests, and anyone who can't reach their account, can cancel it in one click.
if ( $is_guest || ! $account_link ) {
	// translators: %s: Unsubscribe URL.
	$footer_lines[] = sprintf( esc_html__( 'To stop receiving these messages, unsubscribe: %s', 'woocommerce' ), esc_url( $unsubscribe_link ) );
}
if ( $account_link ) {
	if ( $is_guest ) {
		$footer_lines[] = esc_html__( 'Create an account or log in with this email address to manage your notifications.', 'woocommerce' );
	}
	// translators: %s: Account management URL.
	$footer_lines[] = sprintf( esc_html__( 'Manage notifications: %s', 'woocommerce' ), esc_url( $account_link ) );
}
echo implode( "\n", $footer_lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each line is escaped above.
echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
