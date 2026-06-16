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

<?php /* translators: %s: Customer first name, or username if name is not available. */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<p>
<?php
echo wp_kses_post(
	sprintf(
		/* translators: %s: the customer's email address. */
		__( "Once you've confirmed that %s is your email address, we'll link any past orders to your confirmed account.", 'woocommerce' ),
		'<strong>' . esc_html( $user_email ) . '</strong>'
	)
);
?>
</p>
<?php
$wc_verify_button_bg   = get_option( 'woocommerce_email_base_color', '#7f54b3' );
$wc_verify_button_text = wc_hex_is_light( $wc_verify_button_bg ) ? '#000000' : '#ffffff';
?>
<p style="margin: 24px 0;">
	<a href="<?php echo esc_url( $verify_url ); ?>" style="display:inline-block;padding:16px 32px;background-color:<?php echo esc_attr( $wc_verify_button_bg ); ?>;color:<?php echo esc_attr( $wc_verify_button_text ); ?>;border-radius:4px;font-weight:bold;font-size:15px;text-decoration:none;"><?php esc_html_e( 'Confirm email address', 'woocommerce' ); ?></a>
</p>

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
