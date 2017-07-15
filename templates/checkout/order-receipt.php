<?php
/**
 * Checkout Order Receipt Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-receipt.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<ul class="order_details">
	<li class="order">
		<?php _e( 'Order number:', 'woocommerce' ); ?>
		<strong><?php echo $order->get_order_number(); ?></strong>
	</li>
	<li class="date">
		<?php _e( 'Date:', 'woocommerce' ); ?>
		<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
	</li>
	<li class="total">
		<?php _e( 'Total:', 'woocommerce' ); ?>
		<strong><?php echo $order->get_formatted_order_total(); ?></strong>
	</li>
	<?php if ( $order->get_payment_method_title() ) : ?>
	<li class="method">
		<?php _e( 'Payment method:', 'woocommerce' ); ?>
		<strong><?php
			echo wp_kses_post( $order->get_payment_method_title() );
		?></strong>
	</li>
	<?php endif; ?>
</ul>

<?php do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order_id ); ?>

<div class="clear"></div>