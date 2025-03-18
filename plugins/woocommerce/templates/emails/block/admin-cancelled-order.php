<?php
/**
 * Admin cancelled order email (initial block content)
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
<h2> <?php echo esc_html__( 'Order cancelled: #{order_number}', 'woocommerce' ); ?> </h2>
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
	/* translators: %1$s: Order number. %2$s: Customer full name */
	$text = __( 'We’re getting in touch to let you know that order #%1$s from %2$s has been cancelled.', 'woocommerce' );
	echo sprintf( esc_html( $text ), esc_html( $order->get_order_number() ), esc_html( $order->get_formatted_billing_full_name() ) );
	?>
</p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content"> <?php echo esc_html(BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER); ?> </div>
<!-- /wp:woo/email-content -->

<!-- wp:paragraph -->
<p> <?php echo esc_html__( 'Thanks for reading.', 'woocommerce' ); ?> </p>
<!-- /wp:paragraph -->
