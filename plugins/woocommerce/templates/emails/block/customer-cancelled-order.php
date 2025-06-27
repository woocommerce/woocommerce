<?php
/**
 * Customer cancelled order email - block template.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/block/customer-cancelled-order.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
 * @version 10.1.0
 */

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php
/* translators: %s: Order number */
printf( esc_html__( 'Order Cancelled: #%s', 'woocommerce' ), '<!--[woocommerce/order-number]-->' );
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
/* translators: %s: Order number */
printf( esc_html__( 'Your order #%s has been cancelled.', 'woocommerce' ), '<!--[woocommerce/order-number]-->' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph -->
<p><?php
/* translators: %s: Store admin email */
printf( esc_html__( 'We hope to see you again soon.', 'woocommerce' ) );
?></p>
<!-- /wp:paragraph -->
