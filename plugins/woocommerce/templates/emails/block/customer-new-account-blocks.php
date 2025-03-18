<?php
/**
 * Customer new account email (initial block content).
 *
 * This is intended as a replacement to WC_Email_Customer_New_Account(),
 * with a set password link instead of including the new password in email
 * content.
 *
 * @package  WooCommerce/Blocks
 * @version 9.9.0
 */

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

defined( 'ABSPATH' ) || exit;

?>

<!-- wp:heading -->
<h2> <?php echo esc_html__( 'Welcome to {site_title}', 'woocommerce' ); ?> </h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>
<?php
if ( isset( $order, $order->get_billing_first_name ) && ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), esc_html( $blogname ) );
?> </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php
/* translators: %s: Username */
echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), esc_html( $user_login ) ), array( 'b' => array() ) );
?> </p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html(BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php printf( esc_html__( 'My account', 'woocommerce' ) ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'We look forward to seeing you soon.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

