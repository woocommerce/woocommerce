<?php
/**
 * Customer abandoned cart recovery email (initial block content)
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
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"> <?php echo esc_html__( 'Pick up where you left off', 'woocommerce' ); ?> </h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), '<!--[woocommerce/customer-first-name]-->' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'Your items are still in your cart. We’ve saved everything, so come back when you’re ready.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php
/* translators: 1: order number, 2: order date */
$order_meta_format = esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' );
printf( $order_meta_format, '<!--[woocommerce/order-number]-->', '<!--[woocommerce/order-date]-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $order_meta_format is escaped above; personalization tokens are literal HTML comments.
?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"12px"}}} -->
<p style="font-size:12px"><a href="<!--[woocommerce/email-unsubscribe-url]-->"><?php echo esc_html__( 'Unsubscribe from checkout recovery emails', 'woocommerce' ); ?></a></p>
<!-- /wp:paragraph -->
