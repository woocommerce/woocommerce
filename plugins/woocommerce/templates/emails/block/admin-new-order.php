<?php
/**
 * Admin new order email (initial block content)
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
/* translators: %s: order number */
printf( esc_html__( 'New order: #%s,', 'woocommerce' ), '<!--[woocommerce/order-number]-->' );
?>
</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>
<?php
/* translators: %s: Customer full name */
printf( esc_html__( 'Woo! You’ve received a new order from %s', 'woocommerce' ), '<!--[woocommerce/customer-full-name]-->' );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"> <?php echo esc_html__( 'Congratulations on the sale!', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->
