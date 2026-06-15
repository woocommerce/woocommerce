<?php
/**
 * Customer verify email address email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-verify-email.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 11.0.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name, or username if name is not available */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<p><?php esc_html_e( 'Please verify your email address by clicking the button below.', 'woocommerce' ); ?></p>
<p><a href="<?php echo esc_url( $verify_url ); ?>" class="button"><?php esc_html_e( 'Verify email address', 'woocommerce' ); ?></a></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 *
 * @since 11.0.0
 */
do_action( 'woocommerce_email_footer', $email );
