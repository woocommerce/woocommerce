<?php
/**
 * Admin payment gateway enabled email (initial block content)
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
 * @version 10.7.0
 */

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Payment gateway enabled', 'woocommerce' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Hello,', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php
	/* translators: %s: Site title */
	printf( esc_html__( 'A payment gateway was just enabled on %s.', 'woocommerce' ), '<!--[woocommerce/site-title]-->' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woocommerce/email-content -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"> <?php echo esc_html__( 'If this was intentional, you can safely ignore and delete this email.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->
