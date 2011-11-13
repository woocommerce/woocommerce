<?php if (!defined('ABSPATH')) exit; ?>

<?php global $order_id, $the_order; ?>

<?php do_action('woocommerce_email_header'); ?>

<?php if ($the_order->status=='pending') : ?>

	<p><?php echo sprintf( __("An order has been created for you on &ldquo;%s&rdquo;. To pay for this order please use the following link: %s", 'woothemes'), get_bloginfo('name'), $the_order->get_checkout_payment_url() ); ?></p>
	
<?php endif; ?>

<?php do_action('woocommerce_email_before_order_table', $the_order, false); ?>

<h2><?php echo __('Order #:', 'woothemes') . ' ' . $the_order->id; ?></h2>

<table cellspacing="0" cellpadding="2" style="width: 100%;">
	<thead>
		<tr>
			<th scope="col" style="text-align:left;"><?php _e('Product', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left;"><?php _e('Quantity', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left;"><?php _e('Price', 'woothemes'); ?></th>
		</tr>
	</thead>
	<tfoot> 
		<tr>
			<th scope="row" colspan="2" style="text-align:left; padding-top: 12px;"><?php _e('Subtotal:', 'woothemes'); ?></th>
			<td style="text-align:left; padding-top: 12px;"><?php echo $the_order->get_subtotal_to_display(); ?></td>
		</tr>
		<?php if ($the_order->order_shipping > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Shipping:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo $the_order->get_shipping_to_display(); ?></td>
		</tr><?php endif; ?>
		<?php if ($the_order->order_discount > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Discount:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($the_order->order_discount); ?></td>
		</tr><?php endif; ?>
		<?php if ($the_order->get_total_tax() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Tax:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($the_order->get_total_tax()); ?></td>
		</tr><?php endif; ?>
		<tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Total:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($the_order->order_total); ?> <?php _e('- via', 'woothemes'); ?> <?php echo ucwords($the_order->payment_method); ?></td>
		</tr>
	</tfoot>
	<tbody>
		<?php echo $the_order->email_order_items_table(); ?>
	</tbody>
</table>

<?php do_action('woocommerce_email_after_order_table', $the_order, false); ?>

<?php do_action('woocommerce_email_footer'); ?>