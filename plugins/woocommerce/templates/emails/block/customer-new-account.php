<?php
/**
 * Customer new account email (initial block content).
 *
 * This template can be overridden by editing it in the WooCommerce email editor.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
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
if ( isset( $user_login ) ) {
	/* translators: %s: Customer username */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_login ) );
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

