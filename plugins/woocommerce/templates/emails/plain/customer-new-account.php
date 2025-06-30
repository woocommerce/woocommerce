<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer username */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_login ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	echo "----------------------------------------\n\n";
	/* translators: %s: Username */
	echo sprintf( esc_html__( 'Username: %s.', 'woocommerce' ), esc_html( $user_login ) ) . "\n\n";
	echo "----------------------------------------\n\n";
	echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ) . "\n\n";
	echo esc_html( wc_get_page_permalink( 'myaccount' ) ) . "\n\n";
} else {
	/* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */
	echo sprintf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce' ), esc_html( $blogname ), esc_html( $user_login ), esc_html( wc_get_page_permalink( 'myaccount' ) ) ) . "\n\n";
}

// Only send the set new password link if the user hasn't set their password during sign-up.
if ( $password_generated && $set_password_url ) {
	/* translators: URL follows */
	echo esc_html__( 'To set your password, visit the following address: ', 'woocommerce' ) . "\n\n";
	echo esc_html( $set_password_url ) . "\n\n";
}

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
