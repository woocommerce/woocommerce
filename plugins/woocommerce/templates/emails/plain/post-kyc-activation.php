<?php
/**
 * Post-KYC activation reminder email plain text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/post-kyc-activation.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.0.0
 *
 * @var int    $stage
 * @var string $email_heading
 * @var string $additional_content
 * @var string $cta_url
 * @var string $cta_label
 */

defined( 'ABSPATH' ) || exit;

$stage_copy = array(
	7  => array(
		'heading' => __( "Your store is ready - let's make your first sale", 'woocommerce' ),
		'body'    => __( "Your WooPayments account is approved and ready to accept payments. Now it's about getting eyes on your store - share your link, tell your network, and make your first sale.", 'woocommerce' ),
	),
	14 => array(
		'heading' => __( 'Two weeks in - have you shared your store yet?', 'woocommerce' ),
		'body'    => __( 'Your account is fully approved and accepting payments. Share your store with your first potential customers to get that first sale.', 'woocommerce' ),
	),
	30 => array(
		'heading' => __( 'Your payments are ready - your first sale can be too', 'woocommerce' ),
		'body'    => __( 'Everything on the payments side is ready. The next step is getting your first customer through the door - share your store link and start spreading the word.', 'woocommerce' ),
	),
);

$content = $stage_copy[ $stage ] ?? $stage_copy[7];

echo esc_html( wp_strip_all_tags( $content['heading'] ) ) . "\n\n";
echo "=====================================================\n\n";
echo esc_html( $content['body'] ) . "\n\n";
echo esc_html( $cta_label ) . ': ' . esc_url_raw( $cta_url ) . "\n\n";

if ( $additional_content ) {
	echo "-----------------------------------------------------\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo "\n=====================================================\n\n";
/**
 * Filter the email footer text.
 *
 * @since 4.0.0
 *
 * @param string $email_footer_text Email footer text.
 */
echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
