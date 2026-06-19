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
 * @version 11.0.0
 *
 * @var string    $email_heading      Email heading.
 * @var string    $additional_content Additional content below the body.
 * @var string    $user_display_name  Customer's display name.
 * @var string    $user_login         Customer's username.
 * @var string    $user_email         Customer's email address.
 * @var string    $blogname           Site name.
 * @var bool      $sent_to_admin      Whether sent to admin.
 * @var bool      $plain_text         Whether plain-text variant.
 * @var \WC_Email $email              Email object.
 * @var bool      $password_generated Whether password was generated.
 * @var string    $set_password_url   Set password URL.
 * @var string    $verify_url         Email verify URL.
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
} else {
	echo esc_html__( 'To confirm your email address, visit the following address: ', 'woocommerce' ) . "\n\n";
	echo esc_html( $verify_url ) . "\n\n";
	printf(
		/* translators: %s: the customer's email address. */
		esc_html__( "Once you've confirmed that %s is your email address, we'll link any past orders to your account.", 'woocommerce' ),
		esc_html( $user_email )
	);
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
