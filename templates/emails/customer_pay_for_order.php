<?php if (!defined('ABSPATH')) exit; ?>

<?php global $order_id, $order, $woocommerce; ?>

<?php do_action('woocommerce_email_header'); ?>

<?php if ($order->status=='pending') : ?>

	<p><?php echo sprintf( __("An order has been created for you on &ldquo;%s&rdquo;. To pay for this order please use the following link: %s", 'woothemes'), get_bloginfo('name'), $order->get_checkout_payment_url() ); ?></p>
	
<?php endif; ?>

<?php do_action('woocommerce_email_before_order_table', $order, false); ?>

<h2><?php echo __('Order #', 'woothemes') . $order->id; ?></h2>

<table cellspacing="0" cellpadding="6" style="width: 100%;" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left;"><?php _e('Product', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left;"><?php _e('Quantity', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left;"><?php _e('Price', 'woothemes'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th scope="row" colspan="2" style="text-align:left; border-top: 4px solid #eee;"><?php _e('Cart Subtotal:', 'woothemes'); ?></th>
			<td style="text-align:left; border-top: 4px solid #eee;"><?php echo $order->get_subtotal_to_display(); ?></td>
		</tr>
		<?php if ($order->get_cart_discount() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Cart Discount:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->get_cart_discount()); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_shipping() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Shipping:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo $order->get_shipping_to_display(); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_total_tax() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php echo $woocommerce->countries->tax_or_vat(); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->get_total_tax()); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_order_discount() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Order Discount:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->get_order_discount()); ?></td>
		</tr><?php endif; ?>
		<tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Order Total:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->get_order_total()); ?></td>
		</tr>
	</tfoot>
	<tbody>
		<?php echo $order->email_order_items_table(); ?>
	</tbody>
</table>

<?php do_action('woocommerce_email_after_order_table', $order, false); ?>

<?php do_action('woocommerce_email_footer'); ?>