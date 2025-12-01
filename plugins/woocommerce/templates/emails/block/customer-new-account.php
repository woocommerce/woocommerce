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
 * @version 10.2.0
 */

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php
/* translators: %s: Site title*/
printf( esc_html__( 'Welcome to %s', 'woocommerce' ), '<!--[woocommerce/site-title]-->' );
?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), '<!--[woocommerce/customer-first-name]-->' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php
	/* translators: %s: Site title */
	printf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), '<!--[woocommerce/site-title]-->' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php
/* translators: %s: Username */
echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), '<!--[woocommerce/customer-username]-->' ), array( 'b' => array() ) );
?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woocommerce/email-content -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php
	$link_template = '<a data-link-href="%1$s" contenteditable="false" style="text-decoration: underline;">%2$s</a>';
	printf(
		'%s',
		wp_kses_post(
			sprintf(
				$link_template,
				esc_attr( '[woocommerce/my-account-url]' ),
				esc_html__( 'My account', 'woocommerce' )
			)
		)
	);
	?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'We look forward to seeing you soon.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

