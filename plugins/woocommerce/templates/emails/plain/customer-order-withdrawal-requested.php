<?php
/**
 * Customer order withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-order-withdrawal-requested.php.
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
 * @var string              $email_heading      Email heading.
 * @var string              $additional_content Additional content below the body.
 * @var array<string,string> $withdrawal_data    Withdrawal request data.
 * @var array<string,string> $detail_rows        Withdrawal request detail rows.
 * @var bool                $sent_to_admin      Whether sent to admin.
 * @var bool                $plain_text         Whether plain-text variant.
 * @var \WC_Email           $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'We have received your request to withdraw from the order below.', 'woocommerce' ) . "\n\n";

foreach ( $detail_rows as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
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
