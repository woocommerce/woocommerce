<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-reset-password.php.
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
 *
 * @var string    $email_heading      Email heading.
 * @var string    $additional_content Additional content below the body.
 * @var string    $user_display_name  Customer's display name.
 * @var string    $blogname           Site name.
 * @var bool      $sent_to_admin      Whether sent to admin.
 * @var bool      $plain_text         Whether plain-text variant.
 * @var \WC_Email $email              Email object.
 * @var string    $reset_key          Reset key.
 * @var string    $user_id            User ID.
 * @var string    $user_login         User login.
 * @var string    $reset_url          Reset password URL.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name, or username if name is not available */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	esc_html_e( 'We received a request to update your password. To reset your password, click the link below:', 'woocommerce' );
	echo "\n\n----------------------------------------\n\n";
	echo esc_url( $reset_url );
	echo "\n\n----------------------------------------\n\n";
	esc_html_e( "If you didn't request this email, there's nothing to worry about, and you can safely ignore it.", 'woocommerce' );
} else {
	/* translators: %s: Store name */
	echo sprintf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	/* translators: %s: Customer username */
	echo sprintf( esc_html__( 'Username: %s', 'woocommerce' ), esc_html( $user_login ) ) . "\n\n";
	echo esc_html__( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce' ) . "\n\n";
	echo esc_url( $reset_url ) . "\n\n";
}

echo "\n\n----------------------------------------\n\n";

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
