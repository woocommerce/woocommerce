<?php
/**
 * Customer fulfillment created email (initial block version)
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
 * @version 10.5.0
 */

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"> <?php echo esc_html__( 'Your item is on the way!', 'woocommerce' ); ?> </h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php
	echo esc_html__( 'Woo! Some items you purchased are being fulfilled. You can use the below information to track your shipment:', 'woocommerce' );
?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woocommerce/email-content -->

<!-- wp:paragraph -->
<p><?php
echo esc_html__( 'Please note that couriers may need some time to provide the latest shipping information.', 'woocommerce' );
?></p>
<!-- /wp:paragraph -->
