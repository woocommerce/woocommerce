<?php global $order_id; $order = &new woocommerce_order( $order_id ); ?>

<?php do_action('woocommerce_email_header'); ?>

<p><?php echo __('You have received an order from', 'woothemes') . ' ' . $order->billing_first_name . ' ' . $order->billing_last_name . __(". Their order is as follows:", 'woothemes'); ?></p>

<?php do_action('woocommerce_email_before_order_table', $order, true); ?>

<h2><?php echo __('Order #:', 'woothemes') . ' ' . $order->id; ?></h2>

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
			<td style="text-align:left; padding-top: 12px;"><?php echo $order->get_subtotal_to_display(); ?></td>
		</tr>
		<?php if ($order->order_shipping > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Shipping:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo $order->get_shipping_to_display(); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->order_discount > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Discount:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->order_discount); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_total_tax() > 0) : ?><tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Tax:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->get_total_tax()); ?></td>
		</tr><?php endif; ?>
		<tr>
			<th scope="row" colspan="2" style="text-align:left;"><?php _e('Total:', 'woothemes'); ?></th>
			<td style="text-align:left;"><?php echo woocommerce_price($order->order_total); ?> <?php _e('- via', 'woothemes'); ?> <?php echo ucwords($order->payment_method); ?></td>
		</tr>
	</tfoot>
	<tbody>
		<?php echo $order->email_order_items_table( false, true ); ?>
	</tbody>
</table>

<?php do_action('woocommerce_email_after_order_table', $order, true); ?>

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