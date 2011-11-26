<?php if (!defined('ABSPATH')) exit; ?>

<?php global $order_id, $woocommerce; $order = &new woocommerce_order( $order_id ); ?>

<?php do_action('woocommerce_email_header'); ?>

<p><?php _e("Your order is complete. Your order's details are below:", 'woothemes'); ?></p>

<?php do_action('woocommerce_email_before_order_table', $order, false); ?>

<h2><?php echo __('Order #:', 'woothemes') . ' ' . $order->id; ?></h2>

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
		<?php echo $order->email_order_items_table( true ); ?>
	</tbody>
</table>

<?php do_action('woocommerce_email_after_order_table', $order, false); ?>

<h2><?php _e('Customer details', 'woothemes'); ?></h2>

<?php if ($order->billing_email) : ?>
	<p><strong><?php _e('Email:', 'woothemes'); ?></strong> <?php echo $order->billing_email; ?></p>
<?php endif; ?>
<?php if ($order->billing_phone) : ?>
	<p><strong><?php _e('Tel:', 'woothemes'); ?></strong> <?php echo $order->billing_phone; ?></p>
<?php endif; ?>

<div style="float:left; width: 49%;">

	<h3><?php _e('Billing address', 'woothemes'); ?></h3>
	
	<p>
		<?php echo $order->billing_first_name . ' ' . $order->billing_last_name; ?><br/>
		<?php if ($order->billing_company) : ?><?php echo $order->billing_company; ?><br/><?php endif; ?>
		<?php echo $order->formatted_billing_address; ?>
	</p>

</div>

<div style="float:right; width: 49%;">

	<h3><?php _e('Shipping address', 'woothemes'); ?></h3>
	
	<p>
		<?php echo $order->shipping_first_name . ' ' . $order->shipping_last_name; ?><br/>
		<?php if ($order->shipping_company) : ?><?php echo $order->shipping_company; ?><br/><?php endif; ?>
		<?php echo $order->formatted_shipping_address; ?>
	</p>

</div>

<div style="clear:both;"></div>

<?php do_action('woocommerce_email_footer'); ?>