<?php
/**
 * Customer note email (inital block version)
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
<h2> <?php echo esc_html__( 'A note has been added to your order', 'woocommerce' ); ?> </h2>
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
<p> <?php echo esc_html__( 'The following note has been added to your order:', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:quote {"lock":{"move":false,"remove":true}} -->
<blockquote class="wp-block-quote">
<!-- wp:paragraph {"lock":{"move":false,"remove":true}} -->
<p> <?php echo '| <!--[woocommerce/admin-order-note]--> |'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  </p>
<!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'As a reminder, here are your order details:', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER ); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph -->
<p>
<?php
/* translators: %s: Store admin email */
	printf( esc_html__( 'Thanks again! If you need any help with your order, please contact us at %s,', 'woocommerce' ), '<!--[woocommerce/store-email]-->' );
?>
	</p>
<!-- /wp:paragraph -->
