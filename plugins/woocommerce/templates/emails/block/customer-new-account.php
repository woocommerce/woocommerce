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
<h2>
<?php
/* translators: %s: Site title*/
printf( esc_html__( 'Welcome to %s', 'woocommerce' ), '<!--[woocommerce/site-title]-->' );
?>
</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>
<?php
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), '<!--[woocommerce/customer-first-name]-->' );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>
<?php
	/* translators: %s: Site title */
	printf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), '<!--[woocommerce/site-title]-->' );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>
<?php
/* translators: %s: Username */
echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), '<!--[woocommerce/customer-username]-->' ), array( 'b' => array() ) );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>
<?php
	$text = '<a data-link-href="%1$s" contenteditable="false" style="text-decoration: underline;"> %2$s </a>';
	printf( wp_kses_post( $text ), '[woocommerce/my-account-url]', esc_html__( 'My account', 'woocommerce' ) );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'We look forward to seeing you soon.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

