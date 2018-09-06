<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s Customer first name */ ?>
<p><?php esc_html_e( 'Hi %s,', 'woocommerce' ); ?>
<p><?php esc_html_e( 'Someone (hopefully you) has requested a password reset for the following account on:', 'woocommerce' ); ?></p>
<?php /* translators: %s Store name */ ?>
<p><?php printf( esc_html__( 'Store: %s', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
<?php /* translators: %s Customer username */ ?>
<p><?php printf( esc_html__( 'Username: %s', 'woocommerce' ), $user_login ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
<p><?php esc_html_e( 'If this was a mistake, just ignore this email and nothing will happen. Otherwise, follow this link to reset your password now:', 'woocommerce' ); ?></p>
<p>
	<a class="link" href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>"><?php // phpcs:ignore ?>
		<?php esc_html_e( 'Click here to reset your password', 'woocommerce' ); ?>
	</a>
</p>
<p><?php esc_html_e( 'Good luck!', 'woocommerce' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
